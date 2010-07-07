<?php defined('SYSPATH') or die('No direct script access.');
/**
* Given a string containing xml, process the string converting it into standard
* format we can use.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Parsers_Xml
{
	public $type;
	public $format;
	public $payload;

	/* When instantiated, run checks on the payload */
	public function __construct()
	{
		$this->type		= 'application/xml';
		$this->format	= 'xml';
	}

	/* Check if this is valid xml */
	public function validate($payload)
	{
		try {
			$this->payload = new Api_Payload('1.0', 'utf-8');
			$this->payload->from_xml($payload);
			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	/* Process the raw data as xml */
	public function process($payload)
	{
		/* If we dont have a result stored already, create it */
		if (get_class($this->payload) != 'Api_Payload') {
			$this->payload = new Api_Payload('1.0', 'utf-8');
			$this->payload->from_xml($payload);
		}
	}
}
?>
