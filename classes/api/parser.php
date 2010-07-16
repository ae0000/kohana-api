<?php defined('SYSPATH') or die('No direct script access.');
/**
 * The API parser parses the incoming payload sent by the client. It does so by
 * defining a set of standard methods to first validate that the payload received
 * is exactly what the client expected the API to receive. After that, the parser
 * checks the headers for the Content-Type and then converts the input payload to
 * a standardised Api_Payload object that can be recursed by a custom processor.
 *
 * @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
 * @package API
 * @copyright (c) 2010 Unmagnify team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * @version $id$
 * @link http://www.stroppytux.net/projects/kohana_api/
 * @since Available since Release 1.0
 */
class Api_Parser
{
	/* Objects used for content negotiation */
	private $core;
	private $input;

	/* Details generated from the parser */
	public $type;
	public $format;
	public $method;
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
		$this->core = $core;
	}

	/**
	 * Check the input method supplied by the client. Once chosen, get payload
	 * data and store it to be processed later. If we are not able to handle the
	 * request method, return a 405 (Method Not Allowed) error to the client.
	 *
	 * @access	public
	 * @return	void
	 */
	public function load()
	{
		/* Store the http method for later use */
		$this->method = $_SERVER['REQUEST_METHOD'];

		/* Check that the API allows the method requested */
		if ($this->method, Kohana::config('api.creator.type')) {
			throw new Api_Exception('Method not supported', 405);
		}

		/* Get the payload out of the request */
		switch ($this->method)
		{
			/* POST method used, just capture the payload content */
			case 'POST':
				$this->input = file_get_contents('php://input');
				break;

			/* GET method used, set the correct Content-Type, then get input */
			case 'GET':
			case 'HEAD':
				$this->input = $_SERVER['QUERY_STRING'];
				$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
				$_SERVER['CONTENT_LENGTH'] = strlen($this->input);
				$this->core->headers->get_input_type();
				$this->core->headers->get_input_length();
				break;

			/* Put method used, still needs to be implemented */
			case 'PUT':
			case 'DELETE':
				throw new Api_Exception('Not Implemented', 501);
				break

			/* Send the payload back to the client. A TRACE was requested */
			case 'TRACE':
				$this->input = file_get_contents('php://input');

			/* The chient sent a request method we dont support, return error */
			default:
				throw new Api_Exception('Method not supported', 405);
				break;
		}
	}

	/**
	 * Check all the data placed in the input payload ties up the the header data
	 * sent to us. This ensures that the data sent to us hasnt been mangled when
	 * getting sent to us. It ensures we have a payload, then checks that the 
	 * length is what the client thought it should be, then checks that the md5
	 * hash matches the one the client sent. If any problems arise, return an
	 * error to the client.
	 *
	 * @access	public
	 * @return	void
	 */
	public function check()
	{
		/* Ensure there is a payload to process */
		if ($this->check_length(0)) {
			throw new Api_Exception('No payload detected', 400);
		}

		/* Ensure that the content length is correct */
		if (!$this->check_length($this->core->headers->input_length)) {
			throw new Api_Exception('Content length incorrect', 411);
		}

		/* Check that if the md5 is set, that it matches */
		if (!$this->check_md5($this->core->headers->input_md5)) {
			throw new Api_Exception('MD5 hash missmatch', 400);
		}
	}

	/* Check if the provided md5sum matches the content */
	public function check_md5($md5hash)
	{
		/* If enforced and no Content-MD5, throw an exception */
		if (Kohana::config('api.parser.enforce_checksum') && !$md5hash) {
			return FALSE;
		}

		/* If the checksum isnt valid, throw an exception */
		return ($md5hash == hash('md5', $this->input) || !$md5hash) ?TRUE:FALSE;
	}

	/* Check the length matches the content */
	public function check_length($length)
	{
		return ($length == strlen($this->input)) ? TRUE : FALSE;
	}

	/**
	 * Parse the input payload provided by the client into a standard format that
	 * we can use. If the client sent a Content-Type header, we force the parser
	 * to the one provided. If on the other hand, the client did not supply a one
	 * we loop through our parsers checking if any of them know what to do with
	 * the payload data. If this fails, we return an error type 415 (Unsupported
	 * Media Type) to the client.
	 *
	 * @access	public
	 * @return	void
	 */
	public function parse()
	{
		/* If a Content-Type was set, force the parser to use it */
		if (!is_null($this->core->headers->input_format)) {
			$format = $this->core->headers->input_format;

			/* Check the parser exists for the Content-Type */
			if (array_search($format, Api_Utils::get_parsers())) {
				/* Parse the input */
				$parser			= 'Api_Parsers_'.ucfirst($format);
				$parser			= new $parser();
				$parser->process($this->input);

				/* Store the results */
				$this->payload	= $parser->payload;
				$this->format	= $parser->format;
				$this->type		= $parser->type;

			/* Client sent us an un-recognised payload, return an error */
			} else {
				throw new Api_Exception('Payload format not supported', 415);
			}

		/* No Content-Type was set, try guess one */
		} else {
			foreach (Api_Utils::get_parsers() as $format) {
				$parser				= 'Api_Parsers_'.ucfirst($format);
				$tmp_parser			= new $parser();
				if ($tmp_parser->validate($this->input)) {
					$this->payload	= $tmp_parser->payload;
					$this->format	= $tmp_parser->format;
					$this->type		= $tmp_parser->type;
					return;
				}
			}

			/* No parsers processed the payload, return an error */
			throw new Api_Exception('Payload format not supported', 415);
		}
	}
}
?>
