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
 - Wikis
 - System Graphs
 - Git Repositories

## Features

 - NO API KEYS REQUIRED
 - Web interface access to all hosted data
 - Local Search for all hosted data, with external search links
 - Offline dictionary search results
 - WEB Interface compatible with DESKTOP, PHONES, and TABLETS
 - Add wikis from locally downloaded [ZIM](https://wiki.openzim.org/wiki/OpenZIM) files
 - Generate reports for local and remote [GIT](https://en.wikipedia.org/wiki/Git) repositories
 - Add web addresses to include any website or individual user channel on sites supported by ([yt-dlp](https://github.com/yt-dlp/yt-dlp)/[youtube-dl](https://ytdl-org.github.io/youtube-dl/index.html)) as a show in the media library
 - Add web addresses of online comics to cache them locally in your comics section with [gallery-dl](https://github.com/mikf/gallery-dl)
 - Add book collections containing .cbz .txt .pdf .zip or simple folders containing image files.
 - Add music collections and music is auto-sorted based on [ID3](https://en.wikipedia.org/wiki/ID3) tag information
 - Add any livestream web address as a live channel using [streamlink](https://streamlink.github.io)
 - Written in [PHP](https://www.php.net), [Bash](https://www.gnu.org/software/bash/), and [Javascript](https://en.wikipedia.org/wiki/JavaScript)
 - Light touches of [Javascript](https://en.wikipedia.org/wiki/JavaScript) mostly for [HTML5](https://en.wikipedia.org/wiki/HTML5) live in webpage player
 - Weather info via [weather-util](http://fungi.yuggoth.org/weather/) WITHOUT NEED FOR AN API KEY thanks to METAR data from the National Oceanic and Atmospheric Administration and forecasts from the National Weather Service.
 - Lots of included Themes and custom themes can be installed with a single [CSS](https://en.wikipedia.org/wiki/CSS) file
 - Direct Links to hosted media for playback with player of your choice
 - [VLC](https://www.videolan.org/vlc/) links to immediately start playback in [VLC](https://www.videolan.org/vlc/) from the webpage on mobile
 - Chapter support for videos with [SponsorBlock](https://sponsor.ajay.app/)
 - Command line interface with man pages
 - Lock the settings interface by adding at least one administrative user
 - SOFTWARE DOES NOT TOUCH THE DATA SOURCES, everything is symlinked

## Install

Copy and extract the source then run

	./configure
	make
	make install

This should build and install the package on any Debian or Ubuntu based system.

## Uninstall

The install process creates and installs a .deb package file. So you can uninstall the software with the system package manager.

	sudo apt-get remove 2web

If you would like to purge config files generated use

	sudo apt-get purge 2web

## Access

To access the local webserver you must have run

	2web

as an adminstrator(sudo 2web) to enable it. Then go to

	http://localhost/

Once you login the first time you will probably want to create a adminstrative user to password lock the settings. You can then enable the modules you want to run automatically at

	https://localhost/settings/modules.php

### Settings

Nearly everything can be configured via the web interface.

	http://localhost/settings/

If you have adminstrative access to the server all the 2web settings are stored in text files in

	/etc/2web/

## Supported Systems
 - Raspbery PI 4
	 - DietPi
	 - Raspbian
	 - Raspberry Pi OS
 - x86/x64
	 - Ubuntu
	 - Debian


