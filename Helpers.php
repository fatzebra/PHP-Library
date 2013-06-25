<?php
/**
* Fat Zebra PHP Gateway Library
*
* The original source for this library, including its tests can be found at
* https://github.com/fatzebra/PHP-Library
*
* Please visit http://docs.fatzebra.com.au for details on the Fat Zebra API
* or https://www.fatzebra.com.au/help for support.
*
* Patches, pull requests, issues, comments and suggestions always welcome.
*
* @package FatZebra
*/
namespace FatZebra;

class Helpers {
	/**
	* Check if int is a timestamp
	* @param int
	* @return boolean
	*/
	static public function isTimestamp($timestamp) {
		return ((int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);

	}

}
