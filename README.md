# OpenBKAdmin

**Centralized OpenBeken device management for Home Assistant**

OpenBKAdmin is a Home Assistant add-on focused on discovering, organizing, monitoring and managing devices running **OpenBeken** from a single web interface.

This project adapts and extends the original administration concept for an OpenBeken-focused environment, with Home Assistant packaging, OpenBeken device discovery, channel-aware naming, firmware information, backup/restore, MQTT discovery, multilingual UI and a dedicated visual identity.

> Current add-on version: **0.4.8**

## Features

### Device management
- Add OpenBeken devices manually by IP and port
- Edit registered devices
- Delete and restart devices
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
OpenBKAdmin can display information obtained from the device, including:
- IP address
- Position
- Short Name
- Full Name
- Chipset
- Firmware version
- Wi-Fi/RSSI information
- Runtime
- Channel/output names
- Device state
- Energy/telemetry information when exposed by the device

Firmware strings are presented in a more useful format. For example:

`OpenBK7231N_1.18.284`

is separated into:
- **Chipset:** `BK7231N`
- **Version:** `1.18.284`

### Channel-aware naming
Multi-channel OpenBeken devices are represented as individual controllable outputs while sharing the same physical device/IP.

OpenBKAdmin works with device/channel naming so the interface can show descriptive names such as:

`Luz - Cozinha - Balcão`

instead of only a generic output name.

The project also handles OpenBeken channel labels when they are available and falls back to names returned by the firmware when no `SetChannelLabel` information can be obtained.

### Network Auto Scan
The Auto Scan page helps discover OpenBeken devices on the local network.

Features include:
- Start and end IP range
- Additional scan ranges
- Configurable port
- Detection of devices responding on the network
- Validation that the detected endpoint is an OpenBeken device
- Review discovered devices before saving
- Save discovered devices in batch

### MQTT discovery
OpenBKAdmin also includes MQTT-assisted discovery.

Configurable options include:
- MQTT broker host
- Port
- Username/password
- Discovery subscriptions
- Discovery timeout
- Command, status and telemetry prefixes

Discovery results distinguish:
- Updated devices
- New devices
- Offline devices
- Topic conflicts

Ambiguous MQTT topics are not automatically applied.

### Device configuration
The interface exposes configuration areas for supported device settings, including:
- General settings
- Network configuration
- MQTT configuration
- Timers
- Wi-Fi/AP settings
- Power-on behavior
- LED behavior
- Hostname
- IP/gateway/subnet/DNS
- MQTT topics and retain options
- Telemetry period

### Backup and restore
OpenBKAdmin includes device backup and restore support.

- Create backups
- Download backups
- Restore an OpenBeken `.dmp` backup to a selected device
- Validation for invalid/wrong backup files
- Warnings before restoring settings that may change network or MQTT accessibility

### Firmware update
Firmware-management functionality includes:
- Manual firmware upload
- Minimal and full firmware packages
- Automatic firmware retrieval
- Update selected devices
- Version comparison
- Optional newer-version-only behavior
- Force-upgrade option
- Stable/Beta update channel settings
- Configurable connection/request timeouts

### Home Assistant integration
The repository is packaged as a Home Assistant add-on and includes:
- Home Assistant add-on configuration
- NGINX + PHP-FPM runtime
- Direct Web UI access
- Persistent configuration/device data
- Repository metadata
- Dedicated `icon.png`
- Dedicated `logo.png`
- OpenBKAdmin branding

### Multilingual interface
OpenBKAdmin keeps the multilingual structure of the application.

**Brazilian Portuguese (pt-BR) has been extensively reviewed and corrected** for the OpenBeken/OpenBKAdmin terminology, including:
- Navigation
- Device management
- Auto Scan
- MQTT discovery
- Backup/restore
- Device configuration
- Firmware updates
- Status/error messages
- JavaScript update messages

The other language files remain available and the project is being progressively reviewed so OpenBeken-specific additions use the same translation system.

## Help links

The Help menu points to the official OpenBeken resources:

- **Documentation:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/README.md
- **Commands:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/commands.md
- **Templates / Devices List:** https://openbekeniot.github.io/webapp/devicesList.html
- **FAQ:** https://github.com/openshwprojects/OpenBK7231T_App/blob/main/docs/faq.md
- **Forum:** https://www.elektroda.com/rtvforum/forums.html

## Installation in Home Assistant

Add this repository to the Home Assistant Add-on Store:

`https://github.com/cyberrep/hassio-openbkadmin`

Then:

1. Open **Settings → Add-ons → Add-on Store**.
2. Open the repository menu.
3. Choose **Repositories**.
4. Add the repository URL above.
5. Refresh the Add-on Store.
6. Open **OpenBKAdmin**.
7. Install and start the add-on.
8. Open the Web UI.

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
    ├── app/
    └── rootfs/
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

### 0.4.8
- New OpenBKAdmin visual identity
- New Home Assistant add-on icon
- New full OpenBKAdmin logo
- Branding prepared for both Add-on Store and application UI
- Brazilian Portuguese translation reviewed and consolidated
- General packaging/repository improvements

### 0.4.x
Major OpenBeken-focused refactoring:
- OpenBKAdmin naming and branding
- Removal/replacement of legacy Tasmota/Sonoff-facing terminology in the OpenBeken workflow
- OpenBeken-specific device information
- Full Name and Short Name support
- Multi-channel device handling
- Channel-aware display names
- Chipset and firmware-version separation
- Improved device detail/configuration page
- Auto Scan improvements
- MQTT discovery
- Backup/restore workflow
- Help menu updated to OpenBeken documentation, commands, templates, FAQ and forum
- Translation-system expansion for newly added UI strings
- Home Assistant add-on build and startup fixes

### 0.3.x
Foundation work for the Home Assistant/OpenBeken adaptation:
- Home Assistant add-on runtime
- OpenBeken device detection
- Device list and control
- Initial Auto Scan implementation
- Device naming improvements
- Multi-channel handling
- Early OpenBeken configuration integration

## Project status

OpenBKAdmin is under active development. OpenBeken firmware behavior can vary between devices, chipsets, templates and firmware versions, so some functionality may depend on what a specific device exposes through its Web UI/API.

Bug reports and reproducible device examples are welcome.

## Credits

OpenBKAdmin is built for the **OpenBeken** ecosystem and uses the work and documentation provided by the OpenBeken/OpenBK7231T_App community.

OpenBeken project:
https://github.com/openshwprojects/OpenBK7231T_App

This repository contains the Home Assistant/OpenBKAdmin adaptation and the additional integration, interface and management work maintained here.

## License

See the license files included in this repository and the licenses/notices of upstream components used by the project.
