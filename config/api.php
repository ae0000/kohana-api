<?php defined('SYSPATH') or die('No direct script access.');
return array(
	/* Values relating to both input and output */
	'root'				=> 'api',		// Name of the default root node

	/* Default values for payload provided BY the client */
	'parser' => array(
		'charset'		=> 'utf8',		// Default Content-Type header
		'language'		=> 'en-us',		// Default Content-Language header
		'enforce_checksum'	=> FALSE,	// Require the client to set Content-MD5

		/* Allowed http methods. ( http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html ) */
		'methods' => array(
						=> 'HEAD',
						=> 'POST',
						=> 'GET',
						//=> 'PUT',
						=> 'TRACE',
						//=> 'DELETE',
		),
	),

	/* Default values for payload presented TO the client */
	'creator' => array(
		'type'			=> 'xml',		// The default creator to use
		'charset'		=> 'utf8',		// Default Content-Type header
		'language'		=> 'en-us',		// Default Content-Language header
	)
);
?>
