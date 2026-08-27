# OpenBKAdmin

<p align="center">
  <img src="https://github.com/user-attachments/assets/75a60374-1bd0-4450-86d4-d6fd79c69613" alt="OpenBKAdmin - Manage, Flash, Monitor" width="100%">
</p>

<p align="center"><strong>Centralized OpenBeken device management for Home Assistant</strong></p>

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring, configuring and managing devices running **OpenBeken** from a single web interface.

> Current add-on version: **0.6.9**

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

The editor heading uses `Device ID - Full Name`. Multi-channel devices have a dedicated Channels section rather than mixing physical-device identity with channel names.

### Device state
OpenBKAdmin reads `POWER` / `POWERx` from `Status 0`; OpenBeken itself generates these values from `CHANNEL_Get()` for relay/toggle channels. Multi-output rows use `POWER1`, `POWER2`, etc., while single-output devices use `POWER`.

The list keeps a switch for every configured row: blue/checked represents ON, gray represents OFF, and the existing red/error presentation represents a device that could not be contacted.

### Multi-channel devices
Multi-channel devices are represented as individual controllable outputs while sharing one physical device/IP. Physical status is requested once and each logical row resolves its matching POWER index.

### Network Auto Scan
Auto Scan supports configurable IP ranges and ports, two-pass probing and OpenBeken-native discovery fallback using `/obkdevicelist`.

### MQTT discovery
MQTT-assisted discovery recognizes OpenBeken native per-device topics such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`. When needed it requests `<device>/ip/get`.

Discovery does **not** require the Tasmota TELE compatibility flag. Tasmota TELE/STAT remains only as a validated fallback so real Tasmota devices are not imported. `MqttGroup` / Group Topic is treated as a shared command group, not per-device identity.

### Backup and restore
- Create/download device backups and restore OpenBeken `.dmp` backups
- Pre-OTA timestamped configuration dump for every unique selected physical device
- Backup filename includes OpenBKAdmin device ID/name
- LittleFS files such as `autoexec.bat` are separate and are not silently restored

### Firmware updates
OpenBKAdmin firmware management includes:
- Manual firmware upload and official OpenBeken GitHub release retrieval
- Automatic chipset detection and chipset-aware firmware selection
- Numeric installed/target firmware comparison and downgrade protection
- Mixed-chipset selections
- Selected-device Full Name, chipset, firmware and IP summary
- Cached release metadata to reduce GitHub API rate-limit failures
- **Mass** parallel and **Individual** sequential OTA modes
- Maximum five post-OTA status checks per device
- Automatic pre-OTA configuration backup
- **BL602 / BL616 native Web App OTA** through OpenBeken `POST /api/ota`

For BL602/BL616, OpenBKAdmin sends the actual official `OpenBL602_*_OTA.bin.xz.ota` bytes as the raw request body to the device's native `/api/ota` endpoint, matching OpenBeken's Web App/REST implementation. Version 0.6.9 prefers the firmware already downloaded into `/data/firmwares/`, avoiding a loopback HTTP request to the add-on itself. The BL60X OTA header and byte count returned by the device are validated before reboot. Other supported platforms continue using the existing `ota_http` flow.

Official firmware source: https://github.com/openshwprojects/OpenBK7231T_App/releases

### Home Assistant add-on
The repository includes Home Assistant add-on packaging, NGINX + PHP-FPM runtime, direct Web UI access, persistent configuration/device data, repository metadata and OpenBKAdmin branding assets.

### Multilingual interface
OpenBKAdmin retains the multilingual architecture. Brazilian Portuguese (pt-BR) is the reviewed Portuguese translation baseline.

## Installation in Home Assistant

Add this repository to the Home Assistant Add-on Store:

`https://github.com/cyberrep/hassio-openbkadmin`

Then open **Settings → Add-ons → Add-on Store → Repositories**, add the repository, refresh the store, select **OpenBKAdmin**, install it and start the add-on. Publishing a newer version in `openbkadmin/config.yaml` makes Home Assistant offer the normal update workflow while persistent add-on data is preserved.

## Changelog

See [`openbkadmin/CHANGELOG.md`](openbkadmin/CHANGELOG.md) for the complete release history.

### 0.6.9
- BL602/BL616 OTA now streams the cached local firmware file directly to OpenBeken `POST /api/ota`
- Avoids loopback HTTP downloads through the add-on Web UI that could produce an empty/short firmware body
- Validates `BL60X_OTA`, sent byte count and OpenBeken-confirmed written byte count before reboot
- Adds OTA source/file/size diagnostics
- Footer and release metadata synchronized to 0.6.9

### 0.6.7
- Device heading uses `Device ID - Full Name`
- Full Name and Short Name are separate editable native OpenBeken values
- Multi-channel devices have a dedicated channel list
- Channel labels use OpenBeken zero-based `SetChannelLabel` indexes
- Fixed desktop action-column stair-step caused by flex table cells

### 0.6.6
- Wi-Fi percentage normalized to 0-100%
- Runtime values such as `0T04:13:55` normalized for display
- Native device-name discovery groundwork

### 0.6.5
- Corrected ON/OFF state handling using OpenBeken `Status 0` POWER/POWERx
- Added native BL602/BL616 Web App OTA through `POST /api/ota`

### 0.6.4
- Improved native OpenBeken MQTT discovery and Tasmota filtering

### 0.6.3
- Expanded native MQTT discovery and pt-BR translations

### 0.6.2
- Expanded native OpenBeken MQTT detection and footer version display

### 0.6.1
- Improved native MQTT discovery and removed redundant SelfUpdate navigation

### 0.5.8
- Numeric firmware version in OTA headings and multilingual labels

### 0.5.7
- Pre-OTA configuration backup and LittleFS/autoexec separation

### 0.5.6
- Mass/Individual OTA modes, five verification attempts and `.ota` upload support

### 0.5.5
- Full Name and firmware/chipset metadata in update selection/logs

### 0.5.4
- Improved state handling, release caching, selected-device summary and branding

### 0.5.1
- Two-pass Auto Scan, `/obkdevicelist` fallback, OTA flow, icon and checkbox improvements

## Project status

OpenBKAdmin is under active development. OpenBeken behavior can vary between devices, chipsets, templates and firmware versions.

## Credits

OpenBKAdmin is based on the TasmotaAdmin codebase and adapted for OpenBeken.

TasmotaAdmin: https://github.com/TasmoAdmin/TasmoAdmin

OpenBeken / OpenBK7231T_App: https://github.com/openshwprojects/OpenBK7231T_App

## License

See the license files included in this repository.
