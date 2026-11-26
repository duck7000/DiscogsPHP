<?php

declare(strict_types=1);

#############################################################################
# DiscogsPHP                                    ed (github user: duck7000)  #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Discogs;

/**
 * Configuration class for Discogs
 * @author ed (github user: duck7000)
 */
class Config
{

    #========================================================[ Cache options]===
    /**
     * Directory to store cached pages. This must be writable by the web
     * server. It doesn't need to be under documentroot.
     * @var string
     */
    public string $cacheDir = './cache/';

    /**
     * Use cached pages if available?
     * @var bool
     */
    public bool $cacheUse = faLse;

    /**
     * Store the pages retrieved for later use?
     * @var bool
     */
    public bool $cacheStore = false;

    /**
     * Use zip compression for caching the retrieved html-files?
     * @see $converttozip if you're changing from false to true
     * @var bool
     */
    public $cacheUseZip = false;

    /**
     * Convert non-zip cache-files to zip
     * You might want to use this if you weren't gzipping your cache files, but now are. They will be rewritten when they're used
     * @var bool
     */
    public bool $cacheConvertZip = false;

    /**
     * Cache expiration time - cached pages older than this value (in seconds) will
     * be automatically deleted.
     * If 0 cached pages will never expire
     * @var int
     */
    public int $cacheExpire = 604800;
    
    /**
     * Discogs API base url
     * @var string
     */
    public string $apiUrl = 'https://api.discogs.com';

    /**
     * Discogs API consumer key
     * @var string
     */
    public string $apiConsumerKey = '';

 /**
     * Discogs API consumer secret key
     * @var string
     */
    public $apiConsumerSecret = '';

    /**
     * Debug mode true or false
     * @var bool
     */
    public bool $debug = true;

    /**
     * Throw Exception if something goes wrong with the api call
     * True: throws Exception, false: returns empty object
     * @var bool
     */
    public bool $throwHttpExceptions = false;

    /**
     * Set curlopt_timout (in seconds), this is the time out if curl has a connection problem
     * @var int
     */
    public int $curloptTimeout = 30;

}
