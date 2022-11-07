% 2WEB()

NAME
====

2web - CLI for administration of 2web server

SYNOPSIS
========

`2web [ -a ] [ --all ] [ all ]`

DESCRIPTION
===========

Generate a website for use on a LAN(Local Area Network) as a http/https accessible multimedia library. 2web allows you to host multimedia content as a KODI compatible http/https media server using apache2 php and minimal javascript. This software is designed to run on the latest version of the raspberry pi (4 2gig). Currently supporting a library size of ~20k shows, ~20k movies, ~20k books, ~50 weather stations and ~5k channels/radio stations on a raspberry pi 4. This software is also designed to be compatible with any Ubuntu or Debian based system. So if you have more than a raspberry pi applications are multi threaded to be able to completely utilize any amount of hardware.

This is the 2web administration and update program. With this interface you can manage all 2web components simultaneously.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-a, --all, all`

:   Run all modules and check for updated settings and content in libaries.

`-a, --all, all`

:   Run all 2web components

`-p, --parallel, parallel`

:   Run all 2web compentents in parallel.(!EXPERMIMENTAL!)

`-I, --iptv, iptv`

:   Update iptv2web

`-N, --nfo, nfo`

:   Update nfo2web

`-C, --comic, comic`

:   Update comic2web

`-rc, --reboot-check, rebootcheck`

:   Check if it is the reboot hour and reboot if it is.

`-cc, --clean-cache, cleancache`

:   Cleanup the web caches based on web cache time setting

`-L, --unlock, unlock`

:   Remove lockfiles leftover from system crashes or reboots.

`-U, --upgrade, upgrade`

:   Upgrade libaries used by modules in the background for operation. This can fix issues with backend resolution issues. Upgrade youtube-dl, gallery-dl, hls.js

`-u, --update, update`

:   Update all 2web components.

`-r, --reset, reset`

:   Reset the state of all 2web components without deleting generated data.

`-w, --webgen, webgen`

:   Generate webpage parts of all 2web components.

`-l, --libary, libary`

:   Download the latest version of the hls.js library for use.

Understanding /etc/2web/
===================

Contains all settings related to 2web and 2web modules. Sever only accessible config files exist and web interface editable example.d/ files exist for that use case. The file ownership of the example.d/ files can be set to root and not www-data to block the web interface out.


- Directories
  - /etc/2web/comics/
    - This is where comic2web settings are stored
  - /etc/2web/nfo/
    - This is where nfo2web settings are stored
  - /etc/2web/ytdl/
    - This is where ytdl2nfo settings are stored
  - /etc/2web/iptv/
    - This is where iptv2web settings are stored
  - /etc/2web/themes/
    - This is the location of all default and user installed CSS themes.
    - Any files found here with a .css extension will be copied into the theme list in the web interface.
    - Custom themes can be created with a single .css file and added in this directory.
  - /etc/2web/users/
    - This is where the administrator credentials are kept for users

- Files
  - /etc/2web/theme.cfg
    - This is the name of the theme file found in /etc/2web/themes/ that has been chosen as the website CSS theme.
  - /etc/2web/fortuneStatus.cfg
    - This is a binary file that if it exists the '/usr/bin/fortune' will be displayed on the homepage of the website.
  - /etc/2web/certInfo.cnf
    - This is the cert info template used to generate the ssl certificate. Look more into openssl key generation.
  - /etc/2web/cacheNewEpisodes.cfg
    - This will cause the new episodes to be cached in the background using a single file queue. Active caching still will work.
  - /etc/2web/weatherLocation.cfg
    - This determines the location used for the weather on the homepage.

### Performance Enhancements

#### RAMDISKS

A ramdisk can be used on the entire website to host the entire generated site in ram, media files will still be loaded from disk. If you load the entire website into ram it will need to be regenerated every time the server is rebooted. In this case if the system is fast enough you can edit /etc/cron.d/2web by adding --parallel to make site components run parallel processing. However some individual directories in the site can be safely symlinked to a ramdisk with little issue.

- /var/cache/2web/web/web_cache/
  - This directory contains php generated files for caching web requests
- /var/cache/2web/web/RESOLVER-CACHE/
  - This directory contains the temporary media files cached for playback from external website videos.
- /var/cache/2web/web/m3u_cache/
  - Contains the temporary .m3u playlists generated on web request.

