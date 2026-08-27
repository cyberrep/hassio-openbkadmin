# OpenBKAdmin

<p align="center">
  <img src="https://github.com/user-attachments/assets/75a60374-1bd0-4450-86d4-d6fd79c69613" alt="OpenBKAdmin - Manage, Flash, Monitor" width="100%">
</p>

<p align="center"><strong>Centralized OpenBeken device management for Home Assistant</strong></p>

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring, configuring and managing devices running **OpenBeken** from a single web interface.

> Current add-on version: **0.7.4**

## Features

### Device management
- Add OpenBeken devices manually by IP and port
- Auto Scan OpenBeken devices on the local network
- Edit, delete and restart registered devices
- Organize devices by position
- Open the native OpenBeken Web UI directly
- Send commands to selected devices
- Short and detailed device-list views
- Select visible columns
- Optional confirmation before switching outputs
- Include devices in **ALL OFF** and hide selected devices from the start page

### OpenBeken device information
OpenBKAdmin displays IP address, Full Name, chipset, firmware version, Wi-Fi/RSSI, runtime, channel/output names, device state and supported telemetry. Firmware strings such as `OpenBK7231N_1.18.302` are separated into chipset **BK7231N** and version **1.18.302**.

### Device names
OpenBKAdmin separates OpenBeken naming concepts in the device editor:
- **Full Name** is the physical device display name and is read/written with OpenBeken `FriendlyName`.
- **Short Name** is the short/MQTT device name and is read/written with OpenBeken `ShortName`.
- **Channel Name** is the individual output label and is written with OpenBeken `SetChannelLabel`.

### Device state
OpenBKAdmin reads `POWER` / `POWERx` from `Status 0`. Multi-output rows use their matching POWER index while single-output devices use `POWER`.

The list keeps a switch for every configured row: blue/checked represents ON, gray represents OFF, and red/error represents a device that could not be contacted.

### Network Auto Scan
Auto Scan supports configurable IP ranges and ports, two-pass probing and OpenBeken-native discovery fallback using `/obkdevicelist`.

### MQTT discovery
MQTT-assisted discovery recognizes OpenBeken native per-device topics such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`. Tasmota-compatible discovery remains only as a validated fallback.

### Backup and restore
- Create/download device configuration backups and restore OpenBeken `.dmp` backups
- Automatic pre-OTA timestamped backup for every unique selected physical device
- **Configuration backup (`.dmp`)** preserves the OpenBeken configuration dump
- **Filesystem backup (`.fs.tar`)** preserves the complete LittleFS contents, including `autoexec.bat`
- Uses OpenBeken's native `/api/lfs/` REST filesystem interface
- Filesystem TAR archives are generated directly in standard USTAR format and do not depend on PHP `PharData`
- Backups page groups Configuration and Filesystem files by device and timestamp
- Automatically retains the **2 newest backup sets per device**

### Firmware updates
OpenBKAdmin firmware management includes:
- Manual firmware upload and official OpenBeken GitHub release retrieval
- Selection from recent official OpenBeken releases
- Automatic chipset detection and chipset-aware firmware selection
- Mixed-chipset selections
- **Mass** parallel and **Individual** sequential OTA modes
- Initial 30-second reboot wait after triggering OTA
- Maximum **5 post-OTA status checks**, with **30 seconds between attempts**
- Automatic pre-OTA configuration + LittleFS backup
- **BL602 / BL616 native Web App OTA** through OpenBeken `POST /api/ota`

For BL602/BL616, OpenBKAdmin sends the actual official OTA bytes as the raw request body to the device's native `/api/ota` endpoint. Other supported platforms continue using the existing `ota_http` flow.

Official firmware source: https://github.com/openshwprojects/OpenBK7231T_App/releases

### Home Assistant add-on
The repository includes Home Assistant add-on packaging, NGINX + PHP-FPM runtime, direct Web UI access, persistent configuration/device data, repository metadata and OpenBKAdmin branding assets.

### Multilingual interface
New OpenBKAdmin user-facing features must provide translations for every supported interface language. Brazilian Portuguese (pt-BR) remains the reviewed Portuguese translation baseline.

## Installation in Home Assistant

Add this repository to the Home Assistant Add-on Store:

`https://github.com/cyberrep/hassio-openbkadmin`

Then open **Settings → Add-ons → Add-on Store → Repositories**, add the repository, refresh the store, select **OpenBKAdmin**, install it and start the add-on.

## Changelog

See [`openbkadmin/CHANGELOG.md`](openbkadmin/CHANGELOG.md) for the complete release history.

### 0.7.4
- Post-OTA verification remains limited to 5 attempts but now waits 30 seconds between attempts
- Reworked LittleFS `.fs.tar` generation without `PharData`
- Filesystem backups are written as standard USTAR archives and include `autoexec.bat` and other LittleFS files
- Added TAR finalization/size validation so incomplete archives are rejected
- Release metadata synchronized for Home Assistant update detection

### 0.7.3
- Fixed the fatal error after choosing an official OpenBeken release
- Official-release selection now proceeds normally to device selection

### 0.7.2
- Added recent official OpenBeken release selection to the OTA screen
- Chipset is detected and verified automatically for each selected physical device

### 0.7.1
- Backup UI and pre-OTA messages translated for all supported languages

### 0.7.0
- Complete pre-OTA backup saves configuration (`.dmp`) and LittleFS filesystem (`.fs.tar`)
- New Backups page and two-backup retention per device

### 0.6.9
- Fixed BL602/BL616 native OTA using the cached firmware bytes directly

### 0.6.7
- Improved device names/channels editor and fixed desktop actions-column CSS

### 0.6.6
- Wi-Fi and runtime normalization

### 0.6.5
- Corrected POWER/POWERx state handling and added native BL602/BL616 OTA

### 0.6.4
- Improved native OpenBeken MQTT discovery and Tasmota filtering

### 0.6.3
- Expanded native MQTT discovery and pt-BR translations

### 0.6.2
- Expanded MQTT detection and footer version display

### 0.6.1
- Improved MQTT discovery and removed redundant SelfUpdate navigation

### 0.5.8
- Numeric firmware version in OTA headings

### 0.5.7
- Pre-OTA configuration backup

### 0.5.6
- Mass/Individual OTA modes and five verification attempts

### 0.5.5
- Full Name and firmware/chipset metadata in OTA selection

### 0.5.4
- State, release caching, selected-device summary and branding improvements

## Project status

OpenBKAdmin is under active development. OpenBeken behavior can vary between devices, chipsets, templates and firmware versions.

## Credits

OpenBKAdmin is based on the TasmotaAdmin codebase and adapted for OpenBeken.

TasmotaAdmin: https://github.com/TasmoAdmin/TasmoAdmin

OpenBeken / OpenBK7231T_App: https://github.com/openshwprojects/OpenBK7231T_App

## License

See the license files included in this repository.
