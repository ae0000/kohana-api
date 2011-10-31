<?php defined('SYSPATH') or die('No direct script access.');
/**
 * The API creator parses the output payload and converts it into the format that
 * the client set in the Accept header. If the Accept header was not set, the API
 * first checks the format of the input payload and tries to return that, else it
 * checks the API config file to return the default format. Once a format's been
 * chosen, the creator calls the corresponding creator handler to create a string
 * representation of the output payload.
 *
 * @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
 * @package API
 * @copyright (c) 2010 Unmagnify team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * @version $id$
 * @link http://www.stroppytux.net/projects/kohana_api/
 * @since Available since Release 1.0
 */
class Api_Creator
{
	/* Objects used for content negotiation */
	private $core;
	public $payload;

	/**
	 * Load the memory pointer into the instance core variable, then define our
	 * base object used for payload output. The root element name can be set with
	 * the config file for the API.
	 *
	 * @access	public
	 * @param	object	$core	Api_Core object
	 * @return	void
	 */
	public function __construct($core)
	{
		$this->core		= $core;
		$this->payload	= new Api_Payload;
		$root = $this->payload->createElement(Kohana::$config->load('api.root'));
		$this->payload->appendChild($root);
	}

	/**
	 * Generate our output payload using a valid creator set with the outgoing
	 * Content-Type header. Once generated, we store the resulting payload in the
	 * Request::response variable to be returned when Kohana clears the output
	 * buffer and returns it to the client.
	 *
	 * @access	public
	 * @return	void
	 */
	public function set_payload()
	{
		/* Check the output payload type from the Content-Type header */
		$type = $this->core->request->headers['Content-Type'];
		$format = Api_Headers::split_content($type, 'format');
		$creator = 'Api_Creators_'.ucfirst($format);
		$payload = new $creator($this->payload);
		$this->core->request->response = $payload;
	}
}
?>
