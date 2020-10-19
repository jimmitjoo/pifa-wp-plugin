<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://resilient-brook-fdgmnomofbq9.vapor-farm-c1.com/
 * @since      1.0.0
 *
 * @package    Pifa
 * @subpackage Pifa/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Pifa
 * @subpackage Pifa/includes
 * @author     Jimmie Johansson <jimmitjoo@gmail.com>
 */
class Pifa_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'pifa',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
