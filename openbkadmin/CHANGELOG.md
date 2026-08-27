# OpenBKAdmin Changelog

## [0.6.9] - 2026-08-27

### BL602 / BL616 Web App OTA
- Fixed the BL602/BL616 native OTA transport after reproducing the `Firmware file is too small or invalid` failure.
- OpenBKAdmin now reads firmware already downloaded by `FirmwareDownloader` directly from persistent `/data/firmwares/` instead of making an HTTP request back to its own public add-on URL.
- This mirrors the OpenBeken Web App behavior: the actual `OpenBL602_*_OTA.bin.xz.ota` bytes are sent as the raw body of `POST /api/ota`.
- Keeps the official `BL60X_OTA` header validation before contacting the device.
- Keeps `Expect: 100-Continue` disabled and sends an explicit `Content-Length` for the embedded OpenBeken HTTP server.
- Requires the device JSON response to confirm exactly the same number of bytes written before reboot is requested.
- OTA diagnostics now report whether firmware came from the local cache or remote fallback, filename, sent size and confirmed written size.

### Release metadata
- Add-on metadata, footer, README and changelog synchronized to 0.6.9.

## [0.6.7] - 2026-08-27

### BL602 / BL616 OTA hotfix
- Reimplemented the native Web App OTA proxy after testing showed BL602 remained on the old firmware.
- Sends the official `OpenBL602_*_OTA.bin.xz.ota` file as the raw body of `POST /api/ota`, matching OpenBeken's REST implementation.
- Disables HTTP `Expect: 100-Continue` for compatibility with OpenBeken's embedded HTTP server.
- Validates the BL602 `BL60X_OTA` header before touching the device.
- Requires OpenBeken to confirm the exact number of bytes written before reporting upload success.
- Calls native `POST /api/reboot` only after the complete OTA image has been confirmed, because the BL602 OTA writer updates the boot partition table but does not itself invoke the reboot routine.
- OTA log now shows the confirmed byte count and reboot request, making transport failures visible immediately instead of waiting for five version checks.

### Device editor
- Device heading now shows `Device ID - Full Name` with a stored-name fallback when the live native name is unavailable.
- Full Name and Short Name are separate editable OpenBeken fields.
- Saving Full Name uses OpenBeken `FriendlyName`; saving Short Name uses `ShortName`.
- Multi-channel devices now have a dedicated Channels section instead of mixing the device name with channel names.
- Channel labels use OpenBeken `SetChannelLabel` with zero-based channel indexes, matching the upstream implementation.
- Channel values are kept separate from the physical device Full Name and Short Name.

### Interface
- Fixed the desktop actions-column stair-step caused by applying `display:flex` directly to table cells.
- Footer version updated to 0.6.7.

### Release metadata
- README, add-on metadata and changelog are synchronized at 0.6.7.

## [0.6.6] - 2026-08-26

### Device information
- Wi-Fi percentage is normalized and clamped to 0-100%, including OpenBeken targets that report RSSI as dBm.
- Runtime values such as `0T04:13:55` are normalized to the normal OpenBKAdmin duration format.
- Runtime falls back to OpenBeken uptime when a valid startup timestamp is not available.

### Device names
- Added native OpenBeken name discovery groundwork for Full Name, Short Name and channel labels.
- Prepared the device editor for clearer separation of physical-device identity and channel names.

## [0.6.5] - 2026-08-26

### Device state
- Corrected the device-list state source after reviewing the OpenBeken implementation itself.
- Removed the invalid use of `Ch` without arguments. In OpenBeken, `ChN value` is a channel SET command, not a read-all status command.
- ON/OFF now uses OpenBeken's `Status 0` POWER/POWERx values, which upstream generates directly from `CHANNEL_Get()` for relay/toggle channels.
- Multi-output rows use their matching `POWER1`, `POWER2`, etc. value; single-output devices use `POWER`.
- Devices without relay channels keep OpenBeken's own POWER behavior instead of being forced OFF.
- The switch remains visible for every configured row; communication failures continue to use the existing red/error state.

### BL602 / BL616 OTA
- Added native Web App OTA support for BL602 and BL616.
- These platforms now use OpenBeken's official REST upload endpoint `POST /api/ota` instead of `ota_http`.
- OpenBKAdmin downloads the selected official/local OTA image server-side and streams it to the device, avoiding browser CORS restrictions.
- Keeps the existing pre-OTA configuration backup and maximum five post-update status checks.
- BK7231/BK7238 and other supported platforms continue using the existing `ota_http` flow.

### Documentation
- README and add-on metadata updated to version 0.6.5.
- Documented the corrected state source and native BL602/BL616 Web App OTA path.

## [0.6.4] - 2026-08-26

### MQTT discovery
- Aligned discovery with the official OpenBeken native MQTT topic documentation.
- Native OpenBeken devices are identified from their base-topic publishes such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`.
- Requests `<device>/ip/get` after native discovery.
- Tasmota-compatible `tele/stat` remains only as a fallback and STATUS0 replies are accepted only when firmware identifies as OpenBeken.
- `MqttGroup` is treated as a shared command group, not device identity.

## [0.6.3] - 2026-08-26

### MQTT discovery
- Discovery no longer depends on the Tasmota TELE compatibility flag.
- Uses OpenBeken native MQTT identity/telemetry topics and requests `<device>/ip/get` when needed.
- Keeps Tasmota-compatible TELE/STAT discovery only as a fallback.

### Portuguese interface
- Firmware update result/summary texts localized instead of hard-coded in English.
- Added pt-BR strings for selected devices, execution mode, backup status and OTA messages.

## [0.6.2] - 2026-08-26

### MQTT discovery
- Expanded native OpenBeken detection beyond `connected` and `ip` to documented native telemetry topics.
- MQTT scan listens long enough to cover the default OpenBeken periodic broadcast interval.
- Discovery requests the device IP using `<device>/ip/get`.

### Interface
- Footer shows `OpenBKAdmin GitHub - OpenBeken GitHub - Version x.x.x`.

## [0.6.1] - 2026-08-26

### MQTT discovery
- Improved MQTT discovery for the native OpenBeken topic layout.
- Broad MQTT subscription with internal filtering and native IP requests.
- Default discovery timeout increased from 5 to 15 seconds.

### Interface
- Removed the redundant SelfUpdate entry from the main navigation for the Home Assistant add-on workflow.

## [0.5.8] - 2026-08-26

### Firmware update
- OTA device headings show only the numeric installed firmware version while chipset remains separate.
- OTA heading labels reuse multilingual translation keys.

## [0.5.7] - 2026-08-26

### OTA safety backup
- Pre-OTA timestamped OpenBeken configuration dump for each unique selected physical device.
- Backup filenames include device ID/name.
- LittleFS/`autoexec.bat` remains separate and is never silently restored.

### Firmware update
- Keeps Mass and Individual update modes and five post-OTA checks.

## [0.5.6] - 2026-08-26

### Firmware update
- Added selectable Mass (parallel) and Individual (sequential) OTA execution modes.
- Reduced post-OTA status verification to five attempts.
- Local firmware upload accepts OpenBeken `.ota` images.

## [0.5.5] - 2026-08-26

### Firmware update
- Update selection/logs prefer native OpenBeken Full Name and show current firmware/chipset metadata.
- Improved multi-device OTA diagnostics.

## [0.5.4] - 2026-08-26

### Firmware update
- Improved channel-aware state handling.
- Reduced OpenBeken GitHub release lookups with caching.
- Added selected-device summary before OTA.
- Improved OpenBKAdmin branding/favicon handling.
