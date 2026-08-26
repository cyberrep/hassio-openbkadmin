## 0.4.8

- Rebuilt Home Assistant icon from the dedicated square OpenBKAdmin artwork.
- Added full OpenBKAdmin branding as the add-on logo.
- Updated internal OpenBKAdmin icon asset.
## [0.4.9] - 2026-08-26

### Changed
- Replaced the obsolete `ota.openbeken.com` firmware source with the official OpenBeken GitHub Releases page/API.
- Automatic firmware selection is now chipset-aware and uses only the asset documented as **OTA Update** in each OpenBeken release.
- Removed the legacy ESP8266/ESP32 Tasmota-style automatic firmware split from the active OpenBeken updater.
- Restored the firmware update execution page and chipset-aware target resolution.
- GitHub/DNS failures are handled without dumping the external scraper stack trace into the settings page.

### Fixed
- Firmware update page no longer depends on the unavailable `ota.openbeken.com` host.
- Update source now points to `openshwprojects/OpenBK7231T_App/releases`.


