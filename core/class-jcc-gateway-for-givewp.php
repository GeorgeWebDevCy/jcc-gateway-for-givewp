<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Jcc_Gateway_For_Givewp' ) ) :

	/**
	 * Main Jcc_Gateway_For_Givewp Class.
	 *
	 * @package		JCCGATEWAY
	 * @subpackage	Classes/Jcc_Gateway_For_Givewp
	 * @since		1.0.0
	 * @author		George Nicolaou
	 */
	final class Jcc_Gateway_For_Givewp {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Jcc_Gateway_For_Givewp
		 */
		private static $instance;

		/**
		 * JCCGATEWAY helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Jcc_Gateway_For_Givewp_Helpers
		 */
		public $helpers;

		/**
		 * JCCGATEWAY settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Jcc_Gateway_For_Givewp_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'jcc-gateway-for-givewp' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'jcc-gateway-for-givewp' ), '1.0.0' );
		}

		/**
		 * Main Jcc_Gateway_For_Givewp Instance.
		 *
		 * Insures that only one instance of Jcc_Gateway_For_Givewp exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Jcc_Gateway_For_Givewp	The one true Jcc_Gateway_For_Givewp
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Jcc_Gateway_For_Givewp ) ) {
				self::$instance					= new Jcc_Gateway_For_Givewp;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Jcc_Gateway_For_Givewp_Helpers();
				self::$instance->settings		= new Jcc_Gateway_For_Givewp_Settings();

				//Fire the plugin logic
				new Jcc_Gateway_For_Givewp_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'JCCGATEWAY/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once JCCGATEWAY_PLUGIN_DIR . 'core/includes/classes/class-jcc-gateway-for-givewp-helpers.php';
			require_once JCCGATEWAY_PLUGIN_DIR . 'core/includes/classes/class-jcc-gateway-for-givewp-settings.php';

			require_once JCCGATEWAY_PLUGIN_DIR . 'core/includes/classes/class-jcc-gateway-for-givewp-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'jcc-gateway-for-givewp', FALSE, dirname( plugin_basename( JCCGATEWAY_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.