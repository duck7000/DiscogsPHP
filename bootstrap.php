<?php

declare(strict_types=1);

#############################################################################
# DiscogsPHP                                    ed (github user: duck7000)  #
# written by ed (github user: duck7000)                                     #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

require_once __DIR__ . '/src/Psr/Log/LoggerInterface.php';
require_once __DIR__ . '/src/Psr/SimpleCache/CacheInterface.php';
require_once __DIR__ . '/src/Discogs/Exception.php';
require_once __DIR__ . '/src/Discogs/Config.php';
require_once __DIR__ . '/src/Discogs/Logger.php';
require_once __DIR__ . '/src/Discogs/Cache.php';
require_once __DIR__ . '/src/Discogs/Request.php';
require_once __DIR__ . '/src/Discogs/Api.php';
require_once __DIR__ . '/src/Discogs/MdbBase.php';
require_once __DIR__ . '/src/Discogs/Release.php';
require_once __DIR__ . '/src/Discogs/Artist.php';
require_once __DIR__ . '/src/Discogs/Master.php';
require_once __DIR__ . '/src/Discogs/Search.php';


//$discogs = new \Discogs\Release(14769209);
//echo '<pre>';
    //print_r($discogs->getReleaseData());
//echo '/<pre>';

$discogs = new \Discogs\Master(41160);
echo '<pre>';
    print_r($discogs->getMasterReleasesData('Vinyl'));
echo '/<pre>';
