# OpenBKAdmin Changelog

## [0.5.8] - 2026-08-26

### Firmware update
- OTA device headings now show only the numeric installed firmware version (for example `1.18.302`) while chipset remains a separate field.
- OTA heading labels reuse the existing localization keys so Device, Chipset and Version follow the selected interface language.
- Portuguese localization remains based on the reviewed pt-BR translation set.

## [0.5.7] - 2026-08-26

### Device state
- Device-list ON/OFF state now prefers OpenBeken's native `Ch` channel data instead of the Tasmota-compatible `Status 0` POWER field.
- Native channel values are merged with the regular status payload, preserving Wi-Fi, chipset, firmware and runtime information while using the real OpenBeken output value for the switch.
- Multi-channel rows continue sharing physical-device metadata while resolving their own channel state.

### OTA safety backup
- Before an OTA starts, OpenBKAdmin creates a timestamped OpenBeken configuration dump for each unique selected physical device.
- Backup filenames include the device ID and device name and are kept under the add-on data directory.
- The backup uses the existing OpenBeken `/dl` configuration export and is compatible with OpenBKAdmin's existing `WebGetConfig` restore mechanism.
- LittleFS is explicitly treated separately: `autoexec.bat` is not assumed to be contained in the configuration dump and is never silently restored after OTA.

### Firmware update
- Keeps both Mass and Individual update modes introduced in 0.5.6.
- Keeps the five-attempt post-OTA status limit.

## [0.5.6] - 2026-08-26

### Firmware update
- Added selectable Mass (parallel) and Individual (sequential) OTA execution modes.
- Reduced post-OTA status verification to a maximum of five attempts per device.
- Local firmware upload accepts OpenBeken `.ota` images in addition to existing firmware formats.

## [0.5.5] - 2026-08-26

### Firmware update
- Update selection and logs prefer the native OpenBeken Full Name and show current firmware/chipset metadata.
- Multi-device OTA execution was made safer and easier to diagnose.

## [0.5.4] - 2026-08-26

### Device state
- Multi-channel OpenBeken devices now use channel-aware state instead of blindly sharing one Tasmota-compatible POWER value.

### Firmware update
- Official firmware is queried after device selection and required chipset OTA assets are resolved together.
- Added release metadata caching to reduce GitHub API rate-limit failures.
- Selected-device summary shows Full Name, detected chipset and IP.

### Branding
- Updated navigation/favicons for OpenBKAdmin branding.

## [0.5.3] - 2026-08-26

### Firmware update safety
- Official Release mode detects each selected physical device chipset automatically.
- Mixed-chipset selections resolve the correct official OTA image independently.
- Automatic flashing is blocked when chipset/OTA target cannot be resolved safely.

### Device identity
- Firmware update logs prefer the live OpenBeken Full Name.

## [0.5.1] - 2026-08-26

### Firmware update
- Verified the OpenBeken OTA flow and chipset-aware official OTA selection.

### Device discovery
- Improved Auto Scan with two-pass probing and `/obkdevicelist` fallback.

### Interface and branding
- Round OpenBKAdmin navigation icon and centered device-selection checkboxes.

## [0.5.0] - 2026-08-26

- Fixed device-selection handoff to firmware update.
- Simplified firmware UI to OpenBeken-oriented Official Release and Local Firmware workflows.
- OTA server defaults to the reachable Home Assistant/OpenBKAdmin host.

## [0.4.9] - 2026-08-26

- Replaced obsolete OTA source with official OpenBeken GitHub Releases.
- Added chipset-aware OTA selection, version comparison and downgrade protection.
- Improved GitHub/network error handling and OpenBeken-specific terminology.

## [0.4.8]

- Introduced dedicated OpenBKAdmin branding.
- Brazilian Portuguese translation extensively reviewed.
- Home Assistant packaging improvements.

## [0.4.x]

Major OpenBeken-focused refactoring: OpenBKAdmin branding, OpenBeken detection, Short/Full Name support, multi-channel handling, chipset/firmware information, Auto Scan, MQTT discovery, backup/restore, help links, localization and Home Assistant add-on fixes.

## [0.3.x]

Foundation work for the Home Assistant/OpenBeken adaptation.

---

OpenBKAdmin is based on the **TasmotaAdmin** codebase: https://github.com/TasmoAdmin/TasmoAdmin

OpenBeken / OpenBK7231T_App: https://github.com/openshwprojects/OpenBK7231T_App
