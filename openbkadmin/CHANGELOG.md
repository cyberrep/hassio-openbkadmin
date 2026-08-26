# OpenBKAdmin Changelog

## [0.6.4] - 2026-08-26

### MQTT discovery
- Aligned discovery with the official OpenBeken native MQTT topic documentation.
- Native OpenBeken devices are identified from their base-topic publishes such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`.
- Requests `<device>/ip/get` after native discovery; OpenBeken documents VARIABLE/get with an empty payload as the query mechanism.
- Tasmota-compatible `tele/stat` remains only as a fallback and STATUS0 replies are accepted only when firmware identifies as OpenBeken, preventing real Tasmota devices from entering OpenBKAdmin discovery results.
- `MqttGroup` is not used as device identity because OpenBeken documents it as a shared command group; the per-device ShortName/base topic is the correct identity.

### Device state
- Fixed OpenBeken native channel indexing: OpenBKAdmin relay 1 maps to OpenBeken Channel0, relay 2 to Channel1, and so on.
- Initial device-list loading now also requests native `Ch` channel data, not only periodic single-device refreshes.
- Native channel values are normalized for numeric, boolean and ON/OFF representations before rendering the switch state.

## [0.6.3] - 2026-08-26

### MQTT discovery
- Discovery no longer depends on the Tasmota TELE compatibility flag.
- Uses OpenBeken native MQTT identity topics such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`.
- Subscribes to the broker wildcard and internally filters documented OpenBeken native telemetry.
- Requests `<device>/ip/get` after detecting a native OpenBeken base topic.
- Keeps Tasmota-compatible TELE/STAT discovery only as a fallback.
- Clarifies that `MqttGroup` / Group Topic is a command group and is not a reliable per-device discovery mechanism.

### Portuguese interface
- Firmware update result/summary texts are now localized instead of being hard-coded in English.
- Added pt-BR strings for selected devices, execution mode, backup status, OTA safety errors and related firmware-update messages.

### Changelog
- Added the missing 0.6.2 release entry and kept the full recent release history visible in Home Assistant.

## [0.6.2] - 2026-08-26

### MQTT discovery
- Expanded native OpenBeken detection beyond `connected` and `ip` to documented native telemetry topics.
- MQTT scan listens long enough to cover the default OpenBeken periodic broadcast interval.
- Discovery requests the device IP using the native `<device>/ip/get` topic.

### Interface
- Footer now shows the add-on version after the two project links: `OpenBKAdmin GitHub - OpenBeken GitHub - Version x.x.x`.

## [0.6.1] - 2026-08-26

### MQTT discovery
- Improved MQTT discovery for the native OpenBeken topic layout.
- Native discovery recognizes `<device-topic>/connected` and `<device-topic>/ip` topics.
- Requests `<device-topic>/ip/get` when an IP address is not yet known.
- Uses a broad MQTT subscription with internal filtering so nested topics can be discovered.
- Keeps Tasmota-compatible `tele`, `stat` and `cmnd` discovery behavior as a fallback.
- Default discovery timeout increased from 5 to 15 seconds.
- Existing `tele/+/LWT` discovery configuration is migrated to the broader native discovery mode.

### Interface
- Removed the redundant SelfUpdate entry from the main navigation for the Home Assistant add-on workflow.

## [0.5.8] - 2026-08-26

### Firmware update
- OTA device headings now show only the numeric installed firmware version while chipset remains a separate field.
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
