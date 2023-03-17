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

`-e --enable or enable`

:  Enable this module and schedule it to run in the background.

`-d --disable or disable`

:  Disable this module, delete cached data, and unschedule from running in the background.

`-u --update or update`

:  This will update the webpages and refresh the database.

`-U --upgrade or upgrade`

:  This upgrades the PIP python packages for yt-dlp and streamlink. HLS.js is compiled in the package build process.

`--reset, reset`

:  This will reset the state of the cache so everything will be updated.

`--nuke, nuke`

:   This will delete the cached website.

`-u, --update, update`

:   Download links and rebuild web data.

`-r, --reset, reset`

:   Reset the state of all 2web components without deleting generated data.

`-w, --webgen, webgen`

:   Generate iptv website from currently processed links.

`-E, --epg, epg`

:   Download and recombine the epg.xml from all external EPG sources. This option processes only EPG files. The regular --update option will process everything including EPG files.
