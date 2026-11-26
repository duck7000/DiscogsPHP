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
 * Artist class
 * @author ed (github user: duck7000)
 */
class Artist extends MdbBase
{

    protected array $results = array();
    protected array $artistReleasesResults = array();

    /**
     * @param int $arId Discogs artist id
     * @param Config $config OPTIONAL override default config
     * @param Logger $cache OPTIONAL override the default logger with a custom one.
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(int $arId, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setArtistId($arId);
    }

    /**
     * Get all data for artist id
     * @return array
     *     [id]         int 108713
     *     [name]       string Nickelback
     *     [image] array
     *          [urlFull]   string
     *          [url150]    string
     *          [width]     int 600
     *          [height]    int 450
     *     [realname]   string
     *     [profile]    string
     *     [urls] array
     *          [0] string http://www.nickelback.com/
     *     [members] array
     *          [0] array
     *              [id]        int 270222
     *              [name]      string Chad Kroeger
     *              [active]    bool 1
     */
    public function getArtistData(): array
    {
        // Data request
        $data = $this->api->doArtistLookup($this->artistID);
        if (empty((array) $data)) {
            return $this->results;
        }
        // images
        $image = array();
        if (
            isset($data->images) &&
            is_array($data->images) &&
            count($data->images) > 0
           )
        {
            foreach ($data->images as $imageValue) {
                if (!empty((array) $imageValue)) {
                    if (isset($imageValue->type) && $imageValue->type === 'primary') {
                        $image = array(
                            'urlFull' => $imageValue->resource_url ?? null,
                            'url150' => $imageValue->uri150 ?? null,
                            'width' => $imageValue->width ?? 0,
                            'height' => $imageValue->height ?? 0
                        );
                    }
                }
            }
        }
        // urls
        $urls = array();
        if (
            isset($data->urls) &&
            is_array($data->urls) &&
            count($data->urls) > 0
           )
        {
            foreach ($data->urls as $urlsValue) {
                if (!empty($urlsValue)) {
                    $urls[] = $urlsValue;
                }
            }
        }
        // members (will be empty by solo artist)
        $members = array();
        if (
            isset($data->members) &&
            is_array($data->members) &&
            count($data->members) > 0
           )
        {
            foreach ($data->members as $membersValue) {
                if (!empty((array) $membersValue)) {
                    $members[] = array(
                        'id' => $membersValue->id ?? null,
                        'name' => $membersValue->name ?? null,
                        'active' => $membersValue->active ?? 0
                    );
                }
            }
        }
        // results array
        $this->results = array(
            'id' => $data->id ?? null,
            'name' => $data->name ?? null,
            'image' => $image,
            'realname' => $data->realname ?? null,
            'profile' => $data->profile ?? null,
            'urls' => $urls,
            'members' => $members
        );
        return $this->results;
    }

    /**
     * Get all releases of this artist ID
     * @param string $sortBy one of: year, title or format. Default: empty string
     * @param string $sortOrder one of: asc or desc. Default: empty string
     * @return array
     *      [master] array
     *          [0] array
     *              [id]        int 173765 (depending on category this is master or release id)
     *              [artist]    string Nickelback
     *              [title]     string Curb
     *              [year]      string 1996
     *              [role]      string Main
     *              [format]    string
     *              [label]     string
     */
    public function getArtistReleasesData(string $sortBy = '', string $sortOrder = ''): array
    {
        // Data request
        $artistReleasesData = $this->api->doArtistReleasesLookup($this->artistID, $sortBy, $sortOrder);
        if (empty($artistReleasesData)) {
            return $this->artistReleasesResults;
        }
        foreach ($artistReleasesData as $value) {
            $category = isset($value->type) ? $value->type : 'unknown';
            $this->artistReleasesResults[$category][] = array(
                'id' => $value->id ?? null,
                'artist' => $value->artist ?? null,
                'title' => $value->title ?? null,
                'year' => $value->year ?? null,
                'role' => $value->role ?? null,
                'format' => $value->format ?? null,
                'label' => $value->label ?? null
            );
        }
        return $this->artistReleasesResults;
    }

}
