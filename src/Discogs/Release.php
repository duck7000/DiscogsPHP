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
 * A release on discogs API
 * @author ed (github user: duck7000)
 */
class Release extends MdbBase
{

    protected array $results = array();

    /**
     * @param int $id discogs release id
     * @param Config $config OPTIONAL override default config
     * @param Logger $cache OPTIONAL override the default logger with a custom one.
     * @param CacheInterface $cache OPTIONAL override the default cache with any PSR-16 cache.
     */
    public function __construct(int $id, ?Config $config = null, ?LoggerInterface $logger = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config, $logger, $cache);
        $this->setid($id);
    }

    /**
     * Get all data from release id
     * @return array
     *       [id]        int 14769209
     *       [title]     string Al 15 Jaar Gewoon André
     *       [artists]   array
     *           [0] => Array
     *               [id]                int 282287
     *               [name]              string André Hazes
     *               [nameVariation]     string
     *       [year] string 1994
     *       [released]  string 1994
     *       [country]   string Netherlands
     *       [genres] array
     *           [0] string Pop
     *       [styles] array
     *           [0] string Ballad
     *       [labels] array
     *           [0] array
     *              [id]                int 26126
     *              [name]              string EMI
     *              [entityTypeName]    string Label
     *              [catno]             string 7243 8306582 0
     *       [formats] array
     *           [0] array
     *              [name]          string CD
     *              [quantity]      string 2
     *              [descriptions] array
     *                  [0] string Compilation
     *              [text] string
     *       [identifiers] array
     *           [0] array
     *               [type]         string Barcode
     *               [value]        string 7 24383 06582 0
     *               [description]  string
     *       [notes] string
     *       [images] array
     *           [front] array
     *               [urlFull]   string
     *               [url150]    string
     *               [width]     int 600
     *               [height]    int 461
     *       [credits] array
     *           [0] => Array
     *               [id] int 282287
     *               [name] string André Hazes
     *               [role] string Liner Notes
     *       [companies] array
     *           [0] => Array
     *               [id]                int 26126
     *               [name]              string EMI
     *               [catno]             string
     *               [entityTypeName]    string Manufactured By
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
     *                  [discNumber]    string '1'
     *                  [trackNumber]   string '1'
     *                  [type]          string 'track'
     *                  [lengthText]    string '3:07'
     *                  [LengthMinSec] array
     *                      [min]     int 3
     *                      [seconds] int 7
     *                  [lengthSeconds] int 187
     */
    public function getReleaseData(): array
    {
        // Data request
        $data = $this->api->doLookup($this->releaseID);
        if (empty((array) $data)) {
            return $this->results;
        }
        // Artists
        $artists = array();
        if (
            isset($data->artists) &&
            is_array($data->artists) &&
            count($data->artists) > 0
           )
        {
            foreach ($data->artists as $artistsValue) {
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
            isset($data->genres) &&
            is_array($data->genres) &&
            count($data->genres) > 0
           )
        {
            foreach ($data->genres as $genreValue) {
                if (!empty($genreValue)) {
                    $genres[] = $genreValue;
                }
            }
        }
        // styles
        $styles = array();
        if (
            isset($data->styles) &&
            is_array($data->styles) &&
            count($data->styles) > 0
           )
        {
            foreach ($data->styles as $styleValue) {
                if (!empty($styleValue)) {
                    $styles[] = $styleValue;
                }
            }
        }
        // labels
        $labels = array();
        if (
            isset($data->labels) &&
            is_array($data->labels) &&
            count($data->labels) > 0
           )
        {
            foreach ($data->labels as $labelValue) {
                if (!empty((array) $labelValue)) {
                    $labels[] = array(
                        'id' => $labelValue->id ?? null,
                        'name' => $labelValue->name ?? null,
                        'entityTypeName' => $labelValue->entity_type_name ?? null,
                        'catno' => $labelValue->catno ?? null
                    );
                }
            }
        }
        // formats
        $formats = array();
        if (
            isset($data->formats) &&
            is_array($data->formats) &&
            count($data->formats) > 0
           )
        {
            foreach ($data->formats as $formatValue) {
                // descriptions
                $descriptions = array();
                if (
                    isset($formatValue->descriptions) &&
                    is_array($formatValue->descriptions) &&
                    count($formatValue->descriptions) > 0
                   )
                {
                    foreach ($formatValue->descriptions as $descValue) {
                        if (!empty($descValue)) {
                            $descriptions[] = $descValue;
                        }
                    }
                }
                if (!empty((array) $formatValue)) {
                    $formats[] = array(
                        'name' => $formatValue->name ?? null,
                        'quantity' => $formatValue->qty ?? null,
                        'descriptions' => $descriptions,
                        'text' => $formatValue->text ?? null
                    );
                }
            }
        }
        // identifiers (barcodes, label code, price code, matrix/runouts)
        $identifiers = array();
        if (
            isset($data->identifiers) &&
            is_array($data->identifiers) &&
            count($data->identifiers) > 0
           )
        {
            foreach ($data->identifiers as $identifiersValue) {
                if (!empty((array) $identifiersValue)) {
                    $identifiers[] = array(
                        'type' => $identifiersValue->type ?? null,
                        'value' => $identifiersValue->value ?? null,
                        'description' => $identifiersValue->description ?? null
                    );
                }
            }
        }
        // images
        $images = array();
        if (
            isset($data->images) &&
            is_array($data->images) &&
            count($data->images) > 0
           )
        {
            foreach ($data->images as $indexKey => $imagesValue) {
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
        // credits
        $credits = array();
        if (
            isset($data->extraartists) &&
            is_array($data->extraartists) &&
            count($data->extraartists) > 0
           )
        {
            foreach ($data->extraartists as $extraartistsValue) {
                if (!empty($extraartistsValue->name)) {
                    $credits[] = array(
                        'id' => $extraartistsValue->id ?? null,
                        'name' => $extraartistsValue->name ?? null,
                        'role' => $extraartistsValue->role ?? null
                    );
                }
            }
        }
        // companies
        $companies = array();
        if (
            isset($data->companies) &&
            is_array($data->companies) &&
            count($data->companies) > 0
           )
        {
            foreach ($data->companies as $companiesValue) {
                if (!empty($companiesValue->name)) {
                    $companies[] = array(
                        'id' => $companiesValue->id ?? null,
                        'name' => $companiesValue->name ?? null,
                        'catno' => $companiesValue->catno ?? null,
                        'entityTypeName' => $companiesValue->entity_type_name ?? null
                    );
                }
            }
        }
        // videos (youtube)
        $videos = array();
        if (
            isset($data->videos) &&
            is_array($data->videos) &&
            count($data->videos) > 0
           )
        {
            foreach ($data->videos as $videosValue) {
                $videos[] = array(
                    'url' => $videosValue->uri ?? null,
                    'title' => $videosValue->title ?? null,
                    'description' => $videosValue->description ?? null,
                    'runtime' => $videosValue->duration ?? 0
                );
            }
        }
        // tracklist array indexes represent real disc and track number
        // disc and tracknumbers are always string (if vinyl: A1 or B2 is returned)
        // LengthMinSec and LengthSeconds are always int
        $media = array();
        if (
            isset($data->tracklist) &&
            is_array($data->tracklist) &&
            count($data->tracklist) > 0
           )
        {
            $trackNumber = '1';
            $discNumber = '1';
            foreach ($data->tracklist as $track) {
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
                    isset($track->artists) &&
                    is_array($track->artists) &&
                    count($track->artists) > 0
                   )
                {
                    foreach ($track->artists as $trackArtist) {
                        if (!empty((array) $trackArtist)) {
                            $trackArtists[] = array(
                                'id' => $trackArtist->id ?? null,
                                'name' => $trackArtist->name ?? null
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
        $this->results = array(
            'id' => $data->id ?? null,
            'title' => $data->title ?? null,
            'artists' => $artists,
            'year' => $data->year ?? 0,
            'released' => $data->released ?? null,
            'country' => $data->country ?? null,
            'genres' => $genres,
            'styles' => $styles,
            'labels' => $labels,
            'formats' => $formats,
            'identifiers' => $identifiers,
            'notes' => $data->notes ?? null,
            'images' => $images,
            'credits' => $credits,
            'companies' => $companies,
            'videos' => $videos,
            'media' => $media
        );
        return $this->results;
    }

}
