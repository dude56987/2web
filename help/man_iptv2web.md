% IPTV2WEB(1)

NAME
====

iptv2web - 2web live module

SYNOPSIS
========

`iptv2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web interface for live iptv links. Support for individual custom channels, local/remote m3u lists, Streaming site links(e.g. youtube, twitch), local/remote Radio m3u lists. All sources are combined into a single m3u list that can be linked to any iptv capable device/app(KODI,VLC).

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-u --update or update`

:  This will update the webpages and refresh the database.

`-U --upgrade or upgrade`

:  This upgrades the PIP python packages for yt-dlp and streamlink. HLS.js is compiled in the package build process.

`--reset, reset`

:  This will reset the state of the cache so everything will be updated.

`--nuke, nuke`

:   This will delete the cached website.

`-u, --update, update`

:   Download links and scan libaries.

`-r, --reset, reset`

:   Reset the state of all 2web components without deleting generated data.

`-w, --webgen, webgen`

:   Generate comic website components.
