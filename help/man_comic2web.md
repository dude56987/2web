% COMIC2WEB(1)

NAME
====

comic2web - 2web Comics module

SYNOPSIS
========

`comic2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web interface for comic and ebook libaries. This module also has downlod functionality for remote comics.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-e, --enable`

:   Enable this module and allow automatic updates with cron.

`-d, --disable`

:   Disable this module and stop automatic updates with cron.

`-u --update or update`

:  This will update the webpages and refresh the database.

`--reset, reset`

:  This will reset the state of the cache so everything will be updated. Converted versions of comics will be removed. The generated website and all thumbnails will also be removed.

`--nuke, nuke`

:   This will delete the cached website. This will also disable the module.

`-u, --update, update`

:   Download links and scan libaries. Enable the module if disabled.

`-r, --reset, reset`

:   Remove downloaded comics and converted comics. This will also run the nuke command and remove the entire comics section of 2web.

`-w, --webgen, webgen`

:   Generate comic website components.
