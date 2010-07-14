<?php defined('SYSPATH') or die('No direct script access.');
/**
* API Core controller.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Core extends Controller
{
	public $utils;
	public $parser;
	public $creator;
	public $headers;

	/**
	* Overwrite the default Controller::before method to parse the input headers
	* and payload. If we receive any errors that cant be dealt with, we set this
	* http header status code to the one thrown by the exception, we then create
	* an entry in the creator payload containing the error message, and finally
	* skip the processing stage to go straight to the Controller:after stage.
	*
	* @access	public
	* @return	void
	*/
	public function before()
	{
		/* Create our utility object and creator payload. */
		set_error_handler(array('Api_Utils','error_handler'));
		$this->utils	= new Api_Utils($this);
		$this->headers	= new Api_Headers($this);
		$this->creator	= new Api_Creator($this);

		/* Process our input headers */
		try {
			$this->headers->get_input_md5();
			$this->headers->get_input_type();
			$this->headers->get_input_length();
			$this->headers->get_input_language();
		/* Error getting headers, send error */
		} catch (Exception $e) {
			$this->request->action = 'skip';
			$node = $this->creator->payload;
			$code = $node->createElement('error', $e->getMessage());
			$root = $node->get_root();
			$root->appendChild($code);
		}

		/* Validate, then store the input payload for processing */
		try {
			$this->parser = new Api_Parser($this);
			$this->parser->load();
			$this->parser->check();
			$this->parser->parse();
		/* Error getting input payload, send error */
		} catch (Exception $e) {
			$this->request->action = 'skip';
			$node = $this->creator->payload;
			$code = $node->createElement('error', $e->getMessage());
			$root = $node->get_root();
			$root->appendChild($code);
		}
	}

	/**
	* Overwrite the default Controller::after method to create the output header
	* and payload. If any errors are encountered during this stage, the output
	* is wiped and a new empty payload is created. This payload then gets the
	* error code added to it. The payload is then returned to the client.
	*
	* @access	public
	* @return	void
	*/
	public function after()
	{
		try {
			/* Set any output headers required by the client */
			$this->headers->get_output_type();
			$this->headers->get_output_encoding();
			$this->headers->get_output_language();
			$this->headers->set_output_language();
			$this->headers->set_output_type();

			/* Generate the output payload data, then add the md5hash */
			$this->creator->set_payload();
			$this->headers->set_output_md5();
		/* Error getting headers, send error */
		} catch (Exception $e) {
			$type = 'application/'.Kohana::config('api.creator.type');
			$this->request->headers['Content-Type'] = $type;
			$this->creator->payload = new Api_Payload;
			$node = $this->creator->payload;
			$code = $node->createElement('error', $e->getMessage());
			$this->creator->payload->appendChild($code);
			$this->creator->set_payload();
			$this->headers->set_output_md5();
		}

		/* Restore the default kohana error handler */
		restore_error_handler();
	}

	/**
	* When there is an error processing the input payload or the input headers,
	* we bypass the processing stage by changing the kohana action to use this
	* skip action. This ensures we dont try process an erroneous payload, but
	* still enables us to generate an output payload.
	*
	* @access	public
	* @return	void
	*/
	final public function action_skip()
	{
	}
}
?>
