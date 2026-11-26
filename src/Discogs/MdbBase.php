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

use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * A title on TMDb API
 * @author ed (github user: duck7000)
 */
class MdbBase extends Config
{
    public string $version = '1.0';

    protected $cache;
    protected $logger;
    protected $config;
    protected $api;
    protected int $releaseID;
    protected int $artistID;
    protected int $masterID;

    /**
     * @param Config $config OPTIONAL override default config
     * @param LoggerInterface $logger OPTIONAL override default logger `\Imdb\Logger` with a custom one
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     * @throws exception
     */
    public function __construct(?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        $this->config = $config ?: $this;
        $this->logger = empty($logger) ? new Logger($this->debug) : $logger;
        $this->cache = empty($cache) ? new Cache($this->config, $this->logger) : $cache;
        if (!empty($this->config->apiConsumerKey) && !empty($this->config->apiConsumerSecret)) {
            $this->api = new Api($this->cache, $this->logger, $this->config);
        } else {
            $this->logger->error("Invalid API consumer key or secret in Config Class, probably empty?");
            throw new \Exception("Invalid API consumer key or secret in Config Class, probably empty?");
        }
    }

    /**
     * Set and validate Discogs release ID
     * @param int $id release id
     * @return void
     */
    protected function setid(int $id): void
    {
        if (is_numeric($id)) {
            $this->releaseID = $id;
        } else {
            $this->debugScalar("<BR>setid: Invalid Discogs release ID '$id'!<BR>");
        }
    }

    /**
     * Set and validate Discogs Artist ID
     * @param int $arId artist id
     * @return void
     */
    protected function setArtistId(int $arId): void
    {
        if (is_numeric($arId)) {
            $this->artistID = $arId;
        } else {
            $this->debugScalar("<BR>setid: Invalid Discogs artist ID '$arId'!<BR>");
        }
    }

    /**
     * Set and validate Discogs Master ID
     * @param int $maId master id
     * @return void
     */
    protected function setMasterId(int $maId):void
    {
        if (is_numeric($maId)) {
            $this->masterID = $maId;
        } else {
            $this->debugScalar("<BR>setid: Invalid Discogs master ID '$maId'!<BR>");
        }
    }

    #---------------------------------------------------------[ Debug helpers ]---
    protected function debugScalar($scalar)
    {
        $this->logger->error($scalar);
    }

    protected function debugObject($object)
    {
        $this->logger->error('{object}', array('object' => $object));
    }

    protected function debugHtml($html)
    {
        $this->logger->error(htmlentities($html));
    }

}
