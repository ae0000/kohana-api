<?php defined('SYSPATH') or die('No direct script access.');
/**
 * A more detailed api controller that calls processes stored within a process
 * directory under classes. Each process contains methods that call the normal
 * Kohana models you define. When a processor or a method doesnt exist, the
 * controller inserts an error message and a status code in the output payload
 * that can then be picked up by the client calling the API.
 *
 * @author Gary Stidston-Broadbent <kohana_api@stroppytux.net>
 * @package API
 * @copyright (c) 2010 Unmagnify team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * @version $id$
 * @link http://www.stroppytux.net/projects/kohana_api/
 * @since Available since Release 1.0
 * *
 */
class Controller_Example extends Api
{
	/**
	 * The index action checks to see what type of request we received. This is
	 * so that wecan accept GET and POST queries without having to change the
	 * way the processors detail with the data we pass into them. If one of the
	 * processors or any of this controllers code raise an exception, we wipe
	 * the output payload and return the exception message to the calling client.
	 *
	 * @author	Gary Stidston-Broadbent <kohana_api@stroppytux.net>
	 * @since	Available since Release 1.0
	 * @access	public
	 * @return	void
	 */
	public function action_index()
	{
		try {
			/* Decide the processing type we need to use */
			switch ($this->parser->format)
			{
				/* Form type processing */
				case 'x-www-form-urlencoded':
					$this->process_form();
					break;

				/* All other processing */
				default:
					$this->process_default();
					break;
			}

		/* An error occured when trying to process the payload, return it */
		} catch (Exception $e) {
			/* Decide on the type of content to return (xml, json, ...) */
			$type = 'application/'.Kohana::config('api.creator.type');
			$this->request->headers['Content-Type'] = $type;

			/* Remove the old output payload and create a new one */
			$this->creator->payload = new Api_Payload;
			$this->creator->payload->from_array(array());
			$node = $this->creator->payload;

			/* Set the output payload error to the exception message */
			$root = $node->get_root();
			$code = $node->createElement('error', $e->getMessage());
			$root->appendChild($code);

			/* Set the payload and add the md5 header */
			$this->creator->set_payload();
			$this->headers->set_output_md5();
		}
	}

	/**
	 * In order to handle form data, we need to refactor the input payload so
	 * that we can accept key/value values instead of nested data. We set the
	 * process class by using the 'section' value, and set the method by using
	 * the 'action' value.
	 *
	 * eg. http://example.com/example?section=user&action=get&userid=10
	 *
	 * @author	Gary Stidston-Broadbent <kohana_api@stroppytux.net>
	 * @since	Available since Release 1.0
	 * @access	protected
	 * @return	void
	 */
	protected function process_form()
	{
		/* Get the root node from the parser payload */
		$node = $this->parser->payload->get_root();
		$result = $this->creator->payload->get_root();

		/* Turn our values into an array */
		$attrs = array();
		foreach ($node->attributes as $attribute) {
			$attrs[$attribute->name] = $attribute->value;
		}

		/* Make sure we have a section and a value */
		if (!array_key_exists('section', $attrs)) {
			throw new Api_Exception('Missing section', 400);
		} elseif (!array_key_exists('action', $attrs)) {
			throw new Api_Exception('Missing action', 400);

		/* Format the payload, and continue with normal processing */
		} else {
			/* Set the xml input structure to a more understandable one */
			$params = array_diff_key($attrs, array('section'=>'', 'action'=>''));
			$tree = array();
			$tree[$attrs['section']] = array();
			$tree[$attrs['section']][$attrs['action']] = $params;

			/* Process the received payload using the normal methods */
			$new_node = new Api_Payload;
			$new_node->from_array($tree);
			$this->parser->payload = $new_node;
			$this->process_default();
		}
	}

	/**
	 * Process normal input payload data. If a comment exists in the payload,
	 * comment will be added into the output payload at the correct place. All
	 * elements at this level are used to name the processor to use. Any other
	 * payload types are ignored.
	 *
	 * @author	Gary Stidston-Broadbent <kohana_api@stroppytux.net>
	 * @since	Available since Release 1.0
	 * @access	protected
	 * @return	void
	 */
	protected function process_default()
	{
		/* Get the root node from the parser payload */
		$node = $this->parser->payload->get_root();
		$result = $this->creator->payload->get_root();

		/* For each top level element we have, instantiate a processor */
		foreach ($node->childNodes as $child) {
			switch ($child->nodeType)
			{
				/* Add any comments found in the input to the output */
				case XML_COMMENT_NODE:
					$comment = $this->creator->payload->createComment($child->data);
					$result->appendChild($comment);
					break;

				/* Call the processor elements found */
				case XML_ELEMENT_NODE:
					$this->call_processor($child, $result);
					break;

				/* Ignore any other node types for now */
				default:
					break;
			}
		}
	}

