########################################################################
# nfo2web makefile
# Copyright (C) 2016  Carl J Smith
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
########################################################################
all: build
	echo "Build the package for use with auto tools (configure,make,make install)"
show:
	echo 'Run "make install" as root to install program!'
test-comics: install
	sudo comic2web webgen
test-live: install
	sudo time -v iptv2web reset || echo "no reset needed..."
	sudo time -v iptv2web update
test-update-live: install
	sudo time -v iptv2web update
test-ondemand: install
	sudo time -v 2web reset || echo "no reset needed..."
	sudo time -v 2web update
test-update-ondemand: install
	sudo time -v 2web update
test: install
	sudo time -v 2web --parallel
lint:
	# check the templates
	php -l templates/*.php
	# check the settings
	php -l settings/*.php
	# check the transcoders/resolvers
	php -l resolvers/*.php
	# check the shell scripts
	shellcheck 2web.sh || echo "Errors Found..."
	shellcheck *2web.sh || echo "Errors Found..."
	# check the package
	lintian 2web_UNSTABLE.deb
	# check the package in detail
	lintian -a 2web_UNSTABLE.deb
install: build
	sudo gdebi -n 2web_UNSTABLE.deb
uninstall:
	sudo apt-get purge 2web
uninstall-broken:
	sudo dpkg --remove --force-remove-reinstreq 2web
installed-size:
	du -sx --exclude DEBIAN ./debian/
debugOn:
	sudo mv /etc/2web/nfo/debug.disabled /etc/2web/nfo/debug.enabled
debugOff:
	sudo mv /etc/2web/nfo/debug.enabled /etc/2web/nfo/debug.disabled
downloadable: 2web_UNSTABLE.deb
	cp 2web_UNSTABLE.deb /var/cache/2web/web/kodi/2web_install.deb
	cp 2web_UNSTABLE.tar.gz /var/cache/2web/web/kodi/2web.tar.gz
build: build-deb
	# install the build tools
	sudo make build-deb;
upgrade-hls:
	if ! test -f node_modules/hls.js/dist/hls.js;then npm install --save hls.js;fi
build-deb: upgrade-hls
	# build the directories inside the package
	mkdir -p debian;
	mkdir -p debian/DEBIAN;
	mkdir -p debian/usr;
	mkdir -p debian/usr/bin;
	mkdir -p debian/usr/share/applications;
	mkdir -p debian/usr/share/2web/nfo;
	mkdir -p debian/usr/share/2web/iptv;
	mkdir -p debian/usr/share/2web/;
	mkdir -p debian/usr/share/2web/resolvers/;
	mkdir -p debian/usr/share/2web/help/;
	mkdir -p debian/usr/share/man/man1/;
	mkdir -p debian/usr/share/2web/themes;
	mkdir -p debian/usr/share/2web/theme-templates;
	mkdir -p debian/usr/share/2web/templates;
	mkdir -p debian/usr/share/2web/settings;
	mkdir -p debian/var/cache/2web/cache;
	mkdir -p debian/etc;
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/cache;
	mkdir -p debian/etc/2web/themes;
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/config_default/;
	#mkdir -p debian/etc/2web/search/
	#mkdir -p debian/etc/2web/search/sources.d/
	mkdir -p debian/etc/2web/wiki/
	mkdir -p debian/etc/2web/wiki/libraries.d/
	mkdir -p debian/etc/2web/ytdl/
	mkdir -p debian/etc/2web/ytdl/sources.d/
	mkdir -p debian/etc/2web/ytdl/usernameSources.d/
	mkdir -p debian/etc/2web/users/;
	mkdir -p debian/etc/2web/groups/;
	mkdir -p debian/etc/2web/lockedGroups/;
	mkdir -p debian/etc/2web/nfo/;
	mkdir -p debian/etc/2web/nfo/libaries.d/;
	mkdir -p debian/etc/2web/nfo/disabledLibaries.d/;
	mkdir -p debian/etc/2web/music/;
	mkdir -p debian/etc/2web/music/libaries.d/;
	mkdir -p debian/etc/2web/ai/negative_prompts/;
	mkdir -p debian/etc/2web/ai/promptModels.d/;
	mkdir -p debian/etc/2web/applications/;
	mkdir -p debian/etc/2web/applications/libaries.d/;
	mkdir -p debian/etc/2web/comics/;
	mkdir -p debian/etc/2web/comics/libaries.d/;
	mkdir -p debian/etc/2web/comics/sources.d/;
	mkdir -p debian/etc/2web/comics/webSources.d/;
	mkdir -p debian/etc/2web/repos/;
	mkdir -p debian/etc/2web/repos/sources.d/;
	mkdir -p debian/etc/2web/repos/libaries.d/;
	mkdir -p debian/etc/2web/graph/;
	mkdir -p debian/etc/2web/iptv/;
	mkdir -p debian/etc/2web/iptv/sources.d/;
	mkdir -p debian/etc/2web/iptv/blockedGroups.d/;
	mkdir -p debian/etc/2web/iptv/radioSources.d/;
	mkdir -p debian/etc/2web/iptv/blockedLinks.d/;
	mkdir -p debian/etc/2web/weather/;
	mkdir -p debian/etc/2web/weather/location.d/;
	mkdir -p debian/etc/2web/rss/;
	mkdir -p debian/etc/2web/rss/sources.d/;
	mkdir -p debian/etc/2web/kodi/locations.d/;
	mkdir -p debian/etc/2web/kodi/players.d/;
	mkdir -p debian/etc/cron.d/;
	mkdir -p debian/etc/apache2/;
	mkdir -p debian/etc/apache2/sites-available/;
	mkdir -p debian/etc/apache2/sites-enabled/;
	mkdir -p debian/etc/apache2/conf-available/;
	mkdir -p debian/etc/apache2/conf-enabled/;
	mkdir -p debian/etc/bash_completion.d/;
	mkdir -p debian/etc/avahi/;
	mkdir -p debian/etc/avahi/services/;
	mkdir -p debian/etc/2web/portal/;
	mkdir -p debian/var/lib/2web/;
	# create the ufw applications profile directory
	mkdir -p debian/etc/ufw/applications.d/
	# copy the license over to the webserver to include it in about page and in CLI tools
	cp -v LICENSE debian/usr/share/2web/
	# copy templates over
	cp -rv templates/. debian/usr/share/2web/templates/
	# copy over default config templates
	cp -rv config_default/. debian/etc/2web/config_default/
	# add icon
	cp -rv 2web_icon.png debian/usr/share/2web/favicon_default.png
	# make placeholder
	#touch debian/etc/2web/search/sources.d/.placeholder
	touch debian/etc/2web/cache/.placeholder
	touch debian/etc/2web/ai/promptModels.d/.placeholder
	touch debian/etc/2web/repos/sources.d/.placeholder
	touch debian/etc/2web/repos/libaries.d/.placeholder
	touch debian/etc/2web/wiki/libraries.d/.placeholder
	touch debian/etc/2web/ytdl/sources.d/.placeholder
	touch debian/etc/2web/ytdl/usernameSources.d/.placeholder
	touch debian/etc/2web/iptv/.placeholder
	touch debian/etc/2web/iptv/sources.d/.placeholder
	touch debian/etc/2web/iptv/blockedGroups.d/.placeholder
	touch debian/etc/2web/iptv/radioSources.d/.placeholder
	touch debian/etc/2web/iptv/blockedLinks.d/.placeholder
	touch debian/etc/2web/nfo/.placeholder
	touch debian/etc/2web/music/libaries.d/.placeholder
	touch debian/etc/2web/nfo/libaries.d/.placeholder
	touch debian/etc/2web/nfo/disabledLibaries.d/.placeholder
	touch debian/etc/2web/applications/.placeholder
	touch debian/etc/2web/applications/libaries.d/.placeholder
	touch debian/etc/2web/comics/.placeholder
	touch debian/etc/2web/comics/libaries.d/.placeholder
	touch debian/etc/2web/comics/sources.d/.placeholder
	touch debian/etc/2web/weather/location.d/.placeholder
	touch debian/etc/2web/rss/sources.d/.placeholder
	touch debian/etc/2web/kodi/locations.d/.placeholder
	touch debian/etc/2web/kodi/players.d/.placeholder
	touch debian/var/cache/2web/cache/.placeholder
	touch debian/usr/share/2web/settings/.placeholder
	touch debian/usr/share/2web/themes/.placeholder
	touch debian/etc/2web/users/.placeholder
	touch debian/etc/2web/groups/.placeholder
	touch debian/etc/2web/portal/.placeholder
	# fix ownership
	chown -R www-data:www-data debian/usr/share/2web/theme-templates/
	chown -R www-data:www-data debian/etc/2web/users/
	chown -R www-data:www-data debian/etc/2web/groups/
	chown -R www-data:www-data debian/etc/2web/lockedGroups/
	chown -R www-data:www-data debian/etc/2web/ytdl/*.d/
	chown -R www-data:www-data debian/etc/2web/iptv/*.d/
	chown -R www-data:www-data debian/etc/2web/nfo/*.d/
	chown -R www-data:www-data debian/etc/2web/comics/*.d/
	chown -R www-data:www-data debian/etc/2web/applications/*.d/
	chown -R www-data:www-data debian/etc/2web/weather/*.d/
	chown -R www-data:www-data debian/etc/2web/ai/negative_prompts/
	chown -R www-data:www-data debian/etc/2web/portal/
	#chown -R www-data:www-data debian/etc/2web/ai/
	#chown -R www-data:www-data debian/etc/2web/*.d/
	chown -R www-data:www-data debian/etc/2web/*/*.d/
	chown -R www-data:www-data debian/etc/2web/
	# copy the certInfo default script
	cp certInfo.cnf debian/etc/2web/
	# add the base bash lib used across all modules
	echo "#! /bin/bash" > debian/var/lib/2web/common
	cat build/sh_head.txt > debian/var/lib/2web/common
	# remove all comment lines from the code to reduce package size and disk read on execution
	grep --invert-match "^[[:blank:]]*#" 2webLib.sh | tr -s '\n' >> debian/var/lib/2web/common
	# python common lib for all python based tools
	echo "#! /bin/python3" > debian/usr/share/2web/python2webLib.py
	cat build/py_head.txt > debian/usr/share/2web/python2webLib.py
	# remove all comment lines from the code to reduce package size and disk read on execution
	grep --invert-match "^[[:blank:]]*#" 2webLib.py | tr -s '\n' >> debian/usr/share/2web/python2webLib.py
	# copy update scripts to /usr/bin
	echo "#! /bin/bash" > debian/usr/bin/2web
	cat build/sh_head.txt > debian/usr/bin/2web
	grep --invert-match "^[[:blank:]]*#" 2web.sh | tr -s '\n' >> debian/usr/bin/2web
	# add the queue system
	echo "#! /bin/bash" > debian/usr/bin/queue2web
	cat build/sh_head.txt > debian/usr/bin/queue2web
	grep --invert-match "^[[:blank:]]*#" queue2web.sh | tr -s '\n' >> debian/usr/bin/queue2web
	################################################################################
	# build ai prompt tools
	################################################################################
	# build the text prompt
	echo "#! /usr/bin/python3" > debian/usr/bin/ai2web_prompt
	cat build/py_head.txt > debian/usr/bin/ai2web_prompt
	grep --invert-match "^[[:blank:]]*#" ai2web_prompt.py | tr -s '\n' >> debian/usr/bin/ai2web_prompt
	# txt2img
	echo "#! /usr/bin/python3" > debian/usr/bin/ai2web_txt2img
	cat build/py_head.txt > debian/usr/bin/ai2web_txt2img
	grep --invert-match "^[[:blank:]]*#" ai2web_txt2img.py | tr -s '\n' >> debian/usr/bin/ai2web_txt2img
	# txt2txt
	echo "#! /usr/bin/python3" > debian/usr/bin/ai2web_txt2txt
	cat build/py_head.txt > debian/usr/bin/ai2web_txt2txt
	grep --invert-match "^[[:blank:]]*#" ai2web_txt2txt.py | tr -s '\n' >> debian/usr/bin/ai2web_txt2txt
	# q2a
	echo "#! /usr/bin/python3" > debian/usr/bin/ai2web_q2a
	cat build/py_head.txt > debian/usr/bin/ai2web_q2a
	grep --invert-match "^[[:blank:]]*#" ai2web_q2a.py | tr -s '\n' >> debian/usr/bin/ai2web_q2a
	# img2img
	echo "#! /usr/bin/python3" > debian/usr/bin/ai2web_img2img
	cat build/py_head.txt > debian/usr/bin/ai2web_img2img
	grep --invert-match "^[[:blank:]]*#" ai2web_img2img.py | tr -s '\n' >> debian/usr/bin/ai2web_img2img
	# 2web_search global search helper
	echo "#! /bin/bash" > debian/usr/bin/2web_search
	cat build/sh_head.txt > debian/usr/bin/2web_search
	grep --invert-match "^[[:blank:]]*#" 2web_search.sh | tr -s '\n' >> debian/usr/bin/2web_search
	# 2web_client global search helper
	echo "#! /bin/bash" > debian/usr/bin/2web_client
	cat build/sh_head.txt > debian/usr/bin/2web_client
	grep --invert-match "^[[:blank:]]*#" 2web_client.sh | tr -s '\n' >> debian/usr/bin/2web_client
	# build the shell scripts
	# - add the gpl header on the top
	# - copy the file but remove the comments and blank lines
	echo "#! /bin/bash" > debian/usr/bin/ai2web
	cat build/sh_head.txt > debian/usr/bin/ai2web
	grep --invert-match "^[[:blank:]]*#" ai2web.sh | tr -s '\n' >> debian/usr/bin/ai2web
	echo "#! /bin/bash" > debian/usr/bin/wiki2web
	cat build/sh_head.txt > debian/usr/bin/wiki2web
	grep --invert-match "^[[:blank:]]*#" wiki2web.sh | tr -s '\n' >> debian/usr/bin/wiki2web
	echo "#! /bin/bash" > debian/usr/bin/nfo2web
	cat build/sh_head.txt > debian/usr/bin/nfo2web
	grep --invert-match "^[[:blank:]]*#" nfo2web.sh | tr -s '\n' >> debian/usr/bin/nfo2web
	echo "#! /bin/bash" > debian/usr/bin/portal2web
	cat build/sh_head.txt > debian/usr/bin/portal2web
	grep --invert-match "^[[:blank:]]*#" portal2web.sh | tr -s '\n' >> debian/usr/bin/portal2web
	echo "#! /bin/bash" > debian/usr/bin/git2web
	cat build/sh_head.txt > debian/usr/bin/git2web
	grep --invert-match "^[[:blank:]]*#" git2web.sh | tr -s '\n' >> debian/usr/bin/git2web
	echo "#! /bin/bash" > debian/usr/bin/music2web
	cat build/sh_head.txt > debian/usr/bin/music2web
	grep --invert-match "^[[:blank:]]*#" music2web.sh | tr -s '\n' >> debian/usr/bin/music2web
	echo "#! /bin/bash" > debian/usr/bin/iptv2web
	cat build/sh_head.txt > debian/usr/bin/iptv2web
	grep --invert-match "^[[:blank:]]*#" iptv2web.sh | tr -s '\n' >> debian/usr/bin/iptv2web
	echo "#! /bin/bash" > debian/usr/bin/comic2web
	cat build/sh_head.txt > debian/usr/bin/comic2web
	grep --invert-match "^[[:blank:]]*#" comic2web.sh | tr -s '\n' >> debian/usr/bin/comic2web
	echo "#! /bin/bash" > debian/usr/bin/graph2web
	cat build/sh_head.txt > debian/usr/bin/graph2web
	grep --invert-match "^[[:blank:]]*#" graph2web.sh | tr -s '\n' >> debian/usr/bin/graph2web
	echo "#! /bin/bash" > debian/usr/bin/kodi2web
	cat build/sh_head.txt > debian/usr/bin/kodi2web
	grep --invert-match "^[[:blank:]]*#" kodi2web.sh | tr -s '\n' >> debian/usr/bin/kodi2web
	cat build/sh_head.txt > debian/usr/bin/kodi2web_player
	grep --invert-match "^[[:blank:]]*#" kodi2web_player.sh | tr -s '\n' >> debian/usr/bin/kodi2web_player
	echo "#! /bin/bash" > debian/usr/bin/weather2web
	cat build/sh_head.txt > debian/usr/bin/weather2web
	grep --invert-match "^[[:blank:]]*#" weather2web.sh | tr -s '\n' >> debian/usr/bin/weather2web
	echo "#! /bin/bash" > debian/usr/bin/ytdl2nfo
	cat build/sh_head.txt > debian/usr/bin/ytdl2nfo
	grep --invert-match "^[[:blank:]]*#" ytdl2nfo.sh | tr -s '\n' >> debian/usr/bin/ytdl2nfo
	echo "#! /bin/bash" > debian/usr/bin/rss2nfo
	cat build/sh_head.txt > debian/usr/bin/rss2nfo
	grep --invert-match "^[[:blank:]]*#" rss2nfo.sh | tr -s '\n' >> debian/usr/bin/rss2nfo
	echo "#! /bin/bash" > debian/usr/bin/php2web
	cat build/sh_head.txt > debian/usr/bin/php2web
	grep --invert-match "^[[:blank:]]*#" php2web.sh | tr -s '\n' >> debian/usr/bin/php2web
	# build the man pages for the command line tools
	pandoc --standalone help/man_2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/2web.1.gz
	# build the web versions of the man page
	pandoc help/man_2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/2web.html
	# build the text only render of the manual
	w3m debian/usr/share/2web/help/2web.html > debian/usr/share/2web/help/2web.txt
	################################################################################
	# build the nfo2web manual pages
	################################################################################
	pandoc --standalone help/man_nfo2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/nfo2web.1.gz
	pandoc help/man_nfo2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/nfo2web.html
	w3m debian/usr/share/2web/help/nfo2web.html > debian/usr/share/2web/help/nfo2web.txt
	################################################################################
	# build the iptv2web manual pages
	################################################################################
	pandoc --standalone help/man_iptv2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/iptv2web.1.gz
	pandoc help/man_iptv2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/iptv2web.html
	w3m debian/usr/share/2web/help/iptv2web.html > debian/usr/share/2web/help/iptv2web.txt
	################################################################################
	# build the comic2web manual pages
	################################################################################
	pandoc --standalone help/man_comic2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/comic2web.1.gz
	pandoc help/man_comic2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/comic2web.html
	w3m debian/usr/share/2web/help/comic2web.html > debian/usr/share/2web/help/comic2web.txt
	################################################################################
	# build the ytdl2nfo manual pages
	################################################################################
	pandoc --standalone help/man_ytdl2nfo.md help/man_footer.md -t man -o debian/usr/share/man/man1/ytdl2nfo.1.gz
	pandoc help/man_ytdl2nfo.md help/man_footer.md -t html -o debian/usr/share/2web/help/ytdl2nfo.html
	w3m debian/usr/share/2web/help/ytdl2nfo.html > debian/usr/share/2web/help/ytdl2nfo.txt
	################################################################################
	# build the weather2web manual pages
	################################################################################
	pandoc --standalone help/man_weather2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/weather2web.1.gz
	pandoc help/man_weather2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/weather2web.html
	w3m debian/usr/share/2web/help/weather2web.html > debian/usr/share/2web/help/weather2web.txt
	################################################################################
	# build the music2web manual pages
	################################################################################
	pandoc --standalone help/man_music2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/music2web.1.gz
	pandoc help/man_music2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/music2web.html
	w3m debian/usr/share/2web/help/music2web.html > debian/usr/share/2web/help/music2web.txt
	################################################################################
	# build the graph2web manual pages
	################################################################################
	pandoc --standalone help/man_graph2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/graph2web.1.gz
	pandoc help/man_graph2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/graph2web.html
	w3m debian/usr/share/2web/help/graph2web.html > debian/usr/share/2web/help/graph2web.txt
	################################################################################
	# build the git2web manual pages
	################################################################################
	pandoc --standalone help/man_git2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/git2web.1.gz
	pandoc help/man_git2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/git2web.html
	w3m debian/usr/share/2web/help/git2web.html > debian/usr/share/2web/help/git2web.txt
	################################################################################
	# build the portal2web manual pages
	################################################################################
	#pandoc --standalone help/man_portal2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/portal2web.1.gz
	#pandoc help/man_portal2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/portal2web.html
	#w3m debian/usr/share/2web/help/portal2web.html > debian/usr/share/2web/help/portal2web.txt
	################################################################################
	# build the ai2web manual pages
	################################################################################
	#pandoc --standalone help/man_ai2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/ai2web.1.gz
	#pandoc help/man_ai2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/ai2web.html
	#w3m debian/usr/share/2web/help/ai2web.html > debian/usr/share/2web/help/ai2web.txt
	################################################################################
	# build the rss2nfo manual pages
	################################################################################
	#pandoc --standalone help/man_rss2nfo.md help/man_footer.md -t man -o debian/usr/share/man/man1/rss2nfo.1.gz
	#pandoc help/man_rss2nfo.md help/man_footer.md -t html -o debian/usr/share/2web/help/rss2nfo.html
	#w3m debian/usr/share/2web/help/rss2nfo.html > debian/usr/share/2web/help/rss2nfo.txt
	################################################################################
	# build the php2web manual pages
	################################################################################
	#pandoc --standalone help/man_rss2nfo.md help/man_footer.md -t man -o debian/usr/share/man/man1/php2web.1.gz
	#pandoc help/man_rss2nfo.md help/man_footer.md -t html -o debian/usr/share/2web/help/php2web.html
	#w3m debian/usr/share/2web/help/php2web.html > debian/usr/share/2web/help/php2web.txt
	################################################################################
	# build the readme manual page
	################################################################################
	pandoc --standalone README.md help/man_footer.md -t man -o debian/usr/share/man/man1/2web_help.1.gz
	pandoc README.md help/man_footer.md -t html -o debian/usr/share/2web/help/README.html
	w3m debian/usr/share/2web/help/README.html > debian/usr/share/2web/help/README.txt
	# copy over the theme templates
	cp -v themes/*.css debian/usr/share/2web/theme-templates/
	# copy over the disabled themes
	cp -v themes/*.disabled debian/usr/share/2web/theme-templates/
	# get the latest hls.js from npm and include it in the package
	cp -v node_modules/hls.js/dist/hls.js debian/usr/share/2web/iptv/hls.js
	cp -v node_modules/hls.js/dist/hls.js debian/usr/share/2web/hls.js
	# copy over the main javascript libary
	cp 2webLib.js debian/usr/share/2web/
	# copy over the main php libary
	cp 2webLib.php debian/usr/share/2web/
	# copy over the settings pages
	cp settings/*.php debian/usr/share/2web/settings/
	# copy the resolvers over
	cp resolvers/*.php debian/usr/share/2web/resolvers/
	# copy over the .desktop launcher file to place link in system menus
	cp 2web.desktop debian/usr/share/applications/
	# make the script executable only by root
	chmod u+rwx debian/usr/bin/*
	chmod go-rwx debian/usr/bin/*
	# fix permissions for helper applications that are used by the webserver
	chmod u+rwx debian/usr/bin/*_*
	chmod go-w debian/usr/bin/*_*
	chmod go+x debian/usr/bin/*_*
	# copy over the cron job
	cp 2web.cron debian/usr/share/2web/cron
	# copy over apache configs
	cp -v systemConf/0000-2web-website.conf debian/etc/apache2/sites-available/
	cp -v systemConf/0000-2web-website-SSL.conf debian/etc/apache2/sites-available/
	# copy over the zeroconf configs to anounce the service
	cp -v systemConf/zeroconf_http.service debian/etc/avahi/services/2web_http.service
	#cp -v systemConf/zeroconf_https.service debian/etc/avahi/services/2web_https.service
	# copy over the 2web ufw firewall app profile settings
	cp -v systemConf/ufw_app_profile.ini debian/etc/ufw/applications.d/2web_server
	# copy over bash tab completion scripts
	cp -v tab_complete/* debian/etc/bash_completion.d/
	# write version info last thing before the build process of the package
	# this also makes the build date time more correct since the package is
	# built but is now being compressed and converted into a debian package
	###################
	# set the build directory as safe for git
	git config --global --add safe.directory "$$PWD"
	# create the 2web version info special flags
	if /usr/bin/git status| grep "Changes not staged for";then echo -n "+UNSTABLE+ " >> debian/usr/share/2web/version.cfg;fi
	if /usr/bin/git status| grep "Changes to be committed";then echo -n "+TESTING+ " >> debian/usr/share/2web/version.cfg;fi
	# add the mark ahead of the version number
	echo -n "#" >> debian/usr/share/2web/version.cfg
	# write the version number
	/usr/bin/git log --oneline | wc -l | cut -f1 >> debian/usr/share/2web/simple_version.cfg
	/usr/bin/git log --oneline | wc -l | cut -f1 > simple_version.txt
	# Write the simple version number to the complex version name, it includes unstable and testing flags
	cat debian/usr/share/2web/simple_version.cfg >> debian/usr/share/2web/version.cfg
	# create each modules version info
	echo -n "#" > debian/usr/share/2web/version_2web.cfg
	/usr/bin/git log --stat | grep "^ 2web.sh" | wc -l >> debian/usr/share/2web/version_2web.cfg
	echo -n "#" > debian/usr/share/2web/version_nfo2web.cfg
	/usr/bin/git log --stat | grep "^ nfo2web.sh" | wc -l >> debian/usr/share/2web/version_nfo2web.cfg
	echo -n "#" > debian/usr/share/2web/version_portal2web.cfg
	/usr/bin/git log --stat | grep "^ portal2web.sh" | wc -l >> debian/usr/share/2web/version_portal2web.cfg
	echo -n "#" > debian/usr/share/2web/version_comic2web.cfg
	/usr/bin/git log --stat | grep "^ comic2web.sh" | wc -l >> debian/usr/share/2web/version_comic2web.cfg
	echo -n "#" > debian/usr/share/2web/version_weather2web.cfg
	/usr/bin/git log --stat | grep "^ weather2web.sh" | wc -l >> debian/usr/share/2web/version_weather2web.cfg
	echo -n "#" > debian/usr/share/2web/version_music2web.cfg
	/usr/bin/git log --stat | grep "^ music2web.sh" | wc -l >> debian/usr/share/2web/version_music2web.cfg
	echo -n "#" > debian/usr/share/2web/version_iptv2web.cfg
	/usr/bin/git log --stat | grep "^ iptv2web.sh" | wc -l >> debian/usr/share/2web/version_iptv2web.cfg
	echo -n "#" > debian/usr/share/2web/version_graph2web.cfg
	/usr/bin/git log --stat | grep "^ graph2web.sh" | wc -l >> debian/usr/share/2web/version_graph2web.cfg
	echo -n "#" > debian/usr/share/2web/version_ytdl2nfo.cfg
	/usr/bin/git log --stat | grep "^ ytdl2nfo.sh" | wc -l >> debian/usr/share/2web/version_ytdl2nfo.cfg
	echo -n "#" > debian/usr/share/2web/version_wiki2web.cfg
	/usr/bin/git log --stat | grep "^ wiki2web.sh" | wc -l >> debian/usr/share/2web/version_wiki2web.cfg
	echo -n "#" > debian/usr/share/2web/version_ai2web.cfg
	/usr/bin/git log --stat | grep "^ ai2web.sh" | wc -l >> debian/usr/share/2web/version_ai2web.cfg
	echo -n "#" > debian/usr/share/2web/version_rss2nfo.cfg
	/usr/bin/git log --stat | grep "^ rss2nfo.sh" | wc -l >> debian/usr/share/2web/version_rss2nfo.cfg
	echo -n "#" > debian/usr/share/2web/version_php2web.cfg
	/usr/bin/git log --stat | grep "^ php2web.sh" | wc -l >> debian/usr/share/2web/version_php2web.cfg
	echo -n "#" > debian/usr/share/2web/version_git2web.cfg
	/usr/bin/git log --stat | grep "^ git2web.sh" | wc -l >> debian/usr/share/2web/version_git2web.cfg
	echo -n "#" > debian/usr/share/2web/version_queue2web.cfg
	/usr/bin/git log --stat | grep "^ queue2web.sh" | wc -l >> debian/usr/share/2web/version_queue2web.cfg
	echo -n "#" > debian/usr/share/2web/version_kodi2web.cfg
	/usr/bin/git log --stat | grep "^ kodi2web.sh" | wc -l >> debian/usr/share/2web/version_kodi2web.cfg
	# version date of creation
	/usr/bin/git log -1 | grep "Date:" | tr -s ' ' | cut -d' ' -f2- > debian/usr/share/2web/versionDate.cfg
	date > debian/usr/share/2web/buildDate.cfg
	# Create the md5sums file
	find ./debian/ -type f -print0 | xargs -0 md5sum > ./debian/DEBIAN/md5sums
	# cut filenames of extra junk
	sed -i.bak 's/\.\/debian\///g' ./debian/DEBIAN/md5sums
	sed -i.bak 's/\\n*DEBIAN*\\n//g' ./debian/DEBIAN/md5sums
	sed -i.bak 's/\\n*DEBIAN*//g' ./debian/DEBIAN/md5sums
	rm -v ./debian/DEBIAN/md5sums.bak
	# figure out the package size, cut off the filename
	du -sx --exclude DEBIAN ./debian/ | cut -f1 > Installed-Size.txt
	# copy over package data
	cp -rv debdata/. debian/DEBIAN/
	# modify the control data to set the installed size and the version number
	sed -i "s/<INSTALLED_SIZE>/$(shell cat Installed-Size.txt)/g" debian/DEBIAN/control
	# read the simple version
	sed -i "s/<VERSION_NUMBER>/$(shell cat simple_version.txt)/g" debian/DEBIAN/control
	# write the changelog
	/usr/bin/git log --date short > ./debian/DEBIAN/changelog
	# remove build directory from safe directory list in git configuration
	git config --global --unset-all safe.directory "$$PWD"
	# fix permissions in package
	chmod -Rv 775 debian/DEBIAN/
	chmod -Rv ugo+r debian/
	chmod -Rv go-w debian/
	chmod -Rv u+w debian/
	# build the package
	# - Upgrade to zstd compression when debian 12 is the last supported version of debian
	#  + Only debian 12+ has support for zstd compression which will make the package smaller
	dpkg-deb -Z xz -z 9 --build debian
	cp -v debian.deb 2web_UNSTABLE.deb
	rm -v debian.deb
	# remove the DEBIAN control directory for the .deb package
	rm -rv debian/DEBIAN
	# compress and save the tarball
	cd debian && tar -czvf ../2web_UNSTABLE.tar.gz etc/ usr/ var/
	# remove the build directory
	rm -rv debian
	# fix permissions on install files
	chmod 777 2web_UNSTABLE.deb
	chmod 777 2web_UNSTABLE.tar.gz
clean:
	# remove temp debian build directory
	rm -rv debian
