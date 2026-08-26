# OpenBKAdmin

<p align="center">
  <img src="https://github.com/user-attachments/assets/75a60374-1bd0-4450-86d4-d6fd79c69613" alt="OpenBKAdmin - Manage, Flash, Monitor" width="100%">
</p>

<p align="center"><strong>Centralized OpenBeken device management for Home Assistant</strong></p>

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring, configuring and managing devices running **OpenBeken** from a single web interface.

> Current add-on version: **0.4.9**

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
OpenBKAdmin can display information obtained from each physical device, including:
- IP address and position
- Short Name and Full Name
- Chipset
- Firmware version
- Wi-Fi/RSSI information
- Runtime
- Channel/output names
- Device state
- Energy/telemetry information when exposed by the firmware

Firmware identification is separated into useful fields. For example, `OpenBK7231N_1.18.284` is displayed as chipset **BK7231N** and version **1.18.284**.

### Multi-channel and channel-aware devices
Multi-channel OpenBeken devices can be represented as individual controllable outputs while sharing one physical device/IP. OpenBKAdmin reuses information obtained from the physical device instead of unnecessarily querying the same IP separately for every channel.

Channel-aware naming allows descriptive names such as `Luz - Cozinha - Balcão`. OpenBeken channel labels are used when available, with fallback to names exposed by the firmware.

### Network Auto Scan
Auto Scan supports configurable IP ranges and ports, validates discovered OpenBeken endpoints, lets you review results and can save multiple discovered devices in batch.

### MQTT discovery
MQTT-assisted discovery supports broker host, port, credentials, discovery subscriptions/timeouts and command/status/telemetry prefixes. Results distinguish updated, new and offline devices as well as topic conflicts; ambiguous MQTT topics are not automatically applied.

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
- Chipset-aware firmware selection
- Uses only the firmware asset identified as **OTA Update** for the device chipset
- Compares installed and available firmware versions
- Offers normal updates only when the official firmware is newer
- Avoids automatic downgrade to an older release
- Update selected devices
- Graceful handling of temporary GitHub/network lookup failures

Official firmware source: https://github.com/openshwprojects/OpenBK7231T_App/releases

### Home Assistant add-on
The repository includes Home Assistant add-on packaging, NGINX + PHP-FPM runtime, direct Web UI access, persistent configuration/device data, repository metadata and dedicated OpenBKAdmin branding assets.

### Multilingual interface
OpenBKAdmin retains the application's multilingual architecture. **Brazilian Portuguese (pt-BR) has been extensively reviewed and corrected** for OpenBKAdmin/OpenBeken terminology. OpenBeken-specific additions are integrated into the translation system for the available languages rather than being hard-coded into individual pages.

## Help links

The Help menu points to official OpenBeken resources:

- **Documentation:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/README.md
- **Commands:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/commands.md
- **Templates / Devices List:** https://openbekeniot.github.io/webapp/devicesList.html
- **FAQ:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/faq.md
- **Forum:** https://www.elektroda.com/rtvforum/forums.html

## Installation in Home Assistant

Add this repository to the Home Assistant Add-on Store:

`https://github.com/cyberrep/hassio-openbkadmin`

Then open **Settings → Add-ons → Add-on Store → Repositories**, add the URL above, refresh the store, select **OpenBKAdmin**, install it and start the add-on.

Once the repository is installed, publishing a newer version number in `openbkadmin/config.yaml` allows Home Assistant to detect the new add-on release and offer the normal **Update** workflow instead of requiring removal/reinstallation. Persistent add-on data is therefore preserved through normal upgrades.

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

### 0.4.9
- Official OpenBeken GitHub Releases integration for firmware updates
- Chipset-aware **OTA Update** firmware selection
- Firmware version comparison so normal updates are offered only for newer firmware
- Removal of the obsolete `ota.openbeken.com` dependency
- Better handling of GitHub/network firmware lookup failures
- OpenBeken-focused updater cleanup

### 0.4.8
- New OpenBKAdmin visual identity
- New Home Assistant add-on icon and full OpenBKAdmin logo
- Brazilian Portuguese translation reviewed and consolidated
- General packaging/repository improvements

### 0.4.x
Major OpenBeken-focused refactoring including OpenBKAdmin naming/branding, OpenBeken-specific device information, Full Name/Short Name support, multi-channel handling, channel-aware display names, chipset/version separation, Auto Scan, MQTT discovery, backup/restore, OpenBeken help links, translation expansion and Home Assistant packaging fixes.

## Project status

OpenBKAdmin is under active development. OpenBeken behavior can vary between devices, chipsets, templates and firmware versions, so some functionality depends on what a specific device exposes through its Web UI/API.

Bug reports and reproducible device examples are welcome.

## Credits and upstream project

**OpenBKAdmin is based on the TasmotaAdmin codebase.** The original TasmotaAdmin project provided the foundation from which the OpenBKAdmin project was adapted and developed:

https://github.com/TasmoAdmin/TasmoAdmin

OpenBKAdmin substantially adapts that foundation for the **OpenBeken** ecosystem, including OpenBeken discovery, firmware/chipset handling, multi-channel behavior, configuration workflows, Home Assistant add-on packaging, translations and the OpenBKAdmin visual identity.

OpenBeken / OpenBK7231T_App:

https://github.com/openshwprojects/OpenBK7231T_App

Many thanks to both upstream communities and contributors whose work made this project possible.

## License

See the license files included in this repository. The repository retains the applicable upstream TasmotaAdmin GPL licensing notice together with the OpenBKAdmin add-on licensing information and notices for other upstream components used by the project.
