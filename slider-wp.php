<?php
/**
 * Plugin Name:         Slider WP
 * Plugin URI:          https://www.slider-wp.com
 * Description:         Slider plugin.
 * Version:             1.0.0
 * Requires at least:   5.2
 * Requires PHP:        7.4
 * Author:              Slider WP
 * Author URI:          https://www.slider-wp.com/
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         slider-wp
 * Domain Path:         /languages

 * SliderWP is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.

 * SliderWP Essential is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with SliderWP. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package SliderWP
 * @category Core
 * @author SliderWP
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The Main Plugin Requirements Checker
 *
 * @since 1.0.0
 */
final class SliderWP {

	/**
	 * Static Property To Hold Singleton Instance
	 *
	 * @var SliderWP The SliderWP Requirement Checker Instance
	 */
	private static $instance;

	/**
	 * Requirements Array
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $requirements = array(
		'php' => array(
			'name'    => 'PHP',
			'minimum' => '7.4',
			'exists'  => true,
			'met'     => false,
			'checked' => false,
			'current' => false,
		),
		'wp'  => array(
			'name'    => 'WordPress',
			'minimum' => '5.2',
			'exists'  => true,
			'checked' => false,
			'met'     => false,
			'current' => false,
		),
	);

	/**
	 * Singleton Instance
	 *
	 * @return SliderWP
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup Plugin Requirements
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Always load translation.
		add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

		// Initialize plugin functionalities or quit.
		$this->requirements_met() ? $this->initialize_modules() : $this->quit();
	}

	/**
	 * Load Localization Files
	 *
	 * @since 1.0
	 * @return void
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_user_locale(), 'slider-wp' );

		unload_textdomain( 'slider-wp' );
		load_textdomain( 'slider-wp', WP_LANG_DIR . '/slider-wp/slider-wp-' . $locale . '.mo' );
		load_plugin_textdomain( 'slider-wp', false, self::get_plugin_dir() . 'languages/' );
	}

	/**
	 * Initialize Plugin Modules
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function initialize_modules() {
        require_once dirname( __FILE__ ) . '/autoloader.php';
		
        // Include the bootstrap file if not loaded.
		if ( ! class_exists( 'SliderWP\Bootstrap' ) ) {
			require_once self::get_plugin_dir() . 'bootstrap.php';
		}

		// Initialize the bootstraper if exists.
		if ( class_exists( 'SliderWP\Bootstrap' ) ) {

			// Initialize all modules through plugins_loaded.
			add_action( 'plugins_loaded', array( $this, 'init' ) );

			register_activation_hook( self::get_plugin_file(), array( $this, 'activate' ) );
			register_deactivation_hook( self::get_plugin_file(), array( $this, 'deactivate' ) );
		}
	}

	/**
	 * Check If All Requirements Are Fulfilled
	 *
	 * @return boolean
	 */
	private function requirements_met() {
		$this->prepare_requirement_versions();

		$passed  = true;
		$to_meet = wp_list_pluck( $this->requirements, 'met' );

		foreach ( $to_meet as $met ) {
			if ( empty( $met ) ) {
				$passed = false;
				continue;
			}
		}

		return $passed;
	}

	/**
	 * Requirement Version Prepare
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function prepare_requirement_versions() {
		foreach ( $this->requirements as $dependency => $config ) {
			switch ( $dependency ) {
				case 'php':
					$version = phpversion();
					break;
				case 'wp':
					$version = get_bloginfo( 'version' );
					break;
				default:
					$version = false;
			}

			if ( ! empty( $version ) ) {
				$this->requirements[ $dependency ]['current'] = $version;
				$this->requirements[ $dependency ]['checked'] = true;
				$this->requirements[ $dependency ]['met']     = version_compare( $version, $config['minimum'], '>=' );
			}
		}
	}

	/**
	 * Initialize everything
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		SliderWP\Bootstrap::instantiate( self::get_plugin_file() );
	}

	/**
	 * Called Only Once While Activation
	 *
	 * @return void
	 */
	public function activate() {
	}

	/**
	 * Called Only Once While Deactivation
	 *
	 * @return void
	 */
	public function deactivate() {
	}

	/**
	 * Quit Plugin Execution
	 *
	 * @return void
	 */
	private function quit() {
		add_action( 'admin_head', array( $this, 'show_plugin_requirements_not_met_notice' ) );
	}

	/**
	 * Show Error Notice For Missing Requirements
	 *
	 * @return void
	 */
	public function show_plugin_requirements_not_met_notice() {
		printf( '<div>Minimum requirements for TimeTics are not met. Please update requirements to continue.</div>' );
	}

	/**
	 * Plugin Current Production Version
	 *
	 * @return string
	 */
	public static function get_version() {
		return '1.0.0';
	}

	/**
	 * Plugin Main File
	 *
	 * @return string
	 */
	public static function get_plugin_file() {
		return __FILE__;
	}

	/**
	 * Plugin Base Directory Path
	 *
	 * @return string
	 */
	public static function get_plugin_dir() {
		return trailingslashit( plugin_dir_path( self::get_plugin_file() ) );
	}

}

SliderWP::get_instance();
