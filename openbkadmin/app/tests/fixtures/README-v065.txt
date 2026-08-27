OpenBKAdmin 0.6.5 verification

State source:
- OpenBeken upstream src/httpserver/json_interface.c generates POWER/POWERx from CHANNEL_Get() for relay/toggle channels.
- OpenBeken upstream src/cmnds/cmd_channels.c defines ChN with an argument as a SET operation; Ch is not a read-all status API.
- OpenBKAdmin therefore uses Status 0 POWER/POWERx for the list state.

BL602/BL616 OTA:
- OpenBeken upstream src/httpserver/rest_interface.c registers POST /api/ota.
- BL602/BL_NEW routes that request to http_rest_post_flash().
- OpenBeken upstream src/hal/bl602/hal_ota_bl602.c validates BL60X_OTA header and writes the backup firmware partition.
- OpenBKAdmin proxies the firmware image server-side to POST /api/ota.
