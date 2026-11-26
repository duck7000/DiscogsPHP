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
 * A master on discogs API
 * @author ed (github user: duck7000)
 */
class Master extends MdbBase
{

    protected array $masterResults = array();
    protected array $masterReleasesResults = array();

    /**
     * @param int $maId discogs master id
     * @param Config $config OPTIONAL override default config
     * @param Logger $cache OPTIONAL override the default logger with a custom one.
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(int $maId, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setMasterId($maId);
    }

    /**
     * Get all data from a master id
     * @return array
     *       [id]        int
     *       [title]     string Al 15 Jaar Gewoon André
     *       [artists]   array
     *           [0] array
     *               [id]                int 282287
     *               [name]              string André Hazes
     *               [nameVariation]     string
     *       [year] string 1994
     *       [genres] array
     *           [0] string Pop
     *       [styles] array
     *           [0] string Ballad
     *       [notes] string
     *       [images] array
     *           [front] array
     *               [urlFull]   string
     *               [url150]    string
     *               [width]     int 600
     *               [height]    int 461
     *       [videos] array
     *           [0] array
     *               [url]          string https://www.youtube.com/watch?v=SgkrznBUspI
     *               [title]        string André Hazes ‎– Al 15 Jaar Gewoon André - TV Reclame (1994)
     *               [description]  string EMI, 1994
     *               [runtime]      int (in seconds)
     *       [media] array indexes represent real disc and tracknumbers
     *           [1] array (discnumber)
     *              [1] array (tracknumber) (if vinyl: A1 is returned)
     *                  [title] string Eenzame Kerst
     *                  [trackArtists] array
     *                      [0] array
     *                          [id] int 282287
     *                          [name] string André Hazes
     *                          [role] string Written by
     *                  [discNumber]    string '1'
     *                  [trackNumber]   string '1'
     *                  [type]          string 'track'
     *                  [lengthText]    string '3:07'
     *                  [LengthMinSec] array
     *                      [min]     int 3
     *                      [seconds] int 7
     *                  [lengthSeconds] int 187
     */
    public function getMasterData(): array
    {
        // Data request
        $masterData = $this->api->doMasterLookup($this->masterID);
        if (empty((array) $masterData)) {
            return $this->masterResults;
        }
        if (
            isset($masterData->artists) &&
            is_array($masterData->artists) &&
            count($masterData->artists) > 0
           )
        {
            foreach ($masterData->artists as $artistsValue) {
                if (!empty((array) $artistsValue)) {
                    $artists[] = array(
                        'id' => $artistsValue->id ?? null,
                        'name' => $artistsValue->name ?? null,
                        'nameVariation' => $artistsValue->anv ?? null
                    );
                }
            }
        }
        // genres
        $genres = array();
        if (
            isset($masterData->genres) &&
            is_array($masterData->genres) &&
            count($masterData->genres) > 0
           )
        {
            foreach ($masterData->genres as $genreValue) {
                if (!empty($genreValue)) {
                    $genres[] = $genreValue;
                }
            }
        }
        // styles
        $styles = array();
        if (
            isset($masterData->styles) &&
            is_array($masterData->styles) &&
            count($masterData->styles) > 0
           )
        {
            foreach ($masterData->styles as $styleValue) {
                if (!empty($styleValue)) {
                    $styles[] = $styleValue;
                }
            }
        }
        // images
        $images = array();
        if (
            isset($masterData->images) &&
            is_array($masterData->images) &&
            count($masterData->images) > 0
           )
        {
            foreach ($masterData->images as $indexKey => $imagesValue) {
                if (!empty((array) $imagesValue)) {
                    if (isset($imagesValue->type) && $imagesValue->type === 'primary') {
                        $images['front'] = array(
                            'urlFull' => $imagesValue->resource_url ?? null,
                            'url150' => $imagesValue->uri150 ?? null,
                            'width' => $imagesValue->width ?? 0,
                            'height' => $imagesValue->height ?? 0
                        );
                    } elseif ($indexKey == 1) {
                        $images['back'] = array(
                            'urlFull' => $imagesValue->resource_url ?? null,
                            'url150' => $imagesValue->uri150 ?? null,
                            'width' => $imagesValue->width ?? 0,
                            'height' => $imagesValue->height ?? 0
                        );
                    } elseif (!empty($imagesValue->resource_url) && !empty($imagesValue->uri150)) {
                        $images['other'][] = array(
                            'urlFull' => $imagesValue->resource_url ?? null,
                            'url150' => $imagesValue->uri150 ?? null,
                            'width' => $imagesValue->width ?? 0,
                            'height' => $imagesValue->height ?? 0
                        );
                    }
                }
            }
        }
        // videos (youtube)
        $videos = array();
        if (
            isset($masterData->videos) &&
            is_array($masterData->videos) &&
            count($masterData->videos) > 0
           )
        {
            foreach ($masterData->videos as $videosValue) {
                $videos[] = array(
                    'url' => $videosValue->uri ?? null,
                    'title' => $videosValue->title ?? null,
                    'description' => $videosValue->description ?? null,
                    'runtime' => $videosValue->duration ?? 0
                );
            }
        }
        // tracklist array indexes represent real disc and track number
        // disc and tracknumbers are always string (if vinyl: A1 is returned)
        // LengthMinSec and LengthSeconds are always int
        $media = array();
        if (
            isset($masterData->tracklist) &&
            is_array($masterData->tracklist) &&
            count($masterData->tracklist) > 0
           )
        {
            $trackNumber = '1';
            $discNumber = '1';
            foreach ($masterData->tracklist as $track) {
                if (isset($track->position) && stripos($track->position, '-') !== false) {
                    // multiple discs
                    $trackPositionParts = explode('-', $track->position);
                    $discNumber = trim($trackPositionParts[0]);
                    if (isset($trackPositionParts[1])) {
                        $trackNumber = trim($trackPositionParts[1]);
                    }
                } else {
                    // single disc
                    $trackNumber = trim($track->position);
                }
                $sec = 0;
                $min = 0;
                $totalSeconds = 0;
                if (isset($track->duration)) {
                    $time = explode(':', $track->duration);
                    $min = (int) trim($time[0]);
                    if (isset($time[1])) {
                        $sec = (int) trim($time[1]);
                    }
                    $totalSeconds = ($min * 60) + $sec;
                }
                // track artists (not the disc/album artists!
                $trackArtists = array();
                if (
                    isset($track->extraartists) &&
                    is_array($track->extraartists) &&
                    count($track->extraartists) > 0
                   )
                {
                    foreach ($track->extraartists as $trackArtist) {
                        if (!empty((array) $trackArtist)) {
                            $trackArtists[] = array(
                                'id' => $trackArtist->id ?? null,
                                'name' => $trackArtist->name ?? null,
                                'role' => $trackArtist->role ?? null
                            );
                        }
                    }
                }
                $media[$discNumber][$trackNumber] = array(
                    'title' => $track->title ?? null,
                    'trackArtists' => $trackArtists,
                    'discNumber' => $discNumber,
                    'trackNumber' => $trackNumber,
                    'type' => $track->type_ ?? null,
                    'lengthText' => $track->duration ?? null,
                    'LengthMinSec' => array('min' => $min, 'seconds' => $sec),
                    'lengthSeconds' => $totalSeconds
                );
            }
        }
        // results array
        $this->masterResults = array(
            'id' => $masterData->id ?? null,
            'title' => $masterData->title ?? null,
            'artists' => $artists,
            'year' => $masterData->year ?? 0,
            'genres' => $genres,
            'styles' => $styles,
            'notes' => $masterData->notes ?? null,
            'images' => $images,
            'videos' => $videos,
            'media' => $media
        );
        return $this->masterResults;
    }

