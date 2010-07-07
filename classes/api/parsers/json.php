<?php defined('SYSPATH') or die('No direct script access.');
/**
* Given a string containing JSON, convert it into a standard format we can use.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Parsers_Json
{
	public $type;
	public $format;
	public $payload;

	/* When instantiated, run checks on the payload */
	public function __construct()
	{
		$this->type		= 'application/json';
		$this->format	= 'json';
	}

	/* Ensure we have a valid json string */
	public function validate($payload)
	{
		return (is_null(json_decode($payload))) ? FALSE : TRUE;
	}

	/* Process the raw data as json */
	public function process($payload)
	{
		/* If we dont have a result stored already, try generate one */
		if (get_class($this->payload) != 'Api_Payload') {
			$payload = json_decode($payload, TRUE);

			switch (json_last_error())
			{
				case JSON_ERROR_NONE:
					$this->payload = new Api_Payload('1.0', 'utf-8');
					$this->payload->from_array($payload, Kohana::config('api.root'));
					break;
				case JSON_ERROR_DEPTH:
					throw new Api_Exception('JSON nested too deep', 400);
					break;
				case JSON_ERROR_CTRL_CHAR:
					throw new Api_Exception('Invalid control character in JSON', 400);
					break;
				case JSON_ERROR_SYNTAX:
					throw new Api_Exception('Invalid JSON syntax', 400);
					break;
				case JSON_ERROR_UTF8:
					throw new Api_Exception('Incorrect character encoding', 400);
					break;
				default:
					throw new Api_Exception('Unknown JSON error', 400);
					break;
			}
		}
	}
}
?>
