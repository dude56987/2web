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
if test -f "$(webRoot)/cacheDelay.cfg";then
	echo "Loading cache settings..."
	cacheDelay=$(cat "$(webRoot)/cacheDelay.cfg")
else
	echo "Using default cache settings..."
	cacheDelay="14"
fi
echo "Cache Delay = $cacheDelay"
# delete files older than 14 days ( 2 weeks )
if test -f "$(webRoot)/RESOLVER-CACHE/";then
	find "$(webRoot)/RESOLVER-CACHE/" -type f -mtime +"$cacheDelay" -delete
fi
# delete the m3u cache
if test -f "$(webRoot)/M3U-CACHE/";then
	find "$(webRoot)/M3U-CACHE/" -type f -mtime +"$cacheDelay" -delete
fi
