# OpenBKAdmin Changelog

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
