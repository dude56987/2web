% 2WEB(1)

NAME
====

2web - CLI for administration of 2web server

SYNOPSIS
========

`2web [ -a ] [ --all ] [ all ]`

DESCRIPTION
===========

This is the 2web administration and update program. With this interface you can launch a update check on all 2web modules. 2web allows you to host multimedia content as a kodi compatible php media server using apache2. This software is designed to run on the latest version of the raspberry pi. Currently supporting a extremely large libary ~20k shows,movies,books, and ~100k channels/radio stations.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-a, --all, all`

:   Run all modules and check for updated settings and content in libaries.

`-a, --all, all`

:   Run all 2web components

`-p, --parallel, parallel`

:   Run all 2web compentents in parallel.

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

:   Download the latest version of the hls.js libary for use.

Features
========

# Books

## Text

- Supported Filetypes
  - PDF
  - TXT
  - EPUB (Expermental)

## Comics

- Supported Filetypes
  - CBZ
  - Image Directory

#NFO Media Libaries

## Movies

- You can add movies
  - through the web interface
  - /etc/2web/nfo/libaries.cfg

- Supported Filetypes
  - MKV
  - MP4
  - AVI

## Shows

- You can add shows
  - through the web interface
  - /etc/2web/nfo/libaries.cfg

- Supported Filetypes
  - MKV
  - MP4
  - AVI

# Live

- Supports M3U iptv playlists

## TV

- Supports
  - M3U iptv playlists
  - Twitch Channels
  - Live Youtube Channels
  - Any link that can be resolved with streamlink can be added as a channel
  - Custom Direct video stream Links

## Radio

- Supports
  - M3U iptv playlists
  - Custom Direct Radio Stream Links

