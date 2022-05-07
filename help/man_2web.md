% 2WEB()

NAME
====

2web - CLI for administration of 2web server

SYNOPSIS
========

`2web [ -a ] [ --all ] [ all ]`

DESCRIPTION
===========

This is the 2web administration and update program. With this interface you can launch a update check on all 2web modules. 2web allows you to host multimedia content as a kodi compatible php media server using apache2. This software is designed to run on the latest version of the raspberry pi. Currently supporting a extremely large libary ~20k shows,movies,books, and ~100k channels/radio stations. The '2web_help' manual page contains more usage info.

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
    - This is the location of all default and user installed CSS themes. Any files found here with a .css extension will be copied into the theme list in the web interface.
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

