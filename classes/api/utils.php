<?php defined('SYSPATH') or die('No direct script access.');
/**
* Utilities used by the API to parse and create payloads and headers.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Utils
{
	/**
	* Given a path, the get_handlers method will process all files within the
	* path and return the names of valid handlers.
	*
	* @access	public
	* @param	string	$path
	* @return	array
	*/
	public static function get_handlers($path)
	{
		$result = Kohana::list_files($path);
		array_walk($result,function (&$f,$k){$f = basename($f, EXT);});
		return $result;
	}

	/* Wrapper method to retrieve an array of parsers */
	public static function get_parsers()
	{
		return Api_Utils::get_handlers('classes/api/parsers');
	}

	/* Wrapper method to retrieve an array of creators */
	public static function get_creators()
	{
		return Api_Utils::get_handlers('classes/api/creators');
	}

	/* Wrapper method to retrieve an array of controllers */
	public static function get_controllers()
	{
		return Api_Utils::get_handlers('classes/controller');
	}

	/* Wrapper method to retrieve an array of processors */
	public static function get_processors()
	{
		return Api_Utils::get_handlers('classes/process');
	}

	/**
	* By default, kohana throws an ErrorException when an error occurs and then
	* catches it using its kohana acception handler. This unfortunatly generates
	* loads of output in a format the clients wont be able to process. For this
	* reason, we override the default kohana error handler with our own version
	* that calls an Api_Exception. We can then handle the errors properly and
	* return the error to the client in a format set in its Accept header.
	*
	* @access	public
	* @return	void
	*/
	public static function error_handler($code, $error, $file=NULL, $line=NULL)
	{
		throw new Api_Exception($error, $code, NULL, $file, $line);
		return TRUE;
	}
}
?>
