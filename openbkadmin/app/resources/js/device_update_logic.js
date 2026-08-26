const { compareVersions } = require("compare-versions");

const LEGACY_ESP8266_MULTI_HOP_BASELINE = "9.1.3";

function extractVersionFromResponse(response) {
  const actualMatch = /(\d+(\.\d+)+(\d+)*)/.exec(response);
  if (actualMatch === null) {
    throw Error(`Failed to match version from ${response}`);
  }

  return actualMatch[1];
}

function versionsEqual(target, actual) {
  const actualMatch = extractVersionFromResponse(actual);
  const targetMatch = extractVersionFromResponse(target);

  return targetMatch === actualMatch;
}

function versionUpgrade(target, action) {
  const actualMatch = extractVersionFromResponse(action);
  const targetMatch = extractVersionFromResponse(target);

  return compareVersions(targetMatch, actualMatch) === 1;
}

function shouldTreatStatusAsSuccessful({
  targetVersion,
  beforeVersion,
  currentVersion,
}) {
  if (targetVersion) {
    return versionsEqual(targetVersion, currentVersion);
  }

  return !versionsEqual(beforeVersion, currentVersion);
}

function getFailureDetails({ targetVersion, beforeVersion, currentVersion }) {
  if (targetVersion) {
    return {
      key: "BLOCK_UPDATE_ERROR_VERSION_COMPARE_MISMATCH",
      values: [targetVersion, currentVersion],
    };
  }

  return {
    key: "BLOCK_UPDATE_ERROR_VERSION_NOT_CHANGED",
    values: [beforeVersion],
  };
}

function detectDevicePlatform(response) {
  const hardware = String(response?.StatusFWR?.Hardware ?? "").toUpperCase();
  const version = String(response?.StatusFWR?.Version ?? "").toUpperCase();
  const combined = `${hardware} ${version}`;
  const match = combined.match(/(?:OPEN)?(BK\d+[A-Z]*|XR\d+|BL\d+|W\d+|LN\d+[A-Z]*|TR\d+|RTL\d+[A-Z0-9]*|ESP32[A-Z0-9_-]*)/);
  return match ? match[1].replace(/^OPEN/, "") : "UNKNOWN";
}

function resolveUpdateTarget(updateTargets, response) {
  const platform = detectDevicePlatform(response);
  if (updateTargets[platform]) return updateTargets[platform];
  if (updateTargets.default) return updateTargets.default;
  throw Error(`No OTA Update target configured for chipset ${platform}`);
}

function determineUpgradePlan(target, response) {
  const platform = detectDevicePlatform(response);
  const currentVersion = response?.StatusFWR?.Version ?? "";
  const minimalOtaUrl = target?.minimalOtaUrl ?? "";
  const targetVersion = target?.targetVersion ?? "";
  const source = target?.source ?? "";


  if (minimalOtaUrl) {
    return {
      type: "staged",
      steps: [
        {
          kind: "minimal",
          otaUrl: minimalOtaUrl,
          targetVersion: "",
        },
        {
          kind: "final",
          otaUrl: target.otaUrl,
          targetVersion,
        },
      ],
    };
  }

  return {
    type: "direct",
    steps: [
      {
        kind: "final",
        otaUrl: target.otaUrl,
        targetVersion,
      },
    ],
  };
}

module.exports = {
  detectDevicePlatform,
  determineUpgradePlan,
  extractVersionFromResponse,
  getFailureDetails,
  resolveUpdateTarget,
  shouldTreatStatusAsSuccessful,
  versionUpgrade,
  versionsEqual,
};
