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
	sudo time -v nfo2web reset || echo "no reset needed..."
	sudo time -v nfo2web update
test-update-ondemand: install
	sudo time -v nfo2web update
test-update: install
	sudo time -v nfo2web update
	sudo time -v iptv2web update
test: install
	sudo time -v nfo2web reset || echo "no reset needed..."
	sudo time -v nfo2web update
	sudo time -v iptv2web reset || echo "no reset needed..."
	sudo time -v iptv2web webgen
	sudo time -v iptv2web update
	sudo time -v iptv2web webgen
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
	sudo apt-get purge nfo2web
uninstall-broken:
	sudo dpkg --remove --force-remove-reinstreq nfo2web
installed-size:
	du -sx --exclude DEBIAN ./debian/
debugOn:
	sudo mv /etc/2web/nfo/debug.disabled /etc/2web/nfo/debug.enabled
debugOff:
	sudo mv /etc/2web/nfo/debug.enabled /etc/2web/nfo/debug.disabled
build-tools:
	# for making man files
	sudo apt-get install pandoc
	sudo apt-get install w3m
build:
	# install the build tools
	sudo make build-deb;
build-deb:
	# build the directories inside the package
	mkdir -p debian;
	mkdir -p debian/DEBIAN;
	mkdir -p debian/usr;
	mkdir -p debian/usr/bin;
	mkdir -p debian/usr/share/applications;
	mkdir -p debian/usr/share/2web/nfo;
	mkdir -p debian/usr/share/2web/iptv;
	mkdir -p debian/usr/share/2web/;
	mkdir -p debian/usr/share/2web/help/;
	mkdir -p debian/usr/share/man/man1/;
	mkdir -p debian/usr/share/2web/themes;
	mkdir -p debian/usr/share/2web/theme-templates;
	mkdir -p debian/usr/share/2web/templates;
	mkdir -p debian/usr/share/2web/settings;
	#mkdir -p debian/var/cache/web/web;
	mkdir -p debian/var/cache/2web/cache;
	mkdir -p debian/etc;
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/themes;
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/config_default/;
	mkdir -p debian/etc/2web/ytdl/
	mkdir -p debian/etc/2web/ytdl/sources.d/
	mkdir -p debian/etc/2web/ytdl/usernameSources.d/
	mkdir -p debian/etc/2web/users/;
	mkdir -p debian/etc/2web/nfo/;
	mkdir -p debian/etc/2web/nfo/libaries.d/;
	mkdir -p debian/etc/2web/music/;
	mkdir -p debian/etc/2web/music/libaries.d/;
	mkdir -p debian/etc/2web/comics/;
	mkdir -p debian/etc/2web/comics/libaries.d/;
	mkdir -p debian/etc/2web/comics/sources.d/;
	mkdir -p debian/etc/2web/iptv/;
	mkdir -p debian/etc/2web/iptv/sources.d/;
	mkdir -p debian/etc/2web/iptv/blockedGroups.d/;
	mkdir -p debian/etc/2web/iptv/radioSources.d/;
	mkdir -p debian/etc/2web/iptv/blockedLinks.d/;
	mkdir -p debian/etc/2web/weather/;
	mkdir -p debian/etc/2web/weather/location.d/;
	mkdir -p debian/etc/cron.d/;
	mkdir -p debian/etc/apache2/;
	mkdir -p debian/etc/apache2/sites-available/;
	mkdir -p debian/etc/apache2/sites-enabled/;
	mkdir -p debian/etc/apache2/conf-available/;
	mkdir -p debian/etc/apache2/conf-enabled/;
	mkdir -p debian/etc/bash_completion.d/;
	mkdir -p debian/etc/avahi/;
	mkdir -p debian/etc/avahi/services/;
	mkdir -p debian/var/lib/2web/;
	# copy templates over
	cp -rv templates/. debian/usr/share/2web/templates/
	# copy over default config templates
	cp -rv config_default/. debian/etc/2web/config_default/
	# add icon
	cp -rv 2web_icon.png debian/usr/share/2web/favicon_default.png
	# make placeholder
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
	touch debian/etc/2web/comics/.placeholder
	touch debian/etc/2web/comics/libaries.d/.placeholder
	touch debian/etc/2web/comics/sources.d/.placeholder
	touch debian/etc/2web/weather/location.d/.placeholder
	touch debian/var/cache/2web/cache/.placeholder
	touch debian/usr/share/2web/settings/.placeholder
	touch debian/usr/share/2web/themes/.placeholder
	touch debian/etc/2web/users/.placeholder
	# fix ownership
	chown -R www-data:www-data debian/etc/2web/users/
	chown -R www-data:www-data debian/etc/2web/ytdl/*.d/
	chown -R www-data:www-data debian/etc/2web/iptv/*.d/
	chown -R www-data:www-data debian/etc/2web/nfo/*.d/
	chown -R www-data:www-data debian/etc/2web/comics/*.d/
	chown -R www-data:www-data debian/etc/2web/weather/*.d/
	#chown -R www-data:www-data debian/etc/2web/*.d/
	chown -R www-data:www-data debian/etc/2web/
	# copy the certInfo default script
	cp certInfo.cnf debian/etc/2web/
	# add the base lib used across all modules
	#cp 2webLib.sh debian/var/lib/2web/common
	echo "#! /bin/bash" > debian/var/lib/2web/common
	# remove all comment lines from the code to reduce package size and disk read on execution
	grep --invert-match "^[[:blank:]]*#" 2webLib.sh | tr -s '\n' >> debian/var/lib/2web/common
	# copy update scripts to /usr/bin
	#cp 2web.sh debian/usr/bin/2web
	echo "#! /bin/bash" > debian/usr/bin/2web
	grep --invert-match "^[[:blank:]]*#" 2web.sh | tr -s '\n' >> debian/usr/bin/2web
	#cp nfo2web.sh debian/usr/bin/nfo2web
	echo "#! /bin/bash" > debian/usr/bin/nfo2web
	grep --invert-match "^[[:blank:]]*#" nfo2web.sh | tr -s '\n' >> debian/usr/bin/nfo2web
	#cp music2web.sh debian/usr/bin/music2web
	echo "#! /bin/bash" > debian/usr/bin/music2web
	grep --invert-match "^[[:blank:]]*#" music2web.sh | tr -s '\n' >> debian/usr/bin/music2web
	#cp iptv2web.sh debian/usr/bin/iptv2web
	echo "#! /bin/bash" > debian/usr/bin/iptv2web
	grep --invert-match "^[[:blank:]]*#" iptv2web.sh | tr -s '\n' >> debian/usr/bin/iptv2web
	#cp comic2web.sh debian/usr/bin/comic2web
	echo "#! /bin/bash" > debian/usr/bin/comic2web
	grep --invert-match "^[[:blank:]]*#" comic2web.sh | tr -s '\n' >> debian/usr/bin/comic2web
	#cp graph2web.sh debian/usr/bin/graph2web
	echo "#! /bin/bash" > debian/usr/bin/graph2web
	grep --invert-match "^[[:blank:]]*#" graph2web.sh | tr -s '\n' >> debian/usr/bin/graph2web
	#cp kodi2web.sh debian/usr/bin/kodi2web
	echo "#! /bin/bash" > debian/usr/bin/kodi2web
	grep --invert-match "^[[:blank:]]*#" kodi2web.sh | tr -s '\n' >> debian/usr/bin/kodi2web
	#cp weather2web.sh debian/usr/bin/weather2web
	echo "#! /bin/bash" > debian/usr/bin/weather2web
	grep --invert-match "^[[:blank:]]*#" weather2web.sh | tr -s '\n' >> debian/usr/bin/weather2web
	#cp ytdl2nfo.sh debian/usr/bin/ytdl2nfo
	echo "#! /bin/bash" > debian/usr/bin/ytdl2nfo
	grep --invert-match "^[[:blank:]]*#" ytdl2nfo.sh | tr -s '\n' >> debian/usr/bin/ytdl2nfo
	# build the man pages for the command line tools
	#pandoc -s help/man_2web_header.md help/man_copyright.md help/man_licence.md help/man_2web_content.md -t man -o debian/usr/share/man1/2web.gz
	pandoc --standalone help/man_2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/2web.1.gz
	pandoc --standalone help/man_nfo2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/nfo2web.1.gz
	pandoc --standalone help/man_iptv2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/iptv2web.1.gz
	pandoc --standalone help/man_comic2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/comic2web.1.gz
	pandoc --standalone help/man_weather2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/weather2web.1.gz
	pandoc --standalone help/man_ytdl2nfo.md help/man_footer.md -t man -o debian/usr/share/man/man1/ytdl2nfo.1.gz
	pandoc --standalone help/man_music2web.md help/man_footer.md -t man -o debian/usr/share/man/man1/music2web.1.gz
	# build the web versions of the man pages
	pandoc help/man_2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/2web.html
	pandoc help/man_nfo2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/nfo2web.html
	pandoc help/man_iptv2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/iptv2web.html
	pandoc help/man_comic2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/comic2web.html
	pandoc help/man_weather2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/weather2web.html
	pandoc help/man_ytdl2nfo.md help/man_footer.md -t html -o debian/usr/share/2web/help/ytdl2nfo.html
	pandoc help/man_music2web.md help/man_footer.md -t html -o debian/usr/share/2web/help/music2web.html
	# build the text only render of the manual
	w3m debian/usr/share/2web/help/2web.html > debian/usr/share/2web/help/2web.txt
	w3m debian/usr/share/2web/help/nfo2web.html > debian/usr/share/2web/help/nfo2web.txt
	w3m debian/usr/share/2web/help/iptv2web.html > debian/usr/share/2web/help/iptv2web.txt
	w3m debian/usr/share/2web/help/comic2web.html > debian/usr/share/2web/help/comic2web.txt
	w3m debian/usr/share/2web/help/weather2web.html > debian/usr/share/2web/help/weather2web.txt
	w3m debian/usr/share/2web/help/ytdl2nfo.html > debian/usr/share/2web/help/ytdl2nfo.txt
	w3m debian/usr/share/2web/help/music2web.html > debian/usr/share/2web/help/music2web.txt
	# build the readme
	pandoc --standalone README.md help/man_footer.md -t man -o debian/usr/share/man/man1/2web_help.1.gz
	pandoc README.md help/man_footer.md -t html -o debian/usr/share/2web/help/README.html
	w3m debian/usr/share/2web/help/README.html > debian/usr/share/2web/help/README.txt
	# copy over the theme templates
	cp -v themes/*.css debian/usr/share/2web/theme-templates/
	# get the latest hls.js from npm and include it in the package
	npm install --save hls.js
	cp -v node_modules/hls.js/dist/hls.js debian/usr/share/2web/iptv/hls.js
	# build the default themes
	# user themes can be any self contained .css file
	# copy over the main javascript libary
	cp 2web.js debian/usr/share/2web/
	# copy over the main php libary
	cp 2webLib.php debian/usr/share/2web/
	# copy over the settings pages
	cp settings/*.php debian/usr/share/2web/settings/
	# copy the resolvers
	#grep --invert-match "^[[:blank:]]*#" ytdl-resolver.php | grep --invert-match "^[[:blank:]]*//" | grep --invert-match "^[[:blank:]]*#" | sed "s/\;\n/;/g" | tr -s '\n' > debian/usr/share/2web/ytdl-resolver.php
	#grep --invert-match "^[[:blank:]]*#" iptv-resolver.php | grep --invert-match "^[[:blank:]]*//" | grep --invert-match "^[[:blank:]]*#" | sed "s/\;\n/;/g" | tr -s '\n' > debian/usr/share/2web/iptv-resolver.php
	#grep --invert-match "^[[:blank:]]*#" m3u-gen.php | grep --invert-match "^[[:blank:]]*//" | grep --invert-match "^[[:blank:]]*#" | sed "s/\;\n/;/g" | tr -s '\n' > debian/usr/share/2web/m3u-gen.php
	cp resolvers/ytdl-resolver.php debian/usr/share/2web/
	cp resolvers/m3u-gen.php debian/usr/share/2web/
	cp resolvers/iptv-resolver.php debian/usr/share/2web/iptv/
	cp resolvers/transcode.php debian/usr/share/2web/
	cp resolvers/search.php debian/usr/share/2web/
	# copy over the .desktop launcher file to place link in system menus
	cp 2web.desktop debian/usr/share/applications/
	# make the script executable only by root
	chmod u+rwx debian/usr/bin/*
	chmod go-rwx debian/usr/bin/*
	# copy over the cron job
	cp 2web.cron debian/usr/share/2web/cron
	# copy over apache configs
	cp -v apacheConf/0-2web-ports.conf debian/etc/apache2/conf-available/
	cp -v apacheConf/0-2web-website.conf debian/etc/apache2/sites-available/
	cp -v apacheConf/0-2web-website-SSL.conf debian/etc/apache2/sites-available/
	cp -v apacheConf/0-2web-website-compat.conf debian/etc/apache2/sites-available/
	# copy over the zeroconf configs to anounce the service
	cp -v apacheConf/zeroconf_http.service debian/etc/avahi/services/2web_http.service
	cp -v apacheConf/zeroconf_https.service debian/etc/avahi/services/2web_https.service
	# copy over bash tab completion scripts
	cp -v tab_complete/* debian/etc/bash_completion.d/
	# write version info last thing before the build process of the package
	# this also makes the build date time more correct since the package is
	# built but is now being compressed and converted into a debian package
	/usr/bin/git log --oneline | wc -l > debian/usr/share/2web/version.cfg
	if /usr/bin/git status| grep "Changes not staged for";then echo "+UNSTABLE-BRANCH" >> debian/usr/share/2web/version.cfg;fi
	if /usr/bin/git status| grep "Changes to be committed";then echo "+TESTING" >> debian/usr/share/2web/version.cfg;fi
	#/usr/bin/git log --oneline >> debian/usr/share/2web/version.cfg
	# version date of creation
	/usr/bin/git log -1 | grep "Date:" | tr -s ' ' | cut -d' ' -f2- > debian/usr/share/2web/versionDate.cfg
	date > debian/usr/share/2web/buildDate.cfg
	#/usr/bin/git log -1 >> debian/usr/share/2web/versionDate.cfg
	# Create the md5sums file
	find ./debian/ -type f -print0 | xargs -0 md5sum > ./debian/DEBIAN/md5sums
	# cut filenames of extra junk
	sed -i.bak 's/\.\/debian\///g' ./debian/DEBIAN/md5sums
	sed -i.bak 's/\\n*DEBIAN*\\n//g' ./debian/DEBIAN/md5sums
	sed -i.bak 's/\\n*DEBIAN*//g' ./debian/DEBIAN/md5sums
	rm -v ./debian/DEBIAN/md5sums.bak
	# figure out the package size
	du -sx --exclude DEBIAN ./debian/ > Installed-Size.txt
	# copy over package data
	cp -rv debdata/. debian/DEBIAN/
	# write the changelog
	/usr/bin/git log --date short > ./debian/DEBIAN/changelog
	# fix permissions in package
	chmod -Rv 775 debian/DEBIAN/
	chmod -Rv ugo+r debian/
	chmod -Rv go-w debian/
	chmod -Rv u+w debian/
	# build the package
	dpkg-deb -Z xz --build debian
	cp -v debian.deb 2web_UNSTABLE.deb
	rm -v debian.deb
	rm -rv debian
