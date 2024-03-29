2web
====

Your digital domain, full of hardworking [daemons](https://en.wikipedia.org/wiki/Daemon_(computing))!

2web is focused on allowing you to access content from YOUR [local network](https://en.wikipedia.org/wiki/Local_area_network) without internet access wherever possible. Designed for use with KODI, Phones, Tablets, and Desktop computers. 2web acts as your freedom/privacy/attention respecting frontend to the internet and your local media.

Add multimedia, documents, and databases to 2web for a total offline experience or add your chosen media sources to use caching components. You can even use 2web as a frontend to link to other self hosted services on your network such as Nextcloud, Pi-Hole, or Tiny Tiny RSS. 2web can be used with tailscale to access your data from anywhere on earth or in space. 2web is heavily optimized to target running on the latest raspberry pi, currently the PI 4.

- [Modules](#modules)
- [Supported Content Types](#supported-content-types)
- [Features](#features)
- [Warnings](#warnings)
- [Install With APT Repo](#install-with-apt-repo)
- [Uninstall](#uninstall)
- [Remove APT Repo](#remove-apt-repo)
- [Install From Source](#install-from-source)
- [Access](#access)
- [Settings](#settings)
- [CLI](#cli)
- [Supported Systems](#supported-systems)
- [How To Help](#how-to-help)
- [License](#license)

<span id='modules' />

## Modules

 - Modular design allows the complexity of the server to grow as your use does
 - By DEFAULT all modules are disabled and all settings are hidden to reduce learning curve for new users
 - Enable/Disable modules to show/hide settings and pages related to the content types you want on the server

<span id='supported-content-types' />

## Supported Content Types

 - Movies
 - Shows
 - Podcasts
 - Music
 - Live TV
 - Live Radio
 - Books
 - Comics
 - Wikis
 - Git Repositories
 - System Graphs
 - Weather Forecasts
 - Text Prompting AI
 - Image Generating AI

<span id='features' />

## Features

 - NO API KEYS REQUIRED
 - Written in [PHP](https://www.php.net), [Bash](https://www.gnu.org/software/bash/), and [Javascript](https://en.wikipedia.org/wiki/JavaScript)
 - WEB Access and Administration interface to all hosted data, compatible with PHONES, TABLETS, and DESKTOP
 - CLI (Command line interface) with man pages
 - Offline Search for ALL hosted data, with external search links
 - Offline dictionary search results
 - Add Video/Audio RSS feeds as shows
 - Weather info via [weather-util](http://fungi.yuggoth.org/weather/) WITHOUT NEED FOR AN API KEY thanks to METAR data from the National Oceanic and Atmospheric Administration and forecasts from the National Weather Service.
 - Add wikis from locally downloaded [ZIM](https://wiki.openzim.org/wiki/OpenZIM) files
 - Search all local wiki articles with 2web search.
 - Generate reports for local and remote [GIT](https://en.wikipedia.org/wiki/Git) repositories.
 - Generate graphs for commits and add/removed lines for git repositories.
 - Create [Gource](https://gource.io/) videos for each git repository and a combined [Gource](https://gource.io/) video of all repositories.
 - Generate lint reports for source code in git repositories.
 - Build [Gitinspector](https://github.com/ejwa/gitinspector) data for each git repository.
 - Build documentation for git repos with docstrings for [Python3](https://python.org), [BASH](https://www.gnu.org/software/bash/), and [PHP](https://www.php.net).
 - Add web addresses to include any website or individual user channel on sites supported by ([yt-dlp](https://github.com/yt-dlp/yt-dlp)/[youtube-dl](https://ytdl-org.github.io/youtube-dl/index.html)) as a show in the media library.
 - Add web addresses of online comics to cache them locally in your comics section with [gallery-dl](https://github.com/mikf/gallery-dl)
 - Add book collections containing .cbz .txt .pdf .zip or simple folders containing image files.
 - Add music collections and music is auto-sorted based on [ID3](https://en.wikipedia.org/wiki/ID3) tag information
 - Add any livestream web address as a live channel using [streamlink](https://streamlink.github.io)
 - Add multiple [IPTV](https://en.wikipedia.org/wiki/Internet_Protocol_television) playlists to merge them on the server.
 - Generate a web interface for the merged IPTV playlist.
 - Lots of included themes and custom themes can be installed with a single [CSS](https://en.wikipedia.org/wiki/CSS) file.
 - Opendyslexic font themes for accessibility.
 - Flat CSS themes for supporting older devices accessing the web interface of the server.
 - Direct Links to hosted media for playback with player of your choice.
 - [VLC](https://www.videolan.org/vlc/) links to immediately start playback in [VLC](https://www.videolan.org/vlc/) from the webpage on mobile.
 - Shuffle play all movies from the web interface with exernal player using [M3U](https://en.wikipedia.org/wiki/M3U) files for compability.
 - Continue playback with external player for watching a series to the end from the current episode.
 - Shuffle play a series with a external player.
 - Chapter support for videos with [SponsorBlock](https://sponsor.ajay.app/)
 - Lock the settings interface by adding at least one administrative user.
 - SOFTWARE DOES NOT TOUCH THE DATA SOURCES, everything is symlinked.
 - No RAID array, no problem. You can add multiple paths for libraries to expand your sources as you build your server.
 - NO phoning home, 2web can be isolated completely from the internet and local content will still be accessible
 - Opensearch compatible so you can add 2web local search as a search engine to web browsers supporting the opensearch standard.
 - Generate song lyrics and video subtitles with AI.
 - Prompt AI models for text responses locally with [GPT4All](https://gpt4all.io/).
 - Generate AI images locally with [diffusers](https://github.com/huggingface/diffusers).
 - Prompt multiple AI models at once for more varied results.
 - Add [Munin](https://munin-monitoring.org/) graphs to the web interface.
 - Generate a activity graph for 2web modules.
 - A unified 2web server log.
 - Generate a portal of links to other servers and sevices.
 - Scan ports and open web paths for services on remote servers and generate links in the web portal section. For adding links to other servers on your LAN to your 2web server or non intergrated services on the same server as 2web.
 - Optimised KODI client library updates to only scan paths containing new content without special client configuration.
 - Connect KODI clients so 2web will launch client updates when new content is detected on the server.
 - Media and cache statistics on the server homepage.
 - Combined updated and random playlists in the web interface.
 - Individual playlists for each type of content.
 - KODI remote control via JSON RPC API
 - "Play On Kodi" links for movies and show episodes allows you to play content on remote kodi clients.
 - KODI remote control allows you to play content from the server without adding the server sources to the client.
 - KODI web remote controller allows you to control a KODI client.

<span id='warnings' />

## Warnings

 - 2web is designed to be only accessable on your LAN.
 - Do not expose this server to the open internet.
 - Do not use it unless behind a firewall.
 - AI prompting can take a very long time even on good hardware.
 - Global search will become slower as more content is added.

<span id='install-with-apt-repo' />

## Install with APT Repo

This is a APT repo that is designed work like a PPA. This means it only contains and will only update the 2web software package. The first two commands will add the repo and the repo encryption key. The third line will update your packages to include the new repo. The last line installs the package and will ask for verification.

	sudo wget -q -O '/etc/apt/trusted.gpg.d/2web_ppa.gpg' 'https://dude56987.github.io/2web_ppa/2web_ppa.gpg'
	sudo wget -q -O '/etc/apt/sources.list.d/2web_ppa.list' 'https://dude56987.github.io/2web_ppa/2web_ppa.list'
	sudo apt update
	sudo apt-get install 2web

Or if you use curl instead of wget

	sudo curl -SsL --compressed -o '/etc/apt/trusted.gpg.d/2web_ppa.gpg' 'https://dude56987.github.io/2web_ppa/2web_ppa.gpg'
	sudo curl -SsL --compressed -o '/etc/apt/sources.list.d/2web_ppa.list' 'https://dude56987.github.io/2web_ppa/2web_ppa.list'
	sudo apt update
	sudo apt-get install 2web

<span id='uninstall' />

## Uninstall

The install process creates and installs a .deb package file. So you can uninstall the software with the system package manager.

	sudo apt-get remove 2web

If you would like to purge config files generated use

	sudo apt-get purge 2web

<span id='remove-apt-repo' />

## Remove APT repo

If you installed the APT repo and want to remove it completely from your sources you can use the below commands. You will be prompted to confirm before removal of each of the two files.

	sudo rm -i -v '/etc/apt/trusted.gpg.d/2web_ppa.gpg'
	sudo rm -i -v '/etc/apt/sources.list.d/2web_ppa.list'

<span id='install-from-source' />

## Install from Source

Copy and extract the source then in the extracted path run in a terminal

	./configure
	make
	make install

This should build and install the package on any Debian or Ubuntu based system. Installing in this way you will not receive security or feature updates unless you have also added the APT repo.

<span id='access' />

## Access

To access the web interface on the machine you have installed it on, go to

	http://localhost/

In the top right corner of the webpage click the login button. If no login button exists click the encrypt button to switch to HTTPS. You may have to accept the custom SSL certificate used to encrypt the connection. Once you login the first time you will probably want to create a administrative user to password lock the settings. You can then enable the modules you want to run automatically in

	http://localhost/settings/

If you are on another computer on the local network you can use zeroconf to access by the servers hostname using the .local domain.

	http://hostname.local/

be sure to replace hostname with the hostname you have given the server.

<span id='settings' />

### Settings

Nearly everything can be configured via the web interface.

	http://localhost/settings/

If you have direct access to the server all the 2web settings are stored in text files in

	/etc/2web/

<span id='cli' />

## CLI

On the command line interface you can view the status of modules with

	2web status

To enable a module, for example nfo2web you would use

	nfo2web enable

To disable the same module

	nfo2web disable

If a module is disabled it will cleanup and remove that web section on the next update. To remove all module content manually you can use

	nfo2web nuke

To generate content for the module simply run that module

	nfo2web

Most modules have a option to run the update of its content in parallel with

	nfo2web --parallel

Module commands also have manual pages that can be accessed with

	man nfo2web

or

	nfo2web --help

if your system does not have the man command. If you want to run all enabled modules at once the 2web command acts as a master interface.

	2web update

You can also run all modules in parallel with

	2web parallel

If things need reset completely you can run

	2web nuke

however be advised this will remove everything and require you to redownload thumbnails. No source data will be removed but all generated content will need to be recreated.

<span id='supported-systems' />

## Supported Systems

 - The 2web server will run on any debian or ubuntu based linux system but is tested on
   - Raspbery PI 4
   - DietPi
   - Raspbian
 - Raspberry Pi OS
   - x86/x64
   - Ubuntu
   - Debian
 - The 2web service is available on any devices that can run a webbrowser but is tested on
   - Firefox
   - Brave

<span id='how-to-help' />

## How to Help

 - Use 2web in your homelab
 - Share 2web with others
   - [https://github.com/dude56987/2web](https://github.com/dude56987/2web)

<span id='license' />

## License

[GPL3](./LICENSE)
