<?php defined('SYSPATH') or die('No direct script access.');
/* The API exception handler */
class Api_Exception extends Exception
{
	/* Ensure the message is entered */
	public function __construct($message, $code, $severity=0, $file=NULL, $line=NULL)
	{
		/* Call the parent constructor */
		parent::__construct($message, $code, NULL);

		/* Set the correct HTTP/1.1 status code based off the error code */
		$request = Request::instance();

		if (array_key_exists($code, Request::$messages)) {
			$request->status = $code;
		} else {
			$request->status = 400;
		}
	}
}
?>
