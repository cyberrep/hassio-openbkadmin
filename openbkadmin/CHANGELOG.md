# OpenBKAdmin Changelog

## [0.6.1] - 2026-08-26

### MQTT discovery
- Improved MQTT discovery for the native OpenBeken topic layout.
- Native discovery recognizes `<device-topic>/connected` and `<device-topic>/ip` topics.
- Requests `<device-topic>/ip/get` when an IP address is not yet known.
- Uses a broad MQTT subscription with internal filtering so nested topics such as `openbeken/luz_cozinha` can be discovered.
- Keeps Tasmota-compatible `tele`, `stat` and `cmnd` discovery behavior as a fallback.
- Default discovery timeout increased from 5 to 15 seconds.
- Existing `tele/+/LWT` discovery configuration is migrated to the broader native discovery mode.

### Interface
- Removed the redundant SelfUpdate entry from the main navigation for the Home Assistant add-on workflow.

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

### Firmware update
- Improved channel-aware state handling.
- Reduced OpenBeken GitHub release lookups with caching.
- Added selected-device summary before OTA.
- Improved OpenBKAdmin branding/favicon handling.
