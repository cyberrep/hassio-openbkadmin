import {
  determineUpgradePlan,
  resolveUpdateTarget,
  versionsEqual,
  versionUpgrade,
  shouldTreatStatusAsSuccessful,
  getFailureDetails,
} from "./device_update_logic.js";
import { waitForI18n } from "./app";

const deviceContainerId = "progressbox";
const Level = { info: "info", error: "error", success: "success" };
const updateTargets = JSON.parse(document.getElementById("update_targets").value || "{}");
const sleep = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

// OpenBeken may need time to download, flash, reboot and reconnect to Wi-Fi.
const defaultTries = 18;
const defaultSleepDuration = 10000;
const initialOtaWait = 30000;
const defaultRetryOptions = { maxRetries: defaultTries, sleepDuration: defaultSleepDuration };

const fetchWithRetries = async (url, options, retryOptions = defaultRetryOptions, retryCount = 0) => {
  try {
    const response = await fetch(url, options);
    if (!response.ok) throw Error($.i18n("FETCH_ERROR", url, response.status));
    return response;
  } catch (error) {
    if (retryCount < retryOptions.maxRetries) {
      await sleep(retryOptions.sleepDuration);
      return fetchWithRetries(url, options, retryOptions, retryCount + 1);
    }
    throw error;
  }
};

async function doAjax(deviceId, cmnd, retryOptions = defaultRetryOptions) {
  const url = `${config.base_url}actions?doAjax&id=${deviceId}&cmnd=${encodeURIComponent(cmnd)}`;
  let response = await fetchWithRetries(url, undefined, retryOptions);
  response = await response.json();
  if (response.hasOwnProperty("ERROR")) throw Error($.i18n("BLOCK_UPDATE_ERROR_FROM_BACKEND", response.ERROR));
  if (response.hasOwnProperty("Command") && response.Command === "Unknown") throw Error($.i18n("BLOCK_UPDATE_ERROR_FROM_BACKEND", response.Command));
  return response;
}

async function checkOtaUrlAccessible(otaUrl) {
  try {
    const response = await fetchWithRetries(otaUrl, { method: "HEAD" }, { maxRetries: 0, sleepDuration: 0 });
    return response.status === 200;
  } catch (e) {
    logGlobal($.i18n("BLOCK_UPDATE_ERROR_OTA_NOT_ACCESSIBLE", otaUrl, e), Level.error);
    return false;
  }
}

async function startOpenBekenOta(deviceId, otaUrl) {
  // Native OpenBeken OTA command. It receives the URL and starts the update in one operation.
  return doAjax(deviceId, `ota_http ${otaUrl}`, { maxRetries: 0, sleepDuration: 0 });
}

async function checkStatus(deviceId, tries = defaultTries) {
  try {
    log(deviceId, $.i18n("BLOCK_UPDATE_CHECKING_VERSION"));
    return await doAjax(deviceId, "Status 0", { maxRetries: 0, sleepDuration: 0 });
  } catch (e) {
    if (tries > 0) {
      const remainingTries = tries - 1;
      log(deviceId, $.i18n("BLOCK_UPDATE_FETCH_FAILED", remainingTries, defaultSleepDuration / 1000));
      await sleep(defaultSleepDuration);
      return checkStatus(deviceId, remainingTries);
    }
    throw e;
  }
}

function deviceSelector(deviceId) { return `device${deviceId}`; }
function logGlobal(message, level = Level.info) {
  const logLine = document.createElement("span");
  logLine.classList.add(level);
  logLine.append(message);
  document.getElementById("logGlobal").appendChild(logLine);
}
function log(deviceId, message, level = Level.info) {
  const logLine = document.createElement("span");
  logLine.classList.add(level);
  logLine.append(`[${new Date().toISOString()}] ${message}`);
  document.getElementById(deviceSelector(deviceId)).appendChild(logLine);
}
function createDeviceElement(device) {
  const deviceContainer = document.createElement("div");
  deviceContainer.setAttribute("id", deviceSelector(device.id));
  deviceContainer.classList.add("device");
  const deviceTitle = document.createElement("h1");
  deviceTitle.appendChild(document.createTextNode($.i18n("DEVICE") + ` ${device.id} (${device.name})`));
  deviceContainer.appendChild(deviceTitle);
  return deviceContainer;
}

