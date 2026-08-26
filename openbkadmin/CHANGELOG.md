# OpenBKAdmin Changelog

## [0.5.4] - 2026-08-26

### Device state
- Multi-channel OpenBeken devices now read their native channel values with `GetChannel` instead of trusting the shared Tasmota-compatible `Status 0` POWER state for every logical output.
- Physical-device status is still shared by rows with the same IP; only the individual channel state is queried separately when required.

### Firmware update
- Official firmware is no longer queried from GitHub before the user selects devices.
- The selected physical devices are checked first, their chipsets are detected, and all required OTA assets are resolved from a single OpenBeken release metadata lookup.
- Added a 15-minute release metadata cache to avoid repeated GitHub API requests and unauthenticated rate-limit errors.
- A previously successful cached release can be reused temporarily if GitHub returns a rate-limit/network error.
- The selected-device list is displayed before OTA execution, including Full Name, detected chipset and IP for automatic updates.
- Mixed-chipset selections continue to resolve the correct official OTA Update image independently for each chipset.

### Branding
- Updated the navigation logo wrapper to render the OpenBKAdmin icon directly and avoid the previously blank logo area.
- NGINX now serves the OpenBKAdmin icon through the existing favicon paths and disables caching of the old favicon.

## [0.5.3] - 2026-08-26

### Firmware update safety
- Official Release mode now presents chipset selection as automatic instead of asking the user to choose a chipset manually.
- Before flashing, OpenBKAdmin re-reads `Status 0` from every selected physical device and detects its chipset from the live OpenBeken firmware/hardware information.
- Mixed-chipset selections are supported: the correct official OTA Update asset is resolved and downloaded independently for each detected chipset.
- The update is stopped before flashing if a selected device chipset cannot be identified.
- Added a confirmation summary showing the selected device Full Name, detected chipset and IP before the OTA execution log.
- Existing newer-only/version checks remain active for each device independently.

### Device identity
- Firmware update logs prefer the live OpenBeken Full Name instead of MQTT/short/channel names.

## [0.5.1] - 2026-08-26

### Firmware update
- Verified the OpenBeken OTA command flow against the upstream OpenBeken command implementation: set `OtaUrl` to the selected chipset OTA image, then trigger `Upgrade 1`.
- Keeps automatic firmware selection chipset-aware and continues using only the official **OTA Update** asset published in `openshwprojects/OpenBK7231T_App` releases.
- Multi-device updates continue resolving the target independently for each physical device/chipset.

### Device discovery
- Improved Network Auto Scan reliability with a two-pass TCP probe instead of relying on a single very short connection attempt.
- Added an OpenBeken-native discovery fallback using `/obkdevicelist`, the HTTP list provided by the firmware SSDP `obkDeviceList` implementation.
- Peers returned by OpenBeken are validated as real OpenBeken devices before being shown by OpenBKAdmin.
- Existing configured devices remain excluded from the add-device results.

### Interface and branding
- Changed the navigation branding to the round OpenBKAdmin icon style.
- Centered the device-selection checkboxes in both the header and device rows.

## [0.5.0] - 2026-08-26

### Firmware update
- Fixed the device-selection handoff between the firmware selection table and the update execution page. The shared table posts `device_ids[]`, which is now accepted by the updater.
- Simplified the OpenBeken firmware screen: removed the obsolete Minimal/Full firmware presentation and exposed a single local firmware file workflow.
- Added a clearer Official OpenBeken Release workflow with chipset selection and a dedicated **Use Official Release** action.
- Official automatic firmware continues to come exclusively from `openshwprojects/OpenBK7231T_App` GitHub Releases and uses the OTA Update asset for the selected chipset.
- OTA server address now defaults to the Home Assistant/OpenBKAdmin host used by the browser when no valid configured address is available, avoiding internal add-on/container addresses such as `172.30.x.x`.
- OTA server port defaults to the exposed OpenBKAdmin web port.
- Improved firmware-update button labels and layout.

### Interface
- Firmware update modes are now visually separated into OTA Server, Official OpenBeken Release, and Local Firmware sections.
- Reduced legacy Tasmota-style firmware terminology in the OpenBeken updater.

## [0.4.9] - 2026-08-26

### Firmware update
- Replaced the obsolete `ota.openbeken.com` firmware source with the official OpenBeken GitHub Releases source.
- Firmware metadata is obtained from `openshwprojects/OpenBK7231T_App` releases.
- Automatic firmware selection is now chipset-aware.
- Only the asset documented as **OTA Update** for the matching chipset is selected for automatic flashing.
- Added installed-versus-available firmware version comparison.
- Normal firmware update is offered only when the official OTA firmware version is newer than the version installed on the device.
- Prevents automatic downgrade when the installed device firmware is newer than the current official release.
- Removed the legacy ESP8266/ESP32 Tasmota-style automatic firmware split from the active OpenBeken updater.
- Restored the firmware update execution page and chipset-aware target resolution.
- GitHub/DNS/network failures are handled without dumping the external scraper stack trace into the user interface.

### Device handling
- Continued OpenBeken-specific cleanup of physical-device and multi-channel handling.
- Device information shared by channels on the same physical IP is intended to be reused instead of repeatedly querying identical hardware information for every output.

### Interface and localization
- Continued cleanup of legacy Tasmota-facing terminology in OpenBeken workflows.
- OpenBeken-specific UI additions continue to use the multilingual translation structure.
- Brazilian Portuguese remains the reviewed reference translation for the OpenBKAdmin-specific interface additions.

### Documentation
- Expanded README with current OpenBKAdmin functionality.
- Documented official OpenBeken OTA firmware behavior and update rules.
- Corrected changelog path/documentation.
- Added explicit upstream credit to **TasmotaAdmin**, whose codebase is the foundation on which OpenBKAdmin was built.
- Retained OpenBeken/OpenBK7231T_App project credit and official documentation links.

## [0.4.8]

### Branding
- Introduced the dedicated OpenBKAdmin visual identity.
- Rebuilt the Home Assistant icon from the dedicated square OpenBKAdmin artwork.
- Added the full OpenBKAdmin branding/logo artwork.
- Updated the internal OpenBKAdmin icon asset.

### Localization
- Brazilian Portuguese translation extensively reviewed and corrected for OpenBKAdmin/OpenBeken terminology.
- Continued integration of newly added interface text with the multilingual translation system.

### Packaging
- General Home Assistant add-on repository and packaging improvements.

## [0.4.x]

Major OpenBeken-focused refactoring and feature development:
- OpenBKAdmin naming and branding.
- Adaptation of the TasmotaAdmin foundation to OpenBeken devices.
- OpenBeken-specific device detection and information.
- Short Name and Full Name support.
- Multi-channel device handling and channel-aware names.
- Chipset and firmware-version separation.
- Improved device details and configuration workflow.
- Network Auto Scan.
- MQTT-assisted discovery.
- Backup and restore workflow.
- OpenBeken documentation, Commands, Templates, FAQ and Forum help links.
- Translation-system expansion for OpenBKAdmin-specific strings.
- Home Assistant add-on build/startup fixes.

## [0.3.x]

Foundation work for the Home Assistant/OpenBeken adaptation:
- Home Assistant add-on runtime.
- OpenBeken device detection.
- Device list and control.
- Initial Auto Scan implementation.
- Device naming improvements.
- Early multi-channel handling.
- Initial OpenBeken configuration integration.

---

OpenBKAdmin is based on the **TasmotaAdmin** codebase: https://github.com/TasmoAdmin/TasmoAdmin

OpenBeken / OpenBK7231T_App: https://github.com/openshwprojects/OpenBK7231T_App
