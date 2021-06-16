#! /bin/bash
################################################################################
webRoot(){
	# the webdirectory is a cache where the generated website is stored
	if [ -f /etc/nfo2web/web.cfg ];then
		webDirectory=$(cat /etc/nfo2web/web.cfg)
	else
		mkdir -p /var/cache/nfo2web/web/
		chown -R www-data:www-data "/var/cache/nfo2web/web/"
		echo "/var/cache/nfo2web/web" > /etc/nfo2web/web.cfg
		webDirectory="/var/cache/nfo2web/web"
	fi
	mkdir -p "$webDirectory"
	echo "$webDirectory"
}
################################################################################
# delete symlinks in the cache older than one day
find "$(webRoot)/RESOLVER-CACHE/" -type l -mtime +1 -delete
# delete files older than 14 days ( 2 weeks )
find "$(webRoot)/RESOLVER-CACHE/" -type f -mtime +14 -delete
