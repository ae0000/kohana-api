<?php defined('SYSPATH') or die('No direct script access.');
/**
 * The API_Exception handler extends the standard php Exception class. When an
 * exception is raised, the exception handler checks if a status code has been
 * entered. If one has, it sets the Request::status to the status passed in, if
 * no status was passed in, it sets the Request::status to 400 (Bad Request).
 *
 * @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
 * @package API
 * @copyright (c) 2010 Unmagnify team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * @version $id$
 * @link http://www.stroppytux.net/projects/kohana_api/
 * @since Available since Release 1.0
 */
class Api_Exception extends Exception
{
	/**
	 * Get an instance of the Request object and set the status code to be sent
	 * back to the calling client. If no error code was defined, or it doesnt
	 * contain a valid HTTP/1.1 status code, set the status code to 400.
	 *
	 * @access	public
	 * @param	string	The message that should be returned to the client.
	 * @param	int		The HTTP status code to report back to the client.
	 * @param	int		The severity if the exception raised.
	 * @param	string	The file that contains the code that raised the error.
	 * @param	int		The line number that raised the error.
	 * @return	void
	 */
	public function __construct($message, $code, $severity=0, $file=NULL, $line=NULL)
	{
		/* Call the parent constructor */
		parent::__construct($message, $code, NULL);

		/* Set the correct HTTP/1.1 status code based off the error code */
		$request = Request::$current;

		if (array_key_exists($code, Response::$messages)) {
			Response::factory()->status($code);
		} 
		else {
			Response::factory()->status(400);
		}
	}
}
?>
