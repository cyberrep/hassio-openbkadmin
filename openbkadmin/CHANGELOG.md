# OpenBKAdmin Changelog

## [0.7.4] - 2026-08-27

### OTA reliability
- Increased the post-OTA device status interval from 10 seconds to 30 seconds.
- Keeps a maximum of 5 post-OTA status attempts per device.
- Keeps the initial 30-second reboot wait before status verification starts.

### LittleFS backup TAR
- Reworked LittleFS `.fs.tar` creation so it no longer depends on the unavailable PHP `PharData` class.
- TAR archives are now written directly in standard USTAR format from the files downloaded through OpenBeken's native `/api/lfs/` interface.
- Keeps `autoexec.bat` and the other LittleFS files in the downloadable filesystem backup.
- Adds archive finalization and size validation so an incomplete/empty TAR is not reported as a valid backup.

### Release metadata
- Add-on metadata, README and changelog synchronized to 0.7.4.

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

### Release metadata
- Add-on metadata, footer, README and changelog synchronized to 0.7.0.

## [0.6.9] - 2026-08-27

### BL602 / BL616 Web App OTA
- Fixed BL602/BL616 native OTA transport by sending the actual cached OTA image bytes to OpenBeken `POST /api/ota`.
- Validates the BL60X OTA header and the exact byte count confirmed by the device before reboot.

## [0.6.7] - 2026-08-27

### Device editor and interface
- Device heading uses `Device ID - Full Name`.
- Full Name and Short Name are separate editable OpenBeken fields.
- Multi-channel devices have a dedicated Channels section.
- Fixed the desktop actions-column stair-step caused by flex table cells.

## [0.6.6] - 2026-08-26

### Device information
- Wi-Fi percentage normalized to 0-100% and runtime display normalized.

## [0.6.5] - 2026-08-26

### Device state and BL OTA
- Corrected ON/OFF state handling using OpenBeken `Status 0` POWER/POWERx.
- Added native BL602/BL616 Web App OTA support.

## [0.6.4] - 2026-08-26

### MQTT discovery
- Aligned discovery with official OpenBeken native MQTT topics and improved Tasmota filtering.

## [0.6.3] - 2026-08-26

### MQTT discovery and Portuguese interface
- Expanded native MQTT discovery and pt-BR translations.

## [0.6.2] - 2026-08-26

### MQTT discovery and interface
- Expanded native OpenBeken MQTT detection and footer version display.

## [0.6.1] - 2026-08-26

### MQTT discovery
- Improved native MQTT discovery and removed redundant SelfUpdate navigation.

## [0.5.8] - 2026-08-26

### Firmware update
- Numeric firmware version in OTA headings and multilingual labels.

## [0.5.7] - 2026-08-26

### OTA safety backup
- Pre-OTA configuration backup and LittleFS/autoexec separation.

## [0.5.6] - 2026-08-26

### Firmware update
- Mass/Individual OTA modes, five verification attempts and `.ota` upload support.

## [0.5.5] - 2026-08-26

### Firmware update
- Full Name and firmware/chipset metadata in update selection/logs.

## [0.5.4] - 2026-08-26

### Firmware update
- Improved state handling, release caching, selected-device summary and branding.
