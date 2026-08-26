# OpenBKAdmin

<p align="center">
  <img src="https://github.com/user-attachments/assets/75a60374-1bd0-4450-86d4-d6fd79c69613" alt="OpenBKAdmin - Manage, Flash, Monitor" width="100%">
</p>

<p align="center"><strong>Centralized OpenBeken device management for Home Assistant</strong></p>

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring, configuring and managing devices running **OpenBeken** from a single web interface.

> Current add-on version: **0.5.4**

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
- Protection options for switching on/off
- Include devices in **ALL OFF**
- Hide selected devices from the start page

### OpenBeken device information
OpenBKAdmin can display IP address, Short Name, Full Name, chipset, firmware version, Wi-Fi/RSSI, runtime, channel/output names, device state and supported telemetry.

Firmware identification is separated into useful fields. For example, `OpenBK7231N_1.18.284` is displayed as chipset **BK7231N** and version **1.18.284**.

### Multi-channel devices
Multi-channel OpenBeken devices can be represented as individual controllable outputs while sharing one physical device/IP. OpenBKAdmin reuses physical-device information for rows that share the same IP. Starting with 0.5.4, individual output state is read from the native OpenBeken channel value so one channel does not incorrectly copy another channel's ON/OFF state.

### Network Auto Scan
Auto Scan supports configurable IP ranges and ports and validates discovered OpenBeken endpoints. Version 0.5.1 added a more tolerant two-pass network probe and an OpenBeken-native discovery fallback using `/obkdevicelist`, the HTTP peer list exposed by the firmware SSDP `obkDeviceList` implementation.

### MQTT discovery
MQTT-assisted discovery supports broker host, port, credentials, discovery subscriptions/timeouts and command/status/telemetry prefixes.

### Device configuration
The interface exposes supported OpenBeken configuration including network, MQTT, timers, Wi-Fi/AP, power-on behavior, LED behavior, hostname, IP/gateway/subnet/DNS, MQTT topics/retain options and telemetry period.

### Backup and restore
- Create and download device backups
- Restore OpenBeken `.dmp` backups
- Validate invalid/wrong backup files
- Warn before restoring settings that may change network or MQTT accessibility

### Firmware updates
OpenBKAdmin firmware management is designed specifically around **OpenBeken**:
- Manual firmware upload
- Automatic firmware retrieval from the official OpenBeken GitHub releases
- Automatic chipset detection for selected physical devices
- Chipset-aware firmware selection for each physical device
- Uses only the firmware asset identified as **OTA Update** for the detected device chipset
- Compares installed and available firmware versions
- Offers normal updates only when the official firmware is newer
- Avoids automatic downgrade to an older release
- Updates multiple selected devices, resolving the correct OTA image independently per chipset
- Revalidates the chipset immediately before preparing an automatic firmware update
- Blocks automatic flashing when a compatible chipset/OTA target cannot be resolved safely
- Shows the selected device Full Name, chipset and IP before execution
- Uses one OpenBeken release metadata lookup for all selected chipsets and caches release metadata to reduce GitHub API requests/rate-limit failures
- Uses the OpenBeken OTA command flow: `OtaUrl <url>` followed by `Upgrade 1`

Official firmware source: https://github.com/openshwprojects/OpenBK7231T_App/releases

### Home Assistant add-on
The repository includes Home Assistant add-on packaging, NGINX + PHP-FPM runtime, direct Web UI access, persistent configuration/device data, repository metadata and dedicated OpenBKAdmin branding assets.

### Multilingual interface
OpenBKAdmin retains the application's multilingual architecture. Brazilian Portuguese (pt-BR) has been extensively reviewed for OpenBKAdmin/OpenBeken terminology.

## Help links

- **Documentation:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/README.md
- **Commands:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/commands.md
- **Templates / Devices List:** https://openbekeniot.github.io/webapp/devicesList.html
- **FAQ:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/faq.md
- **Forum:** https://www.elektroda.com/rtvforum/forums.html

## Installation in Home Assistant

Add this repository to the Home Assistant Add-on Store:

`https://github.com/cyberrep/hassio-openbkadmin`

Then open **Settings → Add-ons → Add-on Store → Repositories**, add the repository, refresh the store, select **OpenBKAdmin**, install it and start the add-on.

Publishing a newer version number in `openbkadmin/config.yaml` allows Home Assistant to detect the release and offer the normal **Update** workflow. Persistent add-on data is preserved through normal upgrades.

## Repository structure

```text
hassio-openbkadmin/
├── repository.yaml
├── README.md
└── openbkadmin/
    ├── config.yaml
    ├── Dockerfile
    ├── build.yaml
    ├── icon.png
    ├── logo.png
    ├── CHANGELOG.md
    ├── app/
    └── rootfs/
```

## Changelog

See [`openbkadmin/CHANGELOG.md`](openbkadmin/CHANGELOG.md) for the complete release history.

### 0.5.4
- Correct native per-channel ON/OFF state for multi-channel OpenBeken devices
- Official OTA lookup moved until after device selection
- One GitHub release metadata lookup handles all selected chipsets
- 15-minute OpenBeken release cache with rate-limit fallback
- Selected-device summary shown before OTA execution
- OpenBKAdmin navbar logo/favicons corrected

### 0.5.3
- Automatic chipset detection in the Official OpenBeken Release workflow
- Revalidates each selected device chipset immediately before automatic OTA preparation
- Resolves the correct official OTA image independently for mixed-chipset device selections
- Blocks automatic firmware flashing when the chipset or compatible OTA target cannot be resolved safely
- Shows Full Name, detected chipset and IP during firmware update selection/preparation
- Firmware update logs prioritize the OpenBeken Full Name when available
- Keeps installed-versus-available version checks and downgrade protection

### 0.5.2
- Improved automatic OpenBeken OTA preparation and chipset-aware update handling
- Continued firmware workflow cleanup for physical devices and multi-device updates

### 0.5.1
- More reliable OpenBeken Auto Scan with two-pass probing
- Native `/obkdevicelist` discovery fallback
- Verified OpenBeken `OtaUrl` + `Upgrade 1` OTA flow
- Round OpenBKAdmin navigation icon
- Centered device-selection checkboxes

## Project status

OpenBKAdmin is under active development. OpenBeken behavior can vary between devices, chipsets, templates and firmware versions, so some functionality depends on what a specific device exposes through its Web UI/API.

## Credits and upstream project

**OpenBKAdmin is based on the TasmotaAdmin codebase.** The original TasmotaAdmin project provided the foundation from which OpenBKAdmin was adapted and developed:

https://github.com/TasmoAdmin/TasmoAdmin

OpenBeken / OpenBK7231T_App:

https://github.com/openshwprojects/OpenBK7231T_App

Many thanks to both upstream communities and contributors whose work made this project possible.

## License

See the license files included in this repository. The repository retains the applicable upstream TasmotaAdmin GPL licensing notice together with the OpenBKAdmin add-on licensing information and notices for other upstream components used by the project.