    /**
     * Get all releases of this master ID categorized by format type
     * @return array
     *      [Vinyl] array
     *          [0] array
     *              [id]            int 173765 (depending on category this is master or release id)
     *              [catno]         string catalog number
     *              [title]         string Curb
     *              [released]      string release year
     *              [country]       string
     *              [format]        string (LP, Album)
     *              [label]         string
     *              [thumb]         string thumbnail url
     *              [majorFormats]  array
     */
    public function getMasterReleasesData(): array
    {
        // Data request
        $masterReleasesData = $this->api->doMasterReleasesLookup($this->masterID);
        if (empty($masterReleasesData)) {
            return $this->masterReleasesResults;
        }
        foreach ($masterReleasesData as $value) {
            // major formats
            $majorFormats = array();
            if (
                isset($value->major_formats) &&
                is_array($value->major_formats) &&
                count($value->major_formats) > 0
               )
            {
                $category = !empty($value->major_formats[0]) ?
                                   str_replace(' ', '_', $value->major_formats[0]) : 'unknown';
                foreach ($value->major_formats as $formatValue) {
                    if (!empty($formatValue)) {
                        $majorFormats[] = $formatValue;
                    }
                }
            }
            $this->masterReleasesResults[$category][] = array(
                'id' => $value->id ?? null,
                'catno' => $value->catno ?? null,
                'title' => $value->title ?? null,
                'released' => $value->released ?? null,
                'country' => $value->country ?? null,
                'format' => $value->format ?? null,
                'label' => $value->label ?? null,
                'thumb' => $value->thumb ?? null,
                'majorFormats' => $majorFormats
            );
        }
        return $this->masterReleasesResults;
    }

}
