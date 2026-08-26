import statusHelpers from "./status_helpers";

const { normalizeStatusData } = statusHelpers;

class OpenBeken {
  constructor(options) {
    this.options = { timeout: 10 };
    $.extend(this.options, options);
  }

  getStatus(ip, id, callback) {
    // Status 0 is only the Tasmota-compatible status layer. OpenBeken's native
    // channel values are authoritative for relay state, so enrich every status
    // request with the native `Ch` command.
    this._doAjax(ip, id, "Status 0", (status) => {
      if (!status || status.ERROR || status.WARNING) {
        callback(status);
        return;
      }
      this._doAjax(ip, id, "Ch", (channels) => {
        if (channels && !channels.ERROR && !channels.WARNING) {
          status.OpenBKChannels = channels;
        }
        callback(status);
      });
    });
  }

  getAllStatus(timeout, callback) {
    // Keep the fast bulk Status 0 request for the list, but enrich every online
    // OpenBeken device with its native channel values before rendering. Without
    // this, the initial page load can show the compatibility POWER state instead
    // of the real OpenBeken channel state.
    this._doAjaxAll(timeout, "Status 0", (result) => {
      if (!result || typeof result !== "object") {
        callback(result);
        return;
      }

      const ids = Object.keys(result).filter((id) => {
        const status = result[id];
        return status && !status.ERROR && !status.WARNING && status.statusText === undefined;
      });

      if (ids.length === 0) {
        callback(result);
        return;
      }

      let pending = ids.length;
      ids.forEach((id) => {
        this._doAjax(null, id, "Ch", (channels) => {
          if (channels && !channels.ERROR && !channels.WARNING && result[id]) {
            result[id].OpenBKChannels = channels;
          }
          pending -= 1;
          if (pending === 0) callback(result);
        });
      });
    });
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
    // OpenBeken channels are zero-based (Channel0 is the first channel), while
    // OpenBKAdmin's relay rows are one-based (relay 1 is the first row). Prefer
    // relay-1 first. The previous order checked Channel1 before Channel0 for the
    // first relay and could therefore display another channel's state.
    if (data && data.OpenBKChannels) {
      const channels = data.OpenBKChannels;
      const relay = Number.parseInt(device_relais, 10);
      const candidates = [];
      if (!Number.isNaN(relay)) {
        if (relay > 0) candidates.push(`Channel${relay - 1}`, `Ch${relay - 1}`);
        candidates.push(`Channel${relay}`, `Ch${relay}`);
      }
      for (const key of candidates) {
        if (Object.prototype.hasOwnProperty.call(channels, key)) {
          const raw = channels[key] && channels[key].STATE !== undefined ? channels[key].STATE : channels[key];
          const numeric = Number.parseFloat(raw);
          if (!Number.isNaN(numeric)) return numeric !== 0 ? "ON" : "OFF";
          const text = String(raw).trim().toUpperCase();
          if (text === "ON" || text === "OFF") return text;
        }
      }
    }

    let device_status = "NONE";
    if (data.StatusSTS !== undefined) {
      if (device_relais !== undefined && data.StatusSTS[`POWER${device_relais}`] !== undefined) {
        const value = data.StatusSTS[`POWER${device_relais}`];
        device_status = value && value.STATE !== undefined ? value.STATE : value;
      } else if (data.StatusSTS.POWER !== undefined) {
        const value = data.StatusSTS.POWER;
        device_status = value && value.STATE !== undefined ? value.STATE : value;
      }
    } else if (device_relais !== undefined && data[`POWER${device_relais}`] !== undefined) {
      const value = data[`POWER${device_relais}`];
      device_status = value && value.STATE !== undefined ? value.STATE : value;
    } else if (data.POWER !== undefined) {
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
