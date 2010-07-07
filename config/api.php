<?php defined('SYSPATH') or die('No direct script access.');
return array(
	/* Values relating to both input and output */
	'root'				=> 'api',		// Name of the default root node

	/* Default values for payload provided BY the client */
	'parser' => array(
		'charset'		=> 'utf8',		// Default Content-Type header
		'language'		=> 'en-us',		// Default Content-Language header
	),

	/* Default values for payload presented TO the client */
	'creator' => array(
		'type'			=> 'xml',		// The default creator to use
		'charset'		=> 'utf8',		// Default Content-Type header
		'language'		=> 'en-us',		// Default Content-Language header
	)
);
?>
