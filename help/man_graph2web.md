% GRAPH2WEB(1)

NAME
====

graph2web - CLI for administration of graph module in 2web server

SYNOPSIS
========

`graph2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web interface for system graphs generated by Munin.

OPTIONS
=======

`-h, --help`

:   Show the help message and exit

`-e, --enable`

:   Enable this module to run automatically as a service.

`-d, --disable`

:   Disable this module from running in the background and remove generated data.

`-u --update or update`

:  This will update the webpages and refresh the database.

`--reset, reset`

:  This will reset the state of the cache so everything will be updated.

`--nuke, nuke`

:   This will delete the cached website.

`-u, --update, update`

:   Scan for active munin graphs and load them into the web interface.

`-w, --webgen, webgen`

:   Generate music website components.
