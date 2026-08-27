import statusHelpers from "./status_helpers";

const { normalizeStatusData } = statusHelpers;

class OpenBeken {
  constructor(options) {
    this.options = { timeout: 10 };
    $.extend(this.options, options);
  }

  getStatus(ip, id, callback) {
    // OpenBeken generates POWER/POWERx directly from its real relay/toggle
    // channels in the Tasmota-compatible Status 0 JSON. This is more reliable
    // than issuing `Ch` without an argument (which is a SET command in OBK).
    this._doAjax(ip, id, "Status 0", callback);
  }

  getAllStatus(timeout, callback) {
    this._doAjaxAll(timeout, "Status 0", callback);
  }

  updateConfig(device_id, cmnd, newvalue, callback) {
    this._doAjax(null, device_id, cmnd + " " + newvalue, callback);
  }

  generic(device_id, cmnd, newvalue, callback) {
    const value = newvalue !== undefined ? " " + newvalue : "";
    this._doAjax(null, device_id, cmnd + value, callback);
  }

  toggle(ip, id, relais, callback) {
    relais = relais || 1;
    const cmnd = "Power" + relais + " toggle";
    console.log("[OpenBeken][toggle][" + ip + "][Relais" + relais + "] cmnd => " + cmnd);
    this._doAjax(ip, id, cmnd, callback);
  }

  off(ip, id, relais, callback) {
    relais = relais || 1;
    const cmnd = "Power" + relais + " 0";
    console.log("[OpenBeken][toggle][" + ip + "][Relais" + relais + "] cmnd => " + cmnd);
    this._doAjax(ip, id, cmnd, callback);
  }

  _doAjax(ip, id, cmnd, callback) {
    ip = ip || id;
    $.ajax({
      dataType: "json",
      url: `${this.options.base_url}actions?doAjax`,
      timeout: this.options.timeout * 1000,
      cache: false,
      type: "post",
      async: true,
      data: { id: id, cmnd: encodeURIComponent(cmnd) },
      success: function (data) {
        console.log("[OpenBeken][doAjax][" + ip + "] Got response from: " + cmnd);
        if (data.WARNING) alert(ip + ": " + data.WARNING);
        callback(data);
      },
      error: function (data) { callback(data); },
    });
  }

  _doAjaxAll(timeout, cmnd, callback) {
    timeout = timeout || this.options.timeout;
    $.ajax({
      dataType: "json",
      url: `${this.options.base_url}actions?doAjaxAll`,
      timeout: timeout * 1000,
      cache: false,
      type: "post",
      data: { cmnd: encodeURIComponent(cmnd) },
      success: function (data) { callback(data); },
      error: function (data) { callback(data); },
    });
  }

  parseDeviceStatus(data, device_relais) {
    let device_status = "NONE";

    // OpenBeken's own Tasmota JSON implementation builds POWER/POWERx from
    // CHANNEL_Get() for relay/toggle channels. For devices without relays OBK
    // intentionally reports POWER=ON, which keeps meters/sensors shown online.
    if (data && data.StatusSTS !== undefined) {
      if (
        device_relais !== undefined &&
        data.StatusSTS[`POWER${device_relais}`] !== undefined
      ) {
        const value = data.StatusSTS[`POWER${device_relais}`];
        device_status = value && value.STATE !== undefined ? value.STATE : value;
      } else if (data.StatusSTS.POWER !== undefined) {
        const value = data.StatusSTS.POWER;
        device_status = value && value.STATE !== undefined ? value.STATE : value;
      }
    } else if (
      data &&
      device_relais !== undefined &&
      data[`POWER${device_relais}`] !== undefined
    ) {
      const value = data[`POWER${device_relais}`];
      device_status = value && value.STATE !== undefined ? value.STATE : value;
    } else if (data && data.POWER !== undefined) {
      const value = data.POWER;
      device_status = value && value.STATE !== undefined ? value.STATE : value;
    }

    if (typeof device_status === "boolean") return device_status ? "ON" : "OFF";
    if (typeof device_status === "number") return device_status !== 0 ? "ON" : "OFF";
    const normalized = String(device_status).trim().toUpperCase();
    if (normalized === "1" || normalized === "TRUE" || normalized === "ON") return "ON";
    if (normalized === "0" || normalized === "FALSE" || normalized === "OFF") return "OFF";
    return normalized || "NONE";
  }

  parseDeviceHostname(data) {
    if (data.StatusNET !== undefined && data.StatusNET.Hostname !== undefined) return data.StatusNET.Hostname;
    return false;
  }

  directAjax(url) {
    $.ajax({ url: url, timeout: this.options.timeout * 1000, cache: false });
  }

  setDeviceValue(id, field, newvalue, td) {
    $.ajax({
      dataType: "json",
      url: `${this.options.base_url}actions?doAjax`,
      timeout: this.options.timeout * 1000,
      cache: false,
      type: "post",
      data: { id: id, field: encodeURIComponent(field), newvalue: encodeURIComponent(newvalue), target: "csv" },
      success: function (data) { td.html(data.position); },
      error: function () { console.log("ERROR setDeviceValue"); },
    });
  }

  parseStatusData(data) { return normalizeStatusData(data); }

  _parseVersion(versionString) {
    versionString = versionString.replace("-minimal", "").replace(/\./g, "");
    const last = versionString.slice(-1);
    if (isNaN(last)) {
      versionString = versionString.replace(last, last.charCodeAt(0) - 97 < 10 ? "0" + (last.charCodeAt(0) - 97) : last.charCodeAt(0) - 97);
    } else {
      versionString = versionString + "00";
    }
    return versionString;
  }
}

export { OpenBeken };
