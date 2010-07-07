<?php defined('SYSPATH') or die('No direct script access.');
/**
* The API_Payload object is a SDO that we use to contain content.
*
* @Gary Stidston-Broadbent <kohana_api@stroppytux.net>
* @package API
* @copyright (c) 2010 Unmagnify team
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
* @version $id$
* @link http://www.stroppytux.net/projects/kohana_api/
* @since Available since Release 1.0
*/
class Api_Payload extends DOMDocument
{
	/**
	* Create a new DOMDocument object using the standard DOMDocument::loadXML
	* method. This is just a wrapper to standardise naming conventions.
	*
	* @access	public
	* @param	string	$xml
	* @return	void
	*/
	public function from_xml($xml)
	{
		$this->preserveWhiteSpace = FALSE;
		$this->loadXML($xml);
	}

	/**
	* Convert the DOMDocument object and all its children in to a standard xml
	* string. This is just a wrapper to standardise naming conventions.
	*
	* @access	public
	* @return	string
	*/
	public function as_xml()
	{
		$this->formatOutput = TRUE;
		return $this->saveXML();
	}

	/**
	* Given an array, this method recursivly converts the array key/value pairs
	* into DOMNode objects. When it gets to the end of a stack, it turns any
	* single nested key/value pairs into attributes of the parent element. If
	* an attribute or element already exists, the existing one is converted into
	* an element with the new children nested inside it.
	*
	* @access	public
	* @param	array	$value
	*/
	public function dom_from_array($value, $key=null, DOMElement $element = null)
	{
		/* Set our default values if none have been passed in */
		$key = is_null($key) ? Kohana::config('api.root') : $key;
		$element = is_null($element) ? $this : $element;

		switch (is_array($value))
		{
			case true:
				if (!is_int($key)) {
					$node = $this->createElement($key);
					$element->appendChild($node);
				} else {
					$node = $this->createElement($element->tagName);
					$element->parentNode->appendChild($node);
					//FIXME: remove empty tag! $parent->removeChild($element);
				}
				foreach ($value as $key => $val) {
					$this->from_array($val, $key, $node);
				}
				break;
			case false:
				if (!is_int($key)) {
					$element->setAttribute($key, $value);
				} else {
					$node = $this->createElement($element->tagName, $value);
					$element->parentNode->appendChild($node);
					//FIXME: remove empty tag! $parent->removeChild($element);
				}
				break;
			default:
				throw new Api_Exception('Sorry, written in php!', 400);
				break;
		}
	}

	/**
	* Convert the current DOMDocument and all its children into an array.
	*
	* @access	public
	* @return	array
	*/
	public function dom_as_array(DOMNode $node = null)
	{
		$node = is_null($node) ? $this : $node;
		$result = array();

		switch ($node->nodeType)
		{
			case XML_ELEMENT_NODE:
				print 'E';
				foreach ($node->childNodes as $child) {
					$result[$node->nodeName] = Api_Payload::as_array($child);
				}
			case XML_ATTRIBUTE_NODE:
				print 'A';
			case XML_TEXT_NODE:
				print 'T';
				break;
			case XML_CDATA_SECTION_NODE:
				print 'C';
				break;
			case XML_COMMENT_NODE:
				print 'M';
				break;
			case XML_DOCUMENT_NODE:
				/* If the document node has children, add it as an array */
				foreach ($node->childNodes as $child) {
					$result = array_merge_recursive($result,Api_Payload::as_array($child));
				}
				break;
			default:
				print '?';
				break;
		}
		return $result;
	}

	/**
	* Fetch the root element within the xml document.
	*
	* @access	public
	* @return	object	DOMNode
	*/
	public function get_root()
	{
		/* Get the list of root elements. This should only return 1 result */
		$root = $this->getElementsByTagName(Kohana::config('api.root'));

		/* Ensure we only got one result, if not, raise an error */
		if ($root->length !== 1) {
			throw new Api_Exception('Error finding root node', 400);
		}

		/* Return the root node to the caller */
		return $root->item(0);
	}

	/* FIXME: Hack to get to_array working */
	public function as_array($xml=null)
	{
		/* Create the simplexml element if not created */
		if (is_null($xml)) {
			$xml = simplexml_import_dom($this);
		} elseif(is_subclass_of($xml, 'DOMNode')) {
			$xml = simplexml_import_dom($xml);
		}


		/* Create the main array */
		$return = array();
		$name = $xml->getName();
		$_value = trim((string)$xml);
		if(strlen($_value)==0){$_value = null;}
		if($_value !== null) {$return = $_value;}

		/* Add the attributes to the array */
		$attributes = array();
		foreach($xml->attributes() as $name=>$value) {
			$attributes[$name] = trim($value);
		}
		if(count($attributes)>0) {
			$return = array_merge($return, $attributes);
		}

		/* Add the children to the array */
		$children = array();
		$first = true;
		foreach($xml->children() as $elementName => $child) {
			$value = Api_Payload::as_array($child);
			if(isset($children[$elementName])) {
				if($first) {
					$temp = $children[$elementName];
					unset($children[$elementName]);
					$children[$elementName][] = $temp;
					$first=false;
				}
				$children[$elementName][] = $value;
			} else {
				$children[$elementName] = $value;
			}
		}
		if(count($children)>0) {
			$return = array_merge($return,$children);
		}

		/* All good, lets return */
		return $return;
	}

	/* FIXME: Hack to get from_array working */
	public function from_array(array $array, $start=null)
	{
		$xml = new XmlWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');

		if (is_null($start)) {
			$xml->startElement(Kohana::config('api.root'));
		} else {
			$xml->startElement($start);
		}

		Api_Payload::as_str($xml, $array);
		$xml->endElement();
		return $this->loadXML($xml->outputMemory(true));
	}

	/* Loop through the array keys and create the xml elements */
	private static function as_str(XMLWriter $xml, $data, $label=null)
	{
		foreach($data as $key => $value) {
			/* Decide if we want to add an element or an attribute */
			if(is_array($value)) {
				if (!Api_Payload::is_assoc($value)) {
					Api_Payload::as_str($xml, $value, $key);
				} else {
					$xml->startElement($label ? $label : $key);
					Api_Payload::as_str($xml, $value);
					$xml->endElement();
				}
				continue;
			} else {
				$xml->writeAttribute($key, $value);
			}
		}
	}

	/* Check if an array is associative */
	private static function is_assoc($array)
	{
		return (is_array($array) && (count($array)==0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))))));
	}
}
?>
