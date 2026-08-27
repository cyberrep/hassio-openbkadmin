# OpenBKAdmin Changelog

## [0.7.3] - 2026-08-27

### Official release OTA hotfix
- Fixed the fatal `Undefined constant __L::DEVICE_UPDATE_OFFICIAL_RELEASE_SELECTED` error after selecting an official OpenBeken firmware release.
- Official-release selection now carries the chosen version into the device-selection/update workflow without depending on the missing translation constant.
- Published as a new add-on version so Home Assistant can detect and install the correction normally.

### Release metadata
- Add-on metadata and README synchronized to 0.7.3.

## [0.7.2] - 2026-08-27

### Official OpenBeken releases
- Added selection of recent official OpenBeken firmware releases on the OTA page.
- Removed the obsolete chipset input from the official-release section.
- Chipset is detected and verified automatically for every selected physical device before OTA; mixed-chipset selections remain supported.
- OpenBKAdmin selects/downloads the appropriate OTA asset for each detected chipset.

### Backup integration
- Continued integration of configuration and LittleFS pre-OTA backups with the firmware update workflow.

## [0.7.1] - 2026-08-27

### Complete multilingual pass for new backup features
- Removed hard-coded Portuguese/English text introduced by the 0.7.0 backup workflow.
- Backups navigation, page title, description, table headings, download labels, delete confirmations, empty state and legacy-backup labels now follow the selected UI language.
- Pre-OTA backup summary, selected-device labels, execution mode, backup errors, chipset/firmware errors and safety-stop messages now follow the selected UI language.
- Added translations for every currently supported OpenBKAdmin UI language: Czech, German, English, Spanish, French, Hebrew, Hungarian, Italian, Dutch, Polish, Brazilian Portuguese, Russian and Traditional Chinese.
- Added a project rule in README: new user-facing OpenBKAdmin features must ship with translations for every supported UI language.

### Release metadata
- Add-on metadata, footer, README and changelog synchronized to 0.7.1.

## [0.7.0] - 2026-08-27

### Complete pre-OTA backups
- Added automatic LittleFS filesystem backup before OTA in addition to the existing OpenBeken configuration `.dmp`.
- Uses OpenBeken's native LittleFS REST interface (`GET /api/lfs/<path>`) to enumerate directories and download every filesystem file.
- Builds a downloadable `.fs.tar` archive containing `autoexec.bat` and other LittleFS files.
- Keeps configuration dump and filesystem TAR together as one timestamped backup set per physical device.
- Keeps only the two newest backup sets per device automatically.
- Backup failures are reported independently for configuration and filesystem, so one successful backup is not hidden by failure of the other.

### Backups page
- Added grouped Configuration and Filesystem downloads by device and timestamp.
- Filesystem TAR can be downloaded directly from OpenBKAdmin.
- Delete removes both files belonging to the selected backup set.
- Older/unmatched backups remain visible separately instead of being silently removed.

### Upstream implementation review
- Confirmed in OpenBeken source that LittleFS is exposed through `GET /api/lfs/<path>` and directory requests return JSON listings while file requests return raw file bytes.
- This provides the same filesystem content needed to preserve `autoexec.bat` without relying on the browser Web App.

### Release metadata
- Add-on metadata, footer, README and changelog synchronized to 0.7.0.

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
- Runtime values such as `0T04:13:55` are normalized for display.
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