async function updateDevice(device) {
  document.getElementById(deviceContainerId).appendChild(createDeviceElement(device));
  try {
    log(device.id, $.i18n("BLOCK_GLOBAL_START"));
    let response = await checkStatus(device.id);
    const updateTarget = resolveUpdateTarget(updateTargets, response);
    const { targetVersion } = updateTarget;
    const beforeVersion = response.StatusFWR.Version;
    log(device.id, $.i18n("BLOCK_UPDATE_CURRENT_VERSION_IS", beforeVersion));

    if (targetVersion && !config.force_upgrade && versionsEqual(targetVersion, beforeVersion)) {
      log(device.id, $.i18n("BLOCK_UPDATE_DEVICE_AT_TARGET_VERSION"), Level.success);
      return true;
    }
    if (targetVersion && config.update_newer_only && !versionUpgrade(targetVersion, beforeVersion)) {
      log(device.id, $.i18n("BLOCK_UPDATE_DEVICE_NEWER_THAN_TARGET_VERSION"), Level.success);
      return true;
    }

    const upgradePlan = determineUpgradePlan(updateTarget, response);
    if (upgradePlan.type === "blocked") {
      log(device.id, $.i18n(upgradePlan.key, ...upgradePlan.values), Level.error);
      return false;
    }

    // OpenBeken uses a single OTA image for the detected chipset; no minimal/full stage is required.
    const step = upgradePlan.steps[upgradePlan.steps.length - 1];
    if (!step || !step.otaUrl) throw Error("No compatible OpenBeken OTA firmware URL was resolved for this device.");

    if (step.targetVersion) log(device.id, $.i18n("BLOCK_UPDATE_ATTEMPT_TO_VERSION", step.targetVersion));
    log(device.id, $.i18n("BLOCK_OTAURL_SET_URL_FWURL") + step.otaUrl);
    log(device.id, $.i18n("BLOCK_UPDATE_START"));
    await startOpenBekenOta(device.id, step.otaUrl);

    log(device.id, $.i18n("BLOCK_UPDATE_SLEEPING", initialOtaWait / 1000));
    await sleep(initialOtaWait);

    let upgradeSuccessful = false;
    for (let i = 0; i < defaultTries; i++) {
      try {
        response = await doAjax(device.id, "Status 0", { maxRetries: 0, sleepDuration: 0 });
        log(device.id, $.i18n("BLOCK_UPDATE_CHECKING_VERSION"));
        if (shouldTreatStatusAsSuccessful({
          targetVersion: step.targetVersion,
          beforeVersion,
          currentVersion: response.StatusFWR.Version,
        })) {
          upgradeSuccessful = true;
          break;
        }
        log(device.id, $.i18n(step.targetVersion ? "BLOCK_UPDATE_VERSION_NOT_AT_TARGET_VERSION" : "BLOCK_UPDATE_VERSION_NOT_CHANGED"));
      } catch (e) {
        // A temporarily unreachable device is expected while OpenBeken reboots after flashing.
        log(device.id, $.i18n("BLOCK_UPDATE_FETCH_FAILED", defaultTries - i - 1, defaultSleepDuration / 1000));
      }
      if (i < defaultTries - 1) {
        log(device.id, $.i18n("BLOCK_UPDATE_SLEEPING", defaultSleepDuration / 1000));
        await sleep(defaultSleepDuration);
      }
    }

    if (!upgradeSuccessful) {
      const currentVersion = response?.StatusFWR?.Version || beforeVersion;
      const failure = getFailureDetails({ targetVersion: step.targetVersion, beforeVersion, currentVersion });
      log(device.id, $.i18n(failure.key, ...failure.values), Level.error);
      return false;
    }

    log(device.id, $.i18n("BLOCK_UPDATE_VERSION_IS", response.StatusFWR.Version));
    log(device.id, $.i18n("BLOCK_UPDATE_FINISH_SUCCESS"), Level.success);
    return true;
  } catch (e) {
    log(device.id, e.message, Level.error);
    return false;
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  await waitForI18n();
  if (config.update_fe_check) {
    const otaUrls = [...new Set(Object.values(updateTargets).map((target) => target?.otaUrl).filter(Boolean))];
    for (const otaUrl of otaUrls) if (!(await checkOtaUrlAccessible(otaUrl))) return;
  }
  const results = await Promise.all(devices.map((device) => updateDevice(device)));
  const successful = results.reduce((count, value) => (value ? count + 1 : count), 0);
  logGlobal($.i18n("BLOCK_UPDATE_RESULTS", successful, devices.length), successful === devices.length ? Level.success : Level.error);
});
