<?php defined('SYSPATH') or die('No direct script access.');
/**
* Process a SimpleXMLElement and recursivly translate it into JSON code to be
* returned to the client. We define a __toString method that gets used later in
* the process to return the result of processing.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Creators_Json
{
	private $payload;

	public function __construct($payload)
	{
		$this->payload = $this->generate($payload);
	}

	public function __toString()
	{
		return $this->payload;
	}

	private function generate($payload)
	{
		/* Ensure we have a valid class first */
		if (get_class($payload) == 'Api_Payload') {
			return json_encode($payload->as_array());
		} else {
			throw new Api_Exception('JSON creation error', 400);
		}
	}
}
?>
