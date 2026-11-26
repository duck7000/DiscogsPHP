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
 * Accessing movie, tv and person through TMDb API
 * @author Ed (duck7000)
 */
class Api
{

    private $cache;
    private $logger;
    private $config;
    protected string $apiUrl;

    /**
     * API constructor.
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct($cache, $logger, $config)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
        $this->apiUrl = $this->config->apiUrl;
    }

    /**
     * Search request for Search class
     * @param string $searchInputString input search string
     * @param associative-array $searchParameters input extra search parameters
     * Possible search parameters:
     *          [barcode]       string
     *          [catno]         string (catalog number)
     *          [format]        string (CD, Vinyl, album) @see https://www.discogs.com/help/formatslist
     *          [year]          string
     *          [type]          string (one of: release, master, artist, label)
     *          [artist]        string (artist name)
     *          [title]         string (Search by combined "Artist Name - Release Title" title field)
     *          [release_title] string (search on release title name)
     *          [anv]           string (Artist name variation)
     *          [credit]        string (credited person)
     *          [label]         string (label name)
     *          [genre]         string
     *          [style]         string
     *          [country]       string (country code e.g. US, EU, Europe
     *          [track]         string (track name)
     *          [contributor]   string (search contributor name)
     *          [submitter]     string (search submitter name)
     *          [sort]          string (by: score, title, have, want, hot, date_added, date_changed, year)
     *          [sort_order]    string (asc or desc)
     * @return object|false
     */
    public function doTextSearch(string $searchInputString, array $searchParameters): object|false
    {
        if (empty($searchInputString) && empty($searchParameters)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/database';
        $url .= '/search';
        $url .= '?';
        if (!empty($searchInputString)) {
            $url .= 'q=';
            $url .= rawurlencode($searchInputString);
        }
        if (!empty($searchParameters)) {
            $url .= '&';
            foreach ($searchParameters as $key => $value) {
                if (is_string($key) &&
                    !empty($key) &&
                    is_string($value) &&
                    !empty($value)
                   )
                {
                    $url .= $key . '=' . rawurlencode($value);
                }
                if ($key !== array_key_last($searchParameters)) {
                    $url .= '&';
                }
            }
        }
        return $this->execRequest($url);
    }

    /**
     * build url and data request for release class getReleaseData()
     * @param int $releaseID discogs release Id
     * @return object|false
     */
    public function doLookup(int $releaseID): object|array|false
    {
        if (!isset($releaseID) && !is_int($releaseID)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/';
        $url .= 'releases';
        $url .= '/';
        $url .= $releaseID;
        return $this->setCache($releaseID, $url, false, '_release');
    }

    /**
     * build url and data request for Artist class getArtistData()
     * @param int $artistID discogs artist Id
     * @return object|array|false
     */
    public function doArtistLookup(int $artistID): object|array|false
    {
        if (!isset($artistID) && !is_int($artistID)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/';
        $url .= 'artists';
        $url .= '/';
        $url .= $artistID;
        return $this->setCache($artistID, $url, false, '_artist');
    }

    /**
     * build url and data request for Artist Releases class getArtistReleasesData()
     * @param int $artistID discogs artist Id
     * @param string $sortBy one of: year, title or format
     * @param string $sortOrder one of: asc or desc
     * @return array|false
     */
    public function doArtistReleasesLookup(int $artistID, string $sortBy, string $sortOrder): array|false
    {
        if (!isset($artistID) && !is_int($artistID)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/';
        $url .= 'artists';
        $url .= '/';
        $url .= $artistID;
        $url .= '/';
        $url .= 'releases';
        $url .= '?';
        if (!empty($sortBy)) {
            $url .= 'sort=' . $sortBy;
        }
        if (!empty($sortOrder)) {
            $url .= '&sort_order=' . $sortOrder;
        }
        $url .= '&';
        $url .= 'per_page=100';
        return $this->setCache($artistID, $url, true, '_artistReleases');
    }

    /**
     * build url and data request for Master class getMasterData()
     * @param int $masterID discogs master Id
     * @return object|false
     */
    public function doMasterLookup(int $masterID): object|false
    {
        if (!isset($masterID) && !is_int($masterID)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/';
        $url .= 'masters';
        $url .= '/';
        $url .= $masterID;
        return $this->setCache($masterID, $url, false, '_master');
    }

    /**
     * build url and data request for master Releases class getMasterReleasesData()
     * @param int $masterID discogs master Id
     * @return array|false
     */
    public function doMasterReleasesLookup(int $masterID): array|false
    {
        if (!isset($masterID) && !is_int($masterID)) {
            return false;
        }
        $url = $this->apiUrl;
        $url .= '/';
        $url .= 'masters';
        $url .= '/';
        $url .= $masterID;
        $url .= '/';
        $url .= 'versions';
        $url .= '?';
        $url .= '&';
        $url .= 'per_page=100';
        return $this->setCache($masterID, $url, true, '_masterReleases', $returnEntityType = 'versions');
    }

    /**
     * If more than 100 items paging is required to get all items
     * @param string $url the complete url from initial request
     * @param string $returnEntityType entity name in returned object 2 types supported: releases or versions
     * @return array
     */
    public function paging(string $url, string $returnEntityType): array
    {
        $resultChunks = array();
        $firstResults = $this->execRequest($url);
        if (is_array($firstResults->$returnEntityType) && count($firstResults->$returnEntityType) > 0){
            $resultChunks = array_merge($resultChunks, $firstResults->$returnEntityType);
            if (isset($firstResults->pagination->urls->next)) {
                $next = $firstResults->pagination->urls->next;
                while($next) {
                    $requestData = $this->execRequest($next);
                    $resultChunks = array_merge($resultChunks, $requestData->$returnEntityType);
                    $next = $requestData->pagination->urls->next ?? null;
                    unset($requestData);
                }
            }
        }
        return $resultChunks;
    }

    /**
     * Execute request
     * @param string $url
     * @throw Exception
     * @return object|false
     */
    public function execRequest(string $url): object|false
    {
        $request = new Request($url, $this->config);
        $request->sendRequest();
        if (200 == $request->getStatus() || 307 == $request->getStatus()) {
            return json_decode($request->getResponseBody());
        } elseif (404 == $request->getStatus()) {
            return false;
        } else {
            $this->logger->error(
                "[API] Failed to retrieve query. Response headers:{headers}. Response body:{body}",
                array('headers' => $request->getLastResponseHeaders(), 'body' => $request->getResponseBody())
            );
            if ($this->config->throwHttpExceptions) {
                throw new \Exception("Failed to retrieve query");
            } else {
                return new \StdClass();
            }
        }
    }

    /**
     * Caching return data
     * @param string $id TMDb id from lookUp functions
     * @param string $url exec url from lookUp functions
     * @param bool $pagination use pagination or not
     * @param string $ext cache filename extension
     * @param string $returnEntityType entity name in returned object 2 types supported: releases or versions
     * @return object|array
     */
    public function setCache(int $id, string $url, bool $pagination, string $ext = '', string $returnEntityType = 'releases'): object|array|false
    {
        if ($this->config->cacheUse === true && $this->config->cacheStore === true) {
            $key = $id . $ext . '.json';
            $fromCache = $this->cache->get($key);
            if ($fromCache != null) {
                return json_decode($fromCache);
            }
            if ($pagination === true) {
                $data = $this->paging($url, $returnEntityType);
            } else {
                $data = $this->execRequest($url);
            }
            if (!empty($data) || count((array) $data) > 0) {
                $this->cache->set($key, json_encode($data));
                return $data;
            } else {
                return false;
            }
        } else {
            if ($pagination === true) {
                return $this->paging($url, $returnEntityType);
            } else {
                return $this->execRequest($url);
            }
        }
        
    }

}