	/**
	 * Load the processor and execute any actions contained within the control
	 * block. When a processor isn't present, report the error to the output
	 * handler, then continue to the next block.
	 *
	 * @author	Gary Stidston-Broadbent <kohana_api@stroppytux.net>
	 * @since	Available since Release 1.0
	 * @access	private
	 * @param	object	$node	DOMElement object
	 * @param	object	$result	DOMElement object
	 * @return	void
	 */
	private function call_processor($node, $result)
	{
		/* Add an element for this processor */
		$status		= $this->creator->payload->createElement($node->nodeName);
		$handle		= Api_Utils::get_processors();

		/* Make sure the processor exists */
		if (!array_search($node->nodeName, $handle)) {
			$status->setAttribute('status_code', '0');
			$status->setAttribute('status_msg', $node->nodeName.' not found');
			$result->appendChild($status);
			return;
		}

		/* Load the processor using the reflection class */
		$class		= new ReflectionClass('process_'.$node->nodeName);
		$process	= $class->newInstance();

		/* Run our execution stack */
		foreach ($node->childNodes as $child) {
			switch ($child->nodeType)
			{
				/* Add any comments found in the input to the output */
				case XML_COMMENT_NODE:
					$comment = $this->creator->payload->createComment($child->data);
					$status->appendChild($comment);
					break;

				/* Process the action elements */
				case XML_ELEMENT_NODE:
					$this->call_action($child, $status, $class, $process);
					break;

				/* Ignore any other node types for now */
				default:
					break;
			}
		}

		/* Add the results to the creator payload */
		$result->appendChild($status);
	}

	/**
	 * For each action passed into the call_action method, we validate that the
	 * controller has the method defined, if so, we execute it and add the result
	 * to the creator payload.
	 *
	 * @author	Gary Stidston-Broadbent <kohana_api@stroppytux.net>
	 * @since	Available since Release 1.0
	 * @access	private
	 * @param	object	$node		DOMElement object
	 * @param	object	$result		DOMElement object
	 * @param	object	$class		ReflectionClass object
	 * @param	object	$process	Process object
	 * @return	void
	 */
	private function call_action($node, $result, $class, $process)
	{
		/* Make sure the method exists in the processor */
		if (!$class->hasMethod($node->nodeName)) {
			$status = $this->creator->payload->createElement($node->nodeName);
			$status->setAttribute('status_code', '0');
			$status->setAttribute('status_msg', $node->nodeName.' not found');
			$result->appendChild($status);
			return;
		}

		/* Get the attributes for the process */
		$attributes = $this->parser->payload->as_array($node);

		/* Execute the process and store the result within the output payload */
		$method		= $class->getMethod($node->nodeName);
		$data		= $method->invoke($process, $attributes);

		/* Handle validation errors that might be raised */
		if (is_a($data, 'Validate')) {
			$data = array('error' => 'error while trying to '.$node->nodeName);

		/* Handle arrays of results */
		} elseif (!Arr::is_assoc($data)) {
			/* Add the process tag to the output payload */
			$payload	= new Api_payload;
			$payload->from_array(array(), $node->nodeName);
			$payload	= $payload->documentElement;
			$payload	= $this->creator->payload->importNode($payload, TRUE);
			$result->appendChild($payload);

			/* Add the children results to the process tag */
			foreach ($data as $key => $value) {
				/* Add the results to the output payload */
				$child	= new Api_payload;
				$child->from_array($value, $node->nodeName);
				$child	= $child->documentElement;
				$child	= $this->creator->payload->importNode($child, TRUE);
				$payload->appendChild($child);
			}

		/* Handle normal results */
		} else {
			/* Add the results to the output payload */
			$payload	= new Api_payload;
			$payload->from_array($data, $node->nodeName);
			$payload	= $payload->documentElement;
			$payload	= $this->creator->payload->importNode($payload, TRUE);
			$result->appendChild($payload);
		}
	}
}
?>
