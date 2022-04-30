2web
====

Generate a website for use on a LAN(Local Area Network) as a http accessable multimedia libary for content. 2web allows you to host multimedia content as a kodi compatible php media server using apache2. This software is designed to run on the latest version of the raspberry pi. Currently supporting a libary size of ~20k shows,movies,books, and ~100k channels/radio stations.

- Comics
- Movies
- Shows
- Live TV
- Live Radio
- Weather Forcasts

- Add web addresses to include any website or individual channel on sites supported by youtube-dl as a show
- Add web addreses of online comics to cache them locally in your comics section
- Light touches of Javascript
- Mostly PHP and Bash
- Themes

- Tested with dietpi os on Raspbery PI 4
- Tested with Ubuntu

## Access

	http://localhost:444/

### Settings

Web interface contains settings for adding locally stored server paths to content and ways to add remote content metadata and links to the server.

## CLI

The CLI can be used as root on the server to update various content stacks on the server.

The master interface can update everything.

	2web all

Each individual web section has its own CLI interface for running manual generation or a clean reset of a individual section. Resets may be required for missing or removed content. Sometimes websites change thier layout and the remote metadata will duplicate this can be solved by a section reset.

To update each section use the following

	nfo2web

	iptv2web

	comic2web

	weather2web

Resets can be done with the master interface by

	2web reset

and individually by

	nfo2web reset

	iptv2web reset

	comic2web reset

	weather2web reset

Finally you can delete the entire website with

	2web nuke

or individually

	nfo2web nuke

	iptv2web nuke

	comic2web nuke

	weather2web nuke


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
