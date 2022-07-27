2web
====

Generate a website for use on a LAN(Local Area Network) as a http accessable multimedia libary. 2web allows you to host multimedia content as a kodi compatible php media server using apache2 and minimal javascript. This software is designed to run on the latest version of the raspberry pi. Currently supporting a libary size of ~20k shows, ~20k movies, ~20k books, ~100 weather stations and ~100k channels/radio stations on a raspberry pi 4. This software is compatible with any Ubuntu or Debian system as well.

## Supported Module Content

 - Comics
 - Movies
 - Shows
 - Live TV
 - Live Radio
 - Weather Forcasts
 - System Graphs

## Features

 - Add web addresses to include any website or individual channel on sites supported by youtube-dl as a show
 - Add web addreses of online comics to cache them locally in your comics section
 - Light touches of Javascript
 - Mostly PHP and Bash
 - Themes
 - SOFTWARE DOES NOT TOUCH THE DATA SOURCES, everything is symlinked

## Access
To access the local webserver you must have run

	2web

to enable it. Then go to

	http://localhost/

2web takes over port 80 and 443 by default but can be changed in

	/etc/apache2/sites-available/0-2web-website.conf
	/etc/apache2/sites-available/0-2web-website-SSL.conf


### Settings

Web interface contains settings for adding locally stored server paths to content and ways to add remote content metadata and links to the server.

## CLI

The CLI can be used by adminstrators on the server to update various content stacks on the server.

To build the backbone of the webserver run

	2web

This will enable the server and place all base webpages to allow access to the web interface.

The master interface can update all modules in one command.

	2web all

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
| weather | ✔️        | ✔️            | ✔️          |
| graph   | ❌       | ✔️            | ✔️          |
| kodi    | ❌       | ❌           | ✔️          |

### Resolver Components

| resolvers     | Web Settings | /etc/2web/ |
|---------------|--------------|------------|
| ytdl-resolver | ✔️            | ✔️          |
| m3u-gen       | ❌           | ❌         |
| iptv-resolver | ❌           | ❌         |
