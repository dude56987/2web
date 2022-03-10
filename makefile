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
build:
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
	mkdir -p debian/usr/share/2web/themes;
	mkdir -p debian/usr/share/2web/templates;
	mkdir -p debian/usr/share/2web/settings;
	#mkdir -p debian/var/cache/web/web;
	mkdir -p debian/var/cache/2web/cache;
	mkdir -p debian/etc;
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/themes;
	mkdir -p debian/etc/ytdl2kodi/;
	mkdir -p debian/etc/ytdl2kodi/sources.d/
	mkdir -p debian/etc/ytdl2kodi/usernameSources.d/
	mkdir -p debian/etc/2web/;
	mkdir -p debian/etc/2web/users/;
	mkdir -p debian/etc/2web/nfo/;
	mkdir -p debian/etc/2web/nfo/libaries.d/;
	mkdir -p debian/etc/2web/comics/;
	mkdir -p debian/etc/2web/comics/libaries.d/;
	mkdir -p debian/etc/2web/comics/sources.d/;
	mkdir -p debian/etc/2web/iptv/;
	mkdir -p debian/etc/2web/iptv/sources.d/;
	mkdir -p debian/etc/2web/iptv/blockedGroups.d/;
	mkdir -p debian/etc/2web/iptv/radioSources.d/;
	mkdir -p debian/etc/2web/iptv/blockedLinks.d/;
	mkdir -p debian/etc/cron.d/;
	mkdir -p debian/etc/apache2/;
	mkdir -p debian/etc/apache2/sites-available/;
	mkdir -p debian/etc/apache2/sites-enabled/;
	mkdir -p debian/etc/apache2/conf-enabled/;
	# write version info
	git log --oneline | wc -l > debian/etc/2web/version.cfg
	# copy templates over
	cp -rv templates/. debian/usr/share/2web/templates/
	# add icon
	cp -rv 2web_icon.png debian/usr/share/2web/favicon_default.png
	# make placeholder
	touch debian/etc/ytdl2kodi/sources.d/.placeholder
	touch debian/etc/ytdl2kodi/usernameSources.d/.placeholder
	touch debian/etc/2web/iptv/.placeholder
	touch debian/etc/2web/iptv/sources.d/.placeholder
	touch debian/etc/2web/iptv/blockedGroups.d/.placeholder
	touch debian/etc/2web/iptv/radioSources.d/.placeholder
	touch debian/etc/2web/iptv/blockedLinks.d/.placeholder
	touch debian/etc/2web/nfo/.placeholder
	touch debian/etc/2web/nfo/libaries.d/.placeholder
	touch debian/etc/2web/comics/.placeholder
	touch debian/etc/2web/comics/libaries.d/.placeholder
	touch debian/etc/2web/comics/sources.d/.placeholder
	#touch debian/var/cache/nfo2web/web/.placeholder
	touch debian/var/cache/2web/cache/.placeholder
	touch debian/usr/share/2web/settings/.placeholder
	touch debian/etc/2web/users/.placeholder
	# fix ownership
	chown -R www-data:www-data debian/etc/2web/users/
	chown -R www-data:www-data debian/etc/ytdl2kodi/*.d/
	chown -R www-data:www-data debian/etc/2web/iptv/*.d/
	chown -R www-data:www-data debian/etc/2web/nfo/*.d/
	chown -R www-data:www-data debian/etc/2web/comics/*.d/
	#chown -R www-data:www-data debian/etc/2web/*.d/
	chown -R www-data:www-data debian/etc/2web/
	# copy the certInfo default script
	cp certInfo.cnf debian/etc/2web/
	# copy update scripts to /usr/bin
	cp 2web.sh debian/usr/bin/2web
	cp nfo2web.sh debian/usr/bin/nfo2web
	cp iptv2web.sh debian/usr/bin/iptv2web
	cp comic2web.sh debian/usr/bin/comic2web
	cp ytdl2kodi.sh debian/usr/bin/ytdl2kodi
	# build the default themes
	# - default (gray)
	cat themes/default.css > debian/usr/share/2web/themes/default.css
	cat themes/base.css >> debian/usr/share/2web/themes/default.css
	# - default-soft (gray)
	cat themes/default.css > debian/usr/share/2web/themes/default-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/default-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/default-soft.css
	# - yellow
	cat themes/yellow.css > debian/usr/share/2web/themes/yellow.css
	cat themes/base.css >> debian/usr/share/2web/themes/yellow.css
	# - yellow-soft
	cat themes/yellow.css > debian/usr/share/2web/themes/yellow-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/yellow-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/yellow-soft.css
	# - cyan
	cat themes/cyan.css > debian/usr/share/2web/themes/cyan.css
	cat themes/base.css >> debian/usr/share/2web/themes/cyan.css
	# - cyan-soft
	cat themes/cyan.css > debian/usr/share/2web/themes/cyan-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/cyan-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/cyan-soft.css
	# - blue
	cat themes/blue.css > debian/usr/share/2web/themes/blue.css
	cat themes/base.css >> debian/usr/share/2web/themes/blue.css
	# - blue-soft
	cat themes/blue.css > debian/usr/share/2web/themes/blue-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/blue-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/blue-soft.css
	# - red
	cat themes/red.css > debian/usr/share/2web/themes/red.css
	cat themes/base.css >> debian/usr/share/2web/themes/red.css
	# - red-soft
	cat themes/red.css > debian/usr/share/2web/themes/red-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/red-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/red-soft.css
	# - green
	cat themes/green.css > debian/usr/share/2web/themes/green.css
	cat themes/base.css >> debian/usr/share/2web/themes/green.css
	# - green-soft
	cat themes/green.css > debian/usr/share/2web/themes/green-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/green-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/green-soft.css
	# - violet
	cat themes/violet.css > debian/usr/share/2web/themes/violet.css
	cat themes/base.css >> debian/usr/share/2web/themes/violet.css
	# - violet-soft
	cat themes/violet.css > debian/usr/share/2web/themes/violet-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/violet-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/violet-soft.css
	# - orange
	cat themes/orange.css > debian/usr/share/2web/themes/orange.css
	cat themes/base.css >> debian/usr/share/2web/themes/orange.css
	# - orange-soft
	cat themes/orange.css > debian/usr/share/2web/themes/orange-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/orange-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/orange-soft.css
	# - brown
	cat themes/brown.css > debian/usr/share/2web/themes/brown.css
	cat themes/base.css >> debian/usr/share/2web/themes/brown.css
	# - brown-soft ;P
	cat themes/brown.css > debian/usr/share/2web/themes/brown-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/brown-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/brown-soft.css
	# - rainbow
	cat themes/rainbow.css > debian/usr/share/2web/themes/rainbow.css
	cat themes/base.css >> debian/usr/share/2web/themes/rainbow.css
	# - rainbow-soft
	cat themes/rainbow.css > debian/usr/share/2web/themes/rainbow-soft.css
	cat themes/soft-mod.css >> debian/usr/share/2web/themes/rainbow-soft.css
	cat themes/base.css >> debian/usr/share/2web/themes/rainbow-soft.css
	# user created themes, themes are constructed from above using base theme
	# user themes can be any self contained .css file
	#cp themes/*.css debian/usr/share/2web/themes/
	# copy over javascript libary
	cp 2web.js debian/usr/share/2web/
	# copy over php scripts
	cp templates/randomFanart.php debian/usr/share/2web/
	cp templates/randomPoster.php debian/usr/share/2web/
	# copy over the settings pages
	cp settings/*.php debian/usr/share/2web/settings/
	# copy link page
	cp link.php debian/usr/share/2web/link.php
	# copy the resolvers
	cp ytdl-resolver.php debian/usr/share/2web/
	cp m3u-gen.php debian/usr/share/2web/
	cp transcode.php debian/usr/share/2web/
	cp 404.php debian/usr/share/2web/
	cp 403.php debian/usr/share/2web/
	cp 401.php debian/usr/share/2web/
	cp iptv-resolver.php debian/usr/share/2web/iptv/
	# copy over the .desktop launcher file to place link in system menus
	cp 2web.desktop debian/usr/share/applications/
	# make the script executable only by root
	chmod u+rwx debian/usr/bin/*
	chmod go-rwx debian/usr/bin/*
	# copy over the cron job
	cp 2web.cron debian/etc/cron.d/2web-update
	# copy over apache configs
	cp -v apacheConf/0-2web-ports.conf debian/etc/apache2/conf-enabled/
	cp -v apacheConf/0-2web-website.conf debian/etc/apache2/sites-enabled/
	cp -v apacheConf/0-2web-website-SSL.conf debian/etc/apache2/sites-enabled/
	cp -v apacheConf/0-2web-website-compat.conf debian/etc/apache2/sites-enabled/
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
