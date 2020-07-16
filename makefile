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
test: install
	sudo time -v nfo2web update
install: build
	sudo gdebi -n nfo2web_UNSTABLE.deb
uninstall:
	sudo apt-get purge nfo2web
uninstall-broken:
	sudo dpkg --remove --force-remove-reinstreq nfo2web
installed-size:
	du -sx --exclude DEBIAN ./debian/
build:
	sudo make build-deb;
build-deb:
	# build the directories inside the package
	mkdir -p debian;
	mkdir -p debian/DEBIAN;
	mkdir -p debian/usr;
	mkdir -p debian/usr/bin;
	mkdir -p debian/usr/share/applications;
	mkdir -p debian/usr/share/nfo2web;
	mkdir -p debian/var/cache/nfo2web/web;
	mkdir -p debian/etc;
	mkdir -p debian/etc/cron.d/;
	mkdir -p debian/etc/apache2/;
	mkdir -p debian/etc/apache2/sites-enabled/;
	mkdir -p debian/etc/apache2/conf-enabled/;
	# make placeholder
	touch debian/var/cache/nfo2web/web/.placeholder
	# copy update script to /usr/bin
	cp nfo2web.sh debian/usr/bin/nfo2web
	# copy over the .desktop launcher file to place link in system menus
	cp nfo2web.desktop debian/usr/share/applications/
	# make the script executable only by root
	chmod u+rwx debian/usr/bin/nfo2web
	chmod go-rwx debian/usr/bin/nfo2web
	# copy over the cron job
	cp nfo2web.cron debian/etc/cron.d/nfo2web-update
	# copy over apache configs
	cp -v apacheConf/nfo2web-ports.conf debian/etc/apache2/conf-enabled/
	cp -v apacheConf/nfo2web-website.conf debian/etc/apache2/sites-enabled/
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
	dpkg-deb --build debian
	cp -v debian.deb nfo2web_UNSTABLE.deb
	rm -v debian.deb
	rm -rv debian
