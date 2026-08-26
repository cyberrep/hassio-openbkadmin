Changelog

All notable OpenBKAdmin changes are documented here.

[0.4.8] - 2026-08-26

Added

New OpenBKAdmin visual identity.

Dedicated Home Assistant icon.png.

Full OpenBKAdmin logo.png.

Branding assets for the Add-on Store and application interface.

Fully reviewed Brazilian Portuguese (pt-BR) translation as the reference PT-BR language file.

Changed

Improved repository presentation for public GitHub publication.

Expanded project documentation.

Consolidated OpenBeken/OpenBKAdmin terminology.

[0.4.7] - 2026-08-26

Added

Complete Home Assistant repository/add-on package.

Repository metadata and validation files.

OpenBeken-oriented Add Device and Auto Scan translation keys.

Translation coverage work across the existing language files.

Changed

Help menu links corrected for OpenBeken documentation.

Commands now points directly to docs/commands.md.

Troubleshooting renamed to FAQ and linked directly to docs/faq.md.

Adopt-name UI temporarily removed from the active workflow.

General OpenBKAdmin packaging and build fixes.

[0.4.x] - 2026-08

Added

OpenBeken Full Name and Short Name handling.

Multi-channel device representation.

Channel-aware display names.

Chipset extraction from OpenBeken firmware strings.

Firmware version displayed separately from chipset.

Device details/configuration view.

Network Auto Scan improvements.

MQTT-assisted discovery.

Backup and restore support.

Additional OpenBeken configuration and status information.

New translation keys for OpenBKAdmin-specific functionality.

Changed

Project refactored toward OpenBKAdmin/OpenBeken terminology.

Legacy Tasmota/Sonoff-facing naming progressively replaced in the OpenBeken workflow.

Device polling optimized so multiple channels sharing one IP can reuse physical-device information.

Device-list presentation and actions improved.

Help resources changed to OpenBeken documentation, commands, device templates, FAQ and community forum.

Home Assistant add-on startup/build integration improved.

Device status handling revised to reduce unnecessary state changes/flicker.

Fixed

Multiple Home Assistant add-on startup/build issues encountered during the OpenBeken migration.

Translation constants missing from newly introduced pages.

Device edit type mismatch in the OpenBeken-specific code path.

Several Auto Scan and Save All workflow issues.

Settings page failure when the legacy OTA host could not be resolved.

Various device-name, channel-name and display inconsistencies.

[0.3.x] - 2026-08

Added

Initial Home Assistant add-on adaptation.

OpenBeken device discovery/detection.

Device list and basic controls.

Initial IP Auto Scan.

Device naming support.

Initial multi-channel handling.

Initial OpenBeken configuration integration.

Changed

Began migration from the original administration codebase toward a dedicated OpenBeken management application.
