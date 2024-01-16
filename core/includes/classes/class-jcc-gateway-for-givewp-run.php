<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Jcc_Gateway_For_Givewp_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		JCCGATEWAY
 * @subpackage	Classes/Jcc_Gateway_For_Givewp_Run
 * @author		George Nicolaou
 * @since		1.0.0
 */
class Jcc_Gateway_For_Givewp_Run{

	/**
	 * Our Jcc_Gateway_For_Givewp_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks(){
	
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_scripts_and_styles' ), 20 );
	
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		wp_enqueue_style( 'jccgateway-backend-styles', JCCGATEWAY_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), JCCGATEWAY_VERSION, 'all' );
		wp_enqueue_script( 'jccgateway-backend-scripts', JCCGATEWAY_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), JCCGATEWAY_VERSION, false );
		wp_localize_script( 'jccgateway-backend-scripts', 'jccgateway', array(
			'plugin_name'   	=> __( JCCGATEWAY_NAME, 'jcc-gateway-for-givewp' ),
		));
	}

}
