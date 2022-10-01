% WEATHER2WEB(1)

NAME
====

weather2web - 2web weather module

SYNOPSIS
========

`weather2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web interface for weather station info for given locations. This uses the weather-util package weather command known to work best in north america.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-e, --enable`

:   Enable this module to run automatically as a service.

`-d, --disable`

:   Disable this module from running in the background and remove generated data.

`-u --update or update`

:  This will update the webpages and refresh the database."

`--reset, reset`

:  This will reset the state of the cache so everything will be updated."

`--nuke, nuke`

:   This will delete the cached website."

`-u, --update, update`

:   Download links and scan libaries.

`-r, --reset, reset`

:   Reset the state of all 2web components without deleting generated data.

`-w, --webgen, webgen`

:   Generate weather website components.
