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

class Search extends MdbBase
{

    /**
     * Search for titles matching text input search query
     * @param string $searchString input search string
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
     * @return array
     *  [0]  array
     *      [id]            int release id
     *      [title]         string 3 Doors Down - The Better Life
     *      [year]          string 2000
     *      [country]       string Malaysia (can be continent like Europe)
     *      [format] array
     *          [0] string CD
     *          [1] string Album
     *      [label] array
     *          [0] string Republic Records
     *          [1] string Universal Records
     *      [barcode] array
     *          [0] string 601215392029
     *      [catalogNumber] string 153 920-2
     *      [type]          string release
     */
    public function search(string $searchString = '', array $searchParameters = array()): array
    {
        $results = array();
        $data = $this->api->doTextSearch($searchString, $searchParameters);
        if (
            !isset($data->results) &&
            !is_array($data->results) &&
            count($data->results) < 1
           )
        {
            return $results;
        }
        foreach ($data->results as $value) {
            // formats (CD, LP Album)
            $formats = array();
            if (
                isset($value->format) &&
                is_array($value->format) &&
                count($value->format) > 0
               )
            {
                foreach ($value->format as $formatValue) {
                    if (!empty($formatValue)) {
                        $formats[] = $formatValue;
                    }
                }
            }
            // labels
            $labels = array();
            if (
                isset($value->label) &&
                is_array($value->label) &&
                count($value->label) > 0
               )
            {
                foreach ($value->label as $labelValue) {
                    if (!empty($labelValue)) {
                        $labels[] = $labelValue;
                    }
                }
            }
            // barcodes
            $barcodes = array();
            if (
                isset($value->barcode) &&
                is_array($value->barcode) &&
                count($value->barcode) > 0
               )
            {
                foreach ($value->barcode as $barcodeValue) {
                    if (!empty($barcodeValue)) {
                        $barcodes[] = $barcodeValue;
                    }
                }
            }
            $results[] = array(
                'id' => $value->id ?? null,
                'title' => $value->title ?? null,
                'year' => $value->year ?? null,
                'country' => $value->country ?? null,
                'format' => $formats,
                'label' => array_values(array_unique($labels)),
                'barcode' => array_values(array_unique($barcodes)),
                'catalogNumber' => $value->catno ?? null,
                'type' => $value->type ?? null
            );
        }
        return $results;
    }

}
