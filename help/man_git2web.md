
% GIT2WEB(1)

NAME
====

git2web - Build and host a report for GIT repositories.

SYNOPSIS
========

`git2web [ -u ] [ --update ] [ update ]`

DESCRIPTION
===========

A module of 2web that generates web report for local or remote git repositories.

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

`--nuke, nuke`

:   This will delete the cached website. This will also disable the module.

`-u, --update, update`

:   Download links and scan libaries. Enable the module if disabled.

`-r, --reset, reset`

:   Remove downloaded comics and converted comics. This will also run the nuke command and remove the entire comics section of 2web.

`-w, --webgen, webgen`

:   Generate comic website components.

`--no-video`

: Skip generating the video with gource for the repository.
