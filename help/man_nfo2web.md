% NFO2WEB(1)

NAME
====

nfo2web - CLI for administration

SYNOPSIS
========

`nfo2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web interface for nfo libaries.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`--cert, cert`

:  Updated he self signed ssl cert if it is older than 365 days"

`--CERT, CERT`

:  Force update the self signed ssl cert"

`-u --update or update`

:  This will update the webpages and refresh the database."

`--reset, reset`

:  This will reset the state of the cache so everything will be updated."

`--nuke, nuke`

:   This will delete the cached website."

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
