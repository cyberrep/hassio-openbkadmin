# OpenBKAdmin

<p align="center">
  <img src="https://github.com/user-attachments/assets/75a60374-1bd0-4450-86d4-d6fd79c69613" alt="OpenBKAdmin - Manage, Flash, Monitor" width="100%">
</p>

<p align="center"><strong>Centralized OpenBeken device management for Home Assistant</strong></p>

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring, configuring and managing devices running **OpenBeken** from a single web interface.

> Current add-on version: **0.6.4**

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
Multi-channel OpenBeken devices can be represented as individual controllable outputs while sharing one physical device/IP. OpenBKAdmin reuses physical-device information for rows that share the same IP. Device state prefers native OpenBeken channel values, mapping relay/output rows to their corresponding channels (`Channel0`, `Channel1`, etc.) instead of relying only on the Tasmota-compatible `Status 0` POWER field.

### Network Auto Scan
Auto Scan supports configurable IP ranges and ports and validates discovered OpenBeken endpoints. It includes a tolerant two-pass network probe and OpenBeken-native discovery fallback using `/obkdevicelist`.

### MQTT discovery
MQTT-assisted discovery supports broker host, port, credentials and discovery timeout. Native OpenBeken discovery listens broadly for per-device base topics and recognizes native identity/telemetry topics such as `<device>/connected`, `<device>/ip`, `<device>/rssi`, `<device>/uptime`, `<device>/freeheap`, `<device>/sockets`, `<device>/datetime`, `<device>/mac`, `<device>/build` and `<device>/host`. When a device base topic is detected but its address is not yet known, OpenBKAdmin requests `<device>/ip/get`.

Discovery does **not** require the Tasmota TELE compatibility flag. Tasmota-compatible TELE/STAT discovery remains only as a fallback and STATUS responses are validated so actual Tasmota devices are not imported as OpenBeken devices. OpenBeken `MqttGroup` / Group Topic is treated as a shared command group, not as a reliable per-device discovery identifier.

### Device configuration
The interface exposes supported OpenBeken configuration including network, MQTT, timers, Wi-Fi/AP, power-on behavior, LED behavior, hostname, IP/gateway/subnet/DNS, MQTT topics/retain options and telemetry period.

### Backup and restore
- Create and download device backups
- Restore OpenBeken `.dmp` backups
- Validate invalid/wrong backup files
- Warn before restoring settings that may change network or MQTT accessibility
- Before OTA, automatically create a timestamped configuration dump for every unique selected physical device
- OTA backup filenames include the OpenBKAdmin device ID/name
- LittleFS files such as `autoexec.bat` are treated separately and are not silently restored

### Firmware updates
OpenBKAdmin firmware management is designed specifically around **OpenBeken**:
- Manual firmware upload
- Automatic firmware retrieval from the official OpenBeken GitHub releases
- Automatic chipset detection for selected physical devices
- Chipset-aware firmware selection for each physical device
- Uses only the firmware asset identified as **OTA Update** for the detected chipset
- Compares installed and available firmware versions
- Offers normal updates only when the official firmware is newer
- Avoids automatic downgrade
- Supports mixed-chipset selections
- Shows selected device Full Name, chipset, numeric current firmware version and IP
- Caches release metadata to reduce GitHub API rate-limit failures
- Supports **Mass** parallel OTA and **Individual** sequential OTA modes
- Limits post-OTA status verification to five attempts per device
- Creates a pre-OTA configuration backup before flashing

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

### 0.6.4
- Improved native OpenBeken MQTT discovery based on per-device base topics
- Discovery no longer requires the Tasmota TELE compatibility flag
- Native MQTT discovery uses OpenBeken identity/telemetry topics and requests `<device>/ip/get` when needed
- Tasmota STATUS replies are filtered so real Tasmota devices are not imported as OpenBeken
- Clarified that `MqttGroup` / Group Topic is a command group rather than a per-device discovery identifier
- Improved channel-aware ON/OFF state handling using native OpenBeken channel mapping

### 0.6.3
- Expanded native MQTT discovery to documented OpenBeken telemetry topics
- Added OpenBeken/Tasmota validation for compatibility STATUS responses
- Localized additional firmware-update and backup messages in pt-BR
- Restored missing recent changelog history

### 0.6.2
- Expanded native OpenBeken MQTT detection beyond `connected` and `ip`
- MQTT scan listens long enough for periodic native OpenBeken broadcasts
- Footer shows the running OpenBKAdmin version

### 0.6.1
- Improved MQTT discovery for native OpenBeken topic layout
- Native discovery recognizes `<device-topic>/connected` and `<device-topic>/ip`
- Requests device IP through MQTT when necessary
- Broad MQTT subscription with internal OpenBeken filtering
- Removed the redundant SelfUpdate entry from the main add-on navigation

### 0.5.8
- OTA device heading shows chipset separately and only the numeric installed firmware version
- OTA heading labels use the existing multilingual translation keys
- Reviewed pt-BR remains the Portuguese translation baseline

### 0.5.7
- Native OpenBeken `Ch` channel values used for ON/OFF state
- Pre-OTA configuration backup for each unique physical device
- Timestamped backup names containing device identity
- LittleFS/`autoexec.bat` explicitly kept separate from automatic config restore
- Mass and Individual OTA modes retained with five post-OTA checks

### 0.5.6
- Added Mass (parallel) and Individual (sequential) OTA modes
- Reduced post-OTA verification to five attempts
- Added `.ota` local firmware support

### 0.5.4
- Improved channel-aware state handling
- Reduced OpenBeken GitHub release lookups with caching
- Selected-device summary before OTA
- Branding/favicon improvements

### 0.5.1
- More reliable OpenBeken Auto Scan with two-pass probing
- Native `/obkdevicelist` discovery fallback
- Verified OpenBeken OTA flow
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
