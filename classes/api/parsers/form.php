<?php defined('SYSPATH') or die('No direct script access.');
/**
* Given a string containing valid x-www-form-urlencoded data, convert it into a
* standard format we can use.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Parsers_Form
{
	public $type;
	public $format;
	public $payload;

	/* When instantiated, run checks on the payload */
	public function __construct()
	{
		$this->type		= 'application/x-www-form-urlencoded';
		$this->format	= 'x-www-form-urlencoded';
	}

	/* Check if the x-www-form-urlencoded data is valid */
	public function validate($payload)
	{
		try {
			$payload = Api_Parsers_Form::as_array($payload);
			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	/* Process the raw data as x-www-form-urlencoded */
	public function process($payload)
	{
		/* If we dont have a result stored already, try create it */
		if (get_class($this->payload) != 'Api_Payload') {
			$payload = Api_Parsers_Form::as_array($payload);
			$this->payload = new Api_Payload('1.0', 'utf-8');
			$this->payload->from_array($payload);
		}
	}

	/**
	* As php's standard parse_str is hidious, this is a proper version that does
	* handle variables in the query string with the same name, without the need
	* for the non-standards compliant brackets. Basically, this one works like
	* every cgi except php. (btw, who came up with the name 'parse_str'?)
	*
	* @access	private
	* @param	string	$form
	* @return	array
	*/
	private static function as_array($form)
	{
		/* Break the query into key/values pairs */
		$result	= array();
		$pairs	= explode('&', $form);

		/* Add each key/value pair to the array, checking for duplicates */
		foreach ($pairs as $i) {
			list($name, $value) = explode('=', $i, 2);
			if (isset($result[$name])) {
				if(is_array($result[$name])) {
					$result[$name][] = $value;
				} else {
					$result[$name] = array($result[$name], $value);
				}
			} else {
				$result[$name] = $value;
			}
		}

		/* Return the output to the calling method */
		return $result;
	}
}
?>
