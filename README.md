Discogs
=======

PHP library for retrieving CD information from Discogs.<br>
Retrieve most of the information you can see on Discogs page of specific CD.<br>
Search for titles on Discogs by barcode, cd code, artist, album etc.<br>
Get front poster image url.<br>


Quick Start
===========

* If you're not using composer or an autoloader include `bootstrap.php`.
* Get some data
```php
$title = new \Discogs\Release(125489); // id is always int!
$artist = $title->getReleaseData();


Installation
============

This library uses Discogs API so you need a Discogs account, consumerkey and consumerSecret for this to work

Get the files with one of:
* Git clone. Checkout the latest release tag.
* [Zip/Tar download]

### Requirements
* PHP >= 8.3 or higher (lower version are not supported!)
* PHP cURL extension


Searching for a CD
====================

```php
// include "bootstrap.php"; // Load the class in if you're not using an autoloader
$search = new \Discogs\Search();
$results = $search->search('Batmobile');

// $results is an array of Titles
// The array will have artist, title etc.
```
