<?php

namespace rapidopress\widgets;

/**
 * Singleton that registers and instantiates WP_Widget classes.
 *
 * @package RapidoPress
 * @subpackage Widgets
 * @since 2.8.0
 */
class Widget_Factory {
	public $widgets = array();

	public function __construct() {
		add_action( 'widgets_init', array( $this, '_register_widgets' ), 100 );
	}

	/**
	 * Register a widget subclass.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param string $widget_class The name of a {@see WP_Widget} subclass.
	 */
	public function register( $widget_class ) {
		$this->widgets[$widget_class] = new $widget_class();
	}

	/**
	 * Un-register a widget subclass.
	 *
	 * @since 2.8.0
	 * @access public
	 *
	 * @param string $widget_class The name of a {@see WP_Widget} subclass.
	 */
	public function unregister( $widget_class ) {
		if ( isset($this->widgets[$widget_class]) )
			unset($this->widgets[$widget_class]);
	}

	/**
	 * Utility method for adding widgets to the registered widgets global.
	 *
	 * @since 2.8.0
	 * @access public
	 */
	public function _register_widgets() {
		global $wp_registered_widgets;
		$keys = array_keys($this->widgets);
		$registered = array_keys($wp_registered_widgets);
		$registered = array_map('_get_widget_id_base', $registered);

		foreach ( $keys as $key ) {
			// don't register new widget if old widget with the same id is already registered
			if ( in_array($this->widgets[$key]->id_base, $registered, true) ) {
				unset($this->widgets[$key]);
				continue;
			}

			$this->widgets[$key]->_register();
		}
	}
}

