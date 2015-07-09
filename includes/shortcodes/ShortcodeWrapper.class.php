<?php

namespace rapidopress\shortcodes;

/**
 * Wrapper for shortcode atts. 
 * 
 * @package WordPress
 * @subpackage Shortcodes
 *
 * @since 4.2.2 ( rapido 0.3 )
 *
 */
class ShortcodeWrapper  implements \ArrayAccess{

	private $tag;
	private $atts = array();
	private $content;

 	public function __construct($tag, $atts = array(), $content = null) { 
		$this->tag = $tag;
		$this->atts = $atts; 
		$this->content = $content;
	}
	public function setAtts($atts)  { $this->atts = $atts; }
	public function offsetSet($var, $value) { $this->atts[$var] = $value; }
    public function offsetExists($var) { return isset($this->atts[$var]); }
    public function offsetUnset($var) {  unset($this->atts[$var]); }
    public function offsetGet($var) { return isset($this->atts[$var]) ? $this->atts[$var] : null; }
	public function __get($var) { return isset($this->atts[$var]) ? $this->atts[$var] : null; }
	public function __set($var, $value) { $this->atts[$var] = $value; }

	/**
	 * shortcode_atts wrapper for \rapido\ShortcodeWrapper
	 *
	 * @since 3.6.0
	 *
	 * @use shortcode_atts
	 *
	 * @param array $vars new atts to combine
	 * @shortcode string to filter ( @see shortcode_atts )
	 * @return new vars array combined
	 */
    public function __invoke($vars, $shortcode = '') {  return $this->atts = \shortcode_atts($vars, $this->atts, $shortcode); }
	public function defaults($vars) { return $this->atts = array_replace($vars, $this->atts); }
	public function mix($vars) { return $this->atts = array_replace($this->atts, $vars); }
	public function whoami() { return $this->tag; }
	public function getContent() { return $this->content; }
	public function theContent() { echo $this->content; }
	public function getAtts() { return $this->atts; }
}
