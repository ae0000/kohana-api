<?php defined('SYSPATH') or die('No direct script access.');
/**
 * API example controller processes the input payload and headers, then copies it
 * to the output payload to be parsed and returned to the client.
 *
 * @author Gary Stidston-Broadbent <kohana_api@stroppytux.net>
 * @package API
 * @copyright (c) 2010 Unmagnify team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * @version $id$
 * @link http://www.stroppytux.net/projects/kohana_api/
 * @since Available since Release 1.0
 */
class Controller_Api extends Api
{
	/**
	 * This is an example that just returns the input in the format set in the
	 * Accept-Type header. This should be overwritten with your own method or
	 * you should create your own extended controller.
	 *
	 * @access	public
	 * @return	void
	 */
	public function action_index()
	{
		if ($this->parser->payload) {
			$this->creator->payload = $this->parser->payload;
		}
	}
}
?>
