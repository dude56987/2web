2web
====

Generate a website for use on a LAN(Local Area Network) as a http/https accessible multimedia library. 2web allows you to host multimedia content as a KODI compatible http/https media server using apache2 php and minimal javascript. This software is designed to run on the latest version of the raspberry pi (4 2gig). Currently supporting a library size of ~20k shows, ~20k movies, ~20k books, ~50 weather stations and ~5k channels/radio stations on a raspberry pi 4. This software is also designed to be compatible with any Ubuntu or Debian based system. So if you have more than a raspberry pi applications are multi threaded to be able to completely utilize any amount of hardware.

## Supported Module Content

 - Comics
 - Movies
 - Shows
 - Music
 - Live TV
 - Live Radio
 - Weather Forecasts
 - System Graphs

## Features

 - NO API KEYS REQUIRED
 - Add web addresses to include any website or individual user channel on sites supported by ([yt-dlp](https://github.com/yt-dlp/yt-dlp)/[youtube-dl](https://ytdl-org.github.io/youtube-dl/index.html)) as a show in the media libary
 - Add web addresses of online comics to cache them locally in your comics section with [gallery-dl](https://github.com/mikf/gallery-dl)
 - Add book collections containing .cbz .txt .pdf .zip or simple folders containing image files.
 - Add music collections and music is auto-sorted based on id3 tag information
 - Add any livestream web address as a live channel using [streamlink](https://streamlink.github.io)
 - Mostly [PHP](https://www.php.net) and [Bash](https://www.gnu.org/software/bash/)
 - Light touches of [Javascript](https://en.wikipedia.org/wiki/JavaScript) mostly for html5 live in webpage player
 - Weather info via [weather-util](http://fungi.yuggoth.org/weather/) WITHOUT NEED FOR AN API KEY thanks to METAR data from the National Oceanic and Atmospheric Administration and forecasts from the National Weather Service.
 - Lots of included Themes and custom themes can be installed with a single CSS file
 - Direct Links to hosted media for playback with player of your choice
 - VLC links to immediately start playback in [VLC](https://www.videolan.org/vlc/) from the webpage on mobile
 - Command line interface with excessive man pages
 - SOFTWARE DOES NOT TOUCH THE DATA SOURCES, everything is symlinked

## Access
To access the local webserver you must have run

	2web

as an adminstrator(sudo 2web) to enable it. Then go to

	http://localhost/

Once you login the first time you will probably want to create a adminstrative user to password lock the settings. You can then enable the modules you want to run automatically at

	https://localhost/settings/modules.php

### Apache Settings

2web takes over port 80 and 443 by default but can be changed in

	/etc/apache2/sites-available/0-2web-website.conf
	/etc/apache2/sites-available/0-2web-website-SSL.conf

### Settings

Nearly everything can be configured via the web interface.

	http://localhost/settings/


### Updates

Updates to modules are scheduled via cron and can be only edited at

	/etc/cron.d/2web

by a system administrator. If you want to force a update immediately you must have Command line access to the system and use the update commands in the below CLI section.

## CLI

The CLI can be used by administrators on the server to update various content stacks on the server.

To build the backbone of the webserver run

	2web

This will enable the server and place all base webpages to allow access to the web interface.

The master interface can update all modules in one command.

	2web all

Or to max out the speed

	2web --parallel

Each individual web section has its own CLI interface for running manual generation or a clean reset of a individual module. Resets may be required for missing or removed content. Sometimes after updates the layout will change and the remote metadata will duplicate this can be solved by a section reset. In the worst case if functionality is broken you can run

	2web nuke

and

	2web all

to delete all metadata and website data and rebuild the entire website.

To update each section use the following

	nfo2web

	iptv2web

	comic2web

	weather2web

	ytdl2nfo

	music2web

	graph2web

Resets can be done with the master interface by

	2web reset

and individually by

	nfo2web reset

	iptv2web reset

	comic2web reset

	weather2web reset

	ytdl2nfo reset

	music2web reset

	graph2web reset

Finally you can delete the entire website with

	2web nuke

or individually

	nfo2web nuke

	iptv2web nuke

	comic2web nuke

	weather2web nuke

	ytdl2nfo nuke

	music2web nuke

	graph2web nuke

kodi2web is a diffrent module, it is called inside individual modules to trigger updates within connected remote kodi clients. This to can be triggered manually by an administrator with

	kodi2web

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

# NFO Media Libaries

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

## Testing Info
 - Operating Systems
   - DietPi
     - Raspbery PI 4
   - Ubuntu
   - Raspbian
   - Debian

### Module Components

| module  | man page | Web Settings | /etc/2web/ |
|---------|----------|--------------|------------|
| 2web    | ✔️        | ✔️            | ✔️          |
| nfo     | ✔️        | ✔️            | ✔️          |
| comic   | ✔️        | ✔️            | ✔️          |
| iptv    | ✔️        | ✔️            | ✔️          |
| wiki    | ❌       | ❌           | ✔️          |
| weather | ✔️        | ✔️            | ✔️          |
| graph   | ❌       | ❌           | ✔️          |
| kodi    | ❌       | ❌           | ✔️          |

### Resolver Components

| resolvers     | Web Settings | /etc/2web/ |
|---------------|--------------|------------|
| ytdl-resolver | ✔️            | ✔️          |
| m3u-gen       | ❌           | ❌         |
| iptv-resolver | ❌           | ❌         |
