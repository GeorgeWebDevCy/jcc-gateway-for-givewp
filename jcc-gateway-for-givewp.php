<?php
/**
 * JCC Gateway For GiveWP
 *
 * @package       JCCGATEWAY
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.0.5
 *
 * @wordpress-plugin
 * Plugin Name:   JCC Gateway For GiveWP
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gncy-jcc-give-wp
 * Description:   JCC Payment Gateway for GiveWP
 * Version:       1.0.5
 * Author:        George Nicolaou
 * Author URI:    https://www.georgenicolaou.me/
 * Text Domain:   jcc-gateway-for-givewp
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with JCC Gateway For GiveWP. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'JCCGATEWAY_NAME',			'JCC Gateway For GiveWP' );

// Plugin version
define( 'JCCGATEWAY_VERSION',		'1.0.5' );

// Plugin Root File
define( 'JCCGATEWAY_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'JCCGATEWAY_PLUGIN_BASE',	plugin_basename( JCCGATEWAY_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'JCCGATEWAY_PLUGIN_DIR',	plugin_dir_path( JCCGATEWAY_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'JCCGATEWAY_PLUGIN_URL',	plugin_dir_url( JCCGATEWAY_PLUGIN_FILE ) );

// Option name used to persist the plugin activity log.
define( 'JCCGATEWAY_LOG_OPTION',	'jcc_givewp_logs' );

// Default maximum number of log entries to persist.
define( 'JCCGATEWAY_LOG_LIMIT',	200 );

/**
 * Load the main class for the core functionality
 */
require_once JCCGATEWAY_PLUGIN_DIR . 'core/class-jcc-gateway-for-givewp.php';

/* Github plugin updater code */
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/GeorgeWebDevCy/jcc-gateway-for-givewp',
    __FILE__,
    'jcc-gateway-for-givewp'
);
$myUpdateChecker->setBranch('main');


/**
 * Persist a plugin log entry so administrators can review gateway activity.
 *
 * @since 1.0.5
 *
 * @param string $message Log message.
 * @param array  $context Additional contextual data to assist with debugging.
 * @param string $level   Severity level (info, warning, error).
 *
 * @return void
 */
function jcc_givewp_log_event( $message, $context = array(), $level = 'info' ) {
    if ( ! is_string( $message ) ) {
        return;
    }

    $message = trim( wp_strip_all_tags( $message ) );

    if ( '' === $message ) {
        return;
    }

    $level = strtoupper( preg_replace( '/[^a-z]/i', '', (string) $level ) );

    if ( '' === $level ) {
        $level = 'INFO';
    }

    $sanitized_context = array();

    if ( is_array( $context ) ) {
        $index = 0;

        foreach ( $context as $key => $value ) {
            $index++;
            $key = is_string( $key ) ? sanitize_key( $key ) : 'item_' . $index;

            if ( '' === $key ) {
                $key = 'item_' . $index;
            }

            if ( is_scalar( $value ) || ( is_object( $value ) && method_exists( $value, '__toString' ) ) ) {
                $sanitized_context[ $key ] = wp_strip_all_tags( (string) $value );
            } else {
                $sanitized_context[ $key ] = wp_json_encode( $value );
            }
        }
    }

    $logs = get_option( JCCGATEWAY_LOG_OPTION, array() );

    if ( ! is_array( $logs ) ) {
        $logs = array();
    }

    array_unshift(
        $logs,
        array(
            'time'    => current_time( 'timestamp' ),
            'level'   => $level,
            'message' => $message,
            'context' => $sanitized_context,
        )
    );

    $limit = (int) apply_filters( 'jcc_givewp_log_limit', JCCGATEWAY_LOG_LIMIT );

    if ( $limit > 0 && count( $logs ) > $limit ) {
        $logs = array_slice( $logs, 0, $limit );
    }

    update_option( JCCGATEWAY_LOG_OPTION, $logs, false );
}

/**
 * Retrieve the stored gateway log entries.
 *
 * @since 1.0.5
 *
 * @return array
 */
function jcc_givewp_get_log_entries() {
    $logs = get_option( JCCGATEWAY_LOG_OPTION, array() );

    if ( ! is_array( $logs ) ) {
        return array();
    }

    return $logs;
}

/**
 * Register the admin menu where gateway activity logs are displayed.
 *
 * @since 1.0.5
 *
 * @return void
 */
add_action( 'admin_menu', 'jcc_givewp_register_logs_admin_menu' );
function jcc_givewp_register_logs_admin_menu() {
    add_menu_page(
        __( 'JCC Gateway Logs', 'jcc-gateway-for-givewp' ),
        __( 'JCC Gateway Logs', 'jcc-gateway-for-givewp' ),
        'manage_options',
        'jcc-givewp-logs',
        'jcc_givewp_render_logs_page',
        'dashicons-clipboard',
        56
    );
}

/**
 * Handle the submission that clears the stored log entries.
 *
 * @since 1.0.5
 *
 * @return void
 */
add_action( 'admin_post_jcc_givewp_clear_logs', 'jcc_givewp_handle_clear_logs' );
function jcc_givewp_handle_clear_logs() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to clear the JCC Gateway log.', 'jcc-gateway-for-givewp' ) );
    }

    check_admin_referer( 'jcc_givewp_clear_logs' );

    delete_option( JCCGATEWAY_LOG_OPTION );

    $redirect_url = add_query_arg(
        array(
            'page'                    => 'jcc-givewp-logs',
            'jcc_givewp_logs_cleared' => '1',
        ),
        admin_url( 'admin.php' )
    );

    wp_safe_redirect( $redirect_url );
    exit;
}

/**
 * Render the admin screen that displays stored gateway activity logs.
 *
 * @since 1.0.5
 *
 * @return void
 */
function jcc_givewp_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $logs        = jcc_givewp_get_log_entries();
    $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'JCC Gateway Logs', 'jcc-gateway-for-givewp' ) . '</h1>';

    if ( isset( $_GET['jcc_givewp_logs_cleared'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The gateway log was cleared successfully.', 'jcc-gateway-for-givewp' ) . '</p></div>';
    }

    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:20px;">';
    wp_nonce_field( 'jcc_givewp_clear_logs' );
    echo '<input type="hidden" name="action" value="jcc_givewp_clear_logs" />';
    submit_button( __( 'Clear Logs', 'jcc-gateway-for-givewp' ), 'delete', 'jcc-givewp-clear-logs', false );
    echo '</form>';

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__( 'Date', 'jcc-gateway-for-givewp' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Level', 'jcc-gateway-for-givewp' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Message', 'jcc-gateway-for-givewp' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Context', 'jcc-gateway-for-givewp' ) . '</th>';
    echo '</tr></thead>';

    echo '<tbody>';

    if ( empty( $logs ) ) {
        echo '<tr><td colspan="4">' . esc_html__( 'No gateway activity has been logged yet.', 'jcc-gateway-for-givewp' ) . '</td></tr>';
    } else {
        foreach ( $logs as $entry ) {
            $timestamp = isset( $entry['time'] ) ? absint( $entry['time'] ) : 0;
            $level     = isset( $entry['level'] ) ? $entry['level'] : 'INFO';
            $message   = isset( $entry['message'] ) ? $entry['message'] : '';
            $context   = isset( $entry['context'] ) && is_array( $entry['context'] ) ? $entry['context'] : array();

            $formatted_time = $timestamp ? ( function_exists( 'wp_date' ) ? wp_date( $date_format, $timestamp ) : date_i18n( $date_format, $timestamp ) ) : '';

            echo '<tr>';
            echo '<td>' . esc_html( $formatted_time ) . '</td>';
            echo '<td>' . esc_html( strtoupper( $level ) ) . '</td>';
            echo '<td>' . esc_html( $message ) . '</td>';
            echo '<td>';

            if ( ! empty( $context ) ) {
                foreach ( $context as $context_key => $context_value ) {
                    echo '<div><strong>' . esc_html( $context_key ) . ':</strong> ' . esc_html( $context_value ) . '</div>';
                }
            } else {
                echo '&mdash;';
            }

            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}



//Make sure Givewp is active and if not show a notice amd keep it inactive until Givewp is active
add_action( 'admin_init', 'jcc_givewp_plugin_active' );
function jcc_givewp_plugin_active() {
	if ( is_plugin_active( 'give/give.php' ) ) {
		return;
	} else {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', 'jcc_givewp_plugin_inactive_notice' );
	}
}

//notice for Givewp inactive
function jcc_givewp_plugin_inactive_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><?php _e( 'JCC Gateway For GiveWP requires GiveWP plugin to be active. Please activate GiveWP plugin first.', 'jcc-gateway-for-givewp' ); ?></p>
	</div>
	<?php
}

// delete the settings when the plugin is deactivated to avoid any conflicts
register_deactivation_hook( __FILE__, 'jcc_givewp_deactivate' );
function jcc_givewp_deactivate() {
    delete_option( 'give_settings_jcc-gateway-for-givewp' );
}


//set the default settings on plugin activation if the settings are not in the database
register_activation_hook( __FILE__, 'jcc_givewp_activate' );
function jcc_givewp_activate() {
    if ( ! get_option( 'give_settings_jcc-gateway-for-givewp' ) ) {
        update_option( 'give_settings_jcc-gateway-for-givewp', jcc_givewp_default_gateway_settings() );
    }
}

// Get the gateway settings
function jcc_givewp_get_gateway_settings() {
    return give_get_settings( 'jcc-gateway-for-givewp' );
}




// Register the gateway
add_filter( 'give_payment_gateways', 'jcc_givewp_register_gateway' );
function jcc_givewp_register_gateway( $gateways ) {
    $gateways['jcc-gateway-for-givewp'] = array(
        'admin_label'    => __( 'JCC Gateway For GiveWP', 'jcc-gateway-for-givewp' ),
        'checkout_label' => __( 'JCC Gateway For GiveWP', 'jcc-gateway-for-givewp' ),
    );

    return $gateways;
}

// Add the gateway settings section
add_filter( 'give_get_sections_gateways', 'jcc_givewp_add_gateway_section' );
function jcc_givewp_add_gateway_section( $sections ) {
    $sections['jcc-gateway-for-givewp'] = __( 'JCC Gateway For GiveWP', 'jcc-gateway-for-givewp' );
    return $sections;
}

// Add the gateway settings
add_filter( 'give_get_settings_gateways', 'jcc_givewp_add_gateway_settings' );

function jcc_givewp_add_gateway_settings( $settings ) {
    // Add HTML heading
    $current_section = give_get_current_setting_section();

    if ( 'jcc-gateway-for-givewp' === $current_section ) {
        // Add heading HTML and subheader HTML and display them before the settings
        $heading_html    = '<h2>' . __( 'JCC Gateway For GiveWP', 'jcc-gateway-for-givewp' ) . '</h2>';
        $subheader_html  = '<p>' . __( 'JCC’s payment gateway offers real-time and batch payment processing. It uses all available security measures to prevent fraudulent transactions and ensure data safety yet it’s easy to integrate with merchants’ systems. In addition, it allows merchants to review and manage transactions, prepare reports, etc. through a user-friendly, intuitive administration interface. Another feature the plugin offers is the ability for the merchant to define a prefix value that will be appended in the merchant order id that is sent to JCCgateway. The current plugin supports making payment via HTTP Post redirect to JCC payment gateway and also refunds via JCC Web Services\'s endpoint, called Financial Service.', 'jcc-gateway-for-givewp' ) . '</p>';
        // Display the HTML
        echo $heading_html . $subheader_html;
        $settings = array_merge(
            $settings,
            array(
                array(
                    'id'   => 'jcc-gateway-for-givewp_settings',
                    'type' => 'title',
                ),
                // Enable/Disable JCC Payment Gateway
                array(
                    'id'            => 'givewp_jcc_payment_gateway_enabled',
                    'name'          => __( 'Enable/Disable JCC Payment Gateway', 'givewp' ),
                    'type'          => 'checkbox',
                    'wrapper_class' => 'give-toggle-checkbox',
                ),
                // Title
                array(
                    'id'      => 'givewp_jcc_payment_gateway_title',
                    'name'    => __( 'Title', 'givewp' ),
                    'type'    => 'text',
                    'default' => __( 'Credit/Debit card', 'givewp' ),
                ),
                // Test Mode
                array(
                    'id'      => 'givewp_jcc_payment_gateway_test_mode',
                    'name'    => __( 'Test Mode', 'givewp' ),
                    'type'    => 'checkbox',
                ),
                // Test Financial Service WSDL
                array(
                    'id'      => 'givewp_jcc_payment_gateway_test_financial_service_wsdl',
                    'name'    => __( 'Test Financial Service WSDL', 'givewp' ),
                    'type'    => 'text',
                ),
                // Test Request URL
                array(
                    'id'      => 'givewp_jcc_payment_gateway_test_request_url',
                    'name'    => __( 'Test Request URL', 'givewp' ),
                    'type'    => 'text',
                ),
                // Test Merchant ID
                array(
                    'id'      => 'givewp_jcc_payment_gateway_test_merchant_id',
                    'name'    => __( 'Test Merchant ID', 'givewp' ),
                    'type'    => 'text',
                ),
                // Test Password
                array(
                    'id'      => 'givewp_jcc_payment_gateway_test_password',
                    'name'    => __( 'Test Password', 'givewp' ),
                    'type'    => 'password',
                ),
                // Production Financial Service WSDL
                array(
                    'id'      => 'givewp_jcc_payment_gateway_production_financial_service_wsdl',
                    'name'    => __( 'Production Financial Service WSDL', 'givewp' ),
                    'type'    => 'text',
                ),
                // Production Request URL
                array(
                    'id'      => 'givewp_jcc_payment_gateway_production_request_url',
                    'name'    => __( 'Production Request URL', 'givewp' ),
                    'type'    => 'text',
                ),
                // Production Merchant ID
                array(
                    'id'      => 'givewp_jcc_payment_gateway_production_merchant_id',
                    'name'    => __( 'Production Merchant ID', 'givewp' ),
                    'type'    => 'text',
                ),
                // Production Password
                array(
                    'id'      => 'givewp_jcc_payment_gateway_production_password',
                    'name'    => __( 'Production Password', 'givewp' ),
                    'type'    => 'password',
                ),
                // Merchant Order ID format
                array(
                    'id'      => 'givewp_jcc_payment_gateway_custom_order_id',
                    'name'    => __( 'Merchant Order ID format', 'givewp' ),
                    'type'    => 'select',
                    'options' => array(
                        'Alphanumeric1' => __( 'Alphanumeric starting with the prefix "give_order_"', 'givewp' ),
                        'Alphanumeric2' => __( 'Alphanumeric', 'givewp' ),
                        'Numeric'       => __( 'Numeric (matches the Order # found in the Orders section of admin\'s page)', 'givewp' ),
                    ),
                ),
                // Merchant Order ID Prefix
                array(
                    'id'      => 'givewp_jcc_payment_gateway_merchant_order_id_prefix',
                    'name'    => __( 'Merchant Order ID Prefix', 'givewp' ),
                    'type'    => 'text',
                ),
                // Version
                array(
                    'id'      => 'givewp_jcc_payment_gateway_version',
                    'name'    => __( 'Version', 'givewp' ),
                    'type'    => 'text',
                ),
                // Acquirer ID
                array(
                    'id'      => 'givewp_jcc_payment_gateway_acquirer_id',
                    'name'    => __( 'Acquirer ID', 'givewp' ),
                    'type'    => 'text',
                ),
                // Capture Flag
                array(
                    'id'      => 'givewp_jcc_payment_gateway_capture_flag',
                    'name'    => __( 'Capture Flag', 'givewp' ),
                    'type'    => 'select',
                    'options' => array(
                        'A' => __( 'Automatic', 'givewp' ),
                        'M' => __( 'Manual', 'givewp' ),
                    ),
                ),
                // Signature Method
                array(
                    'id'      => 'givewp_jcc_payment_gateway_signature_method',
                    'name'    => __( 'Signature Method', 'givewp' ),
                    'type'    => 'select',
                    'options' => array(
                        'SHA1' => __( 'SHA1', 'givewp' ),
                    ),
                ),
                // Billing Info
                array(
                    'id'      => 'givewp_jcc_payment_gateway_send_billing_info',
                    'name'    => __( 'Billing Info', 'givewp' ),
                    'type'    => 'checkbox',
                ),
                // Shipping Info
                array(
                    'id'      => 'givewp_jcc_payment_gateway_send_shipping_info',
                    'name'    => __( 'Shipping Info', 'givewp' ),
                    'type'    => 'checkbox',
                ),
                // General Info
                array(
                    'id'      => 'givewp_jcc_payment_gateway_send_general_info',
                    'name'    => __( 'General Info', 'givewp' ),
                    'type'    => 'checkbox',
                ),
                array(
                    'id'   => 'jcc-gateway-for-givewp_settings',
                    'type' => 'sectionend',
                ),
            )
        );

		return $settings;
    }

    

    // If the settings are not in the database, then set them to default or empty
    if ( ! get_option( 'give_settings_jcc-gateway-for-givewp' ) ) {
        update_option( 'give_settings_jcc-gateway-for-givewp', jcc_givewp_default_gateway_settings() );

    }
    return $settings;
}

function jcc_givewp_default_gateway_settings()
{
	//set default settings
	 $default_settings = array(
		'givewp_jcc_payment_gateway_enabled' => 'on',
		'givewp_jcc_payment_gateway_title' => 'Credit/Debit card',
		'givewp_jcc_payment_gateway_test_mode' => 'on',
		'givewp_jcc_payment_gateway_test_financial_service_wsdl' => 'https://tjccpg.jccsecure.com/PgWebService/services/FinancialService?wsdl',
		'givewp_jcc_payment_gateway_test_request_url' => 'https://tjccpg.jccsecure.com/EcomPayment/RedirectAuthLink',
		'givewp_jcc_payment_gateway_test_merchant_id' => '0097789010',
		'givewp_jcc_payment_gateway_test_password' => 'yUS4HRFU',
		'givewp_jcc_payment_gateway_production_financial_service_wsdl' => 'https://jccpg.jccsecure.com/PgWebService/services/FinancialService?wsdl',
		'givewp_jcc_payment_gateway_production_request_url' => 'https://jccpg.jccsecure.com/EcomPayment/RedirectAuthLink',
		'givewp_jcc_payment_gateway_production_merchant_id' => '000000000000000000000',
		'givewp_jcc_payment_gateway_production_password' => '111111111111111111111',
		'givewp_jcc_payment_gateway_custom_order_id' => 'Alphanumeric1',
		'givewp_jcc_payment_gateway_merchant_order_id_prefix' => 'give_order_',
		'givewp_jcc_payment_gateway_version' => '1.0.5',
		'givewp_jcc_payment_gateway_acquirer_id' => '000000000000000000000',
		'givewp_jcc_payment_gateway_capture_flag' => 'A',
		'givewp_jcc_payment_gateway_signature_method' => 'SHA1',
		'givewp_jcc_payment_gateway_send_billing_info' => 'on',
		'givewp_jcc_payment_gateway_send_shipping_info' => 'on',
		'givewp_jcc_payment_gateway_send_general_info' => 'on',
	);
	return $default_settings;
}

/**
 * add link to settings page
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'jcc_givewp_add_action_links' );
function jcc_givewp_add_action_links( $links ) {
    $settings_link = array(
        '<a href="' . admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=jcc-gateway-for-givewp' ) . '">' . __( 'Settings', 'jcc-gateway-for-givewp' ) . '</a>',
    );
    return array_merge( $settings_link, $links );
}


/**
 * Determine the language code that should be sent to JCC based on the current form and active multilingual plugins.
 *
 * @since 1.0.1
 *
 * @param int $give_form_id GiveWP form ID.
 *
 * @return string Two letter language code.
 */
function jcc_givewp_get_payment_language( $give_form_id ) {
    $language = '';

    if ( $give_form_id ) {
        $language_details = apply_filters( 'wpml_post_language_details', null, $give_form_id );

        if ( is_array( $language_details ) && ! empty( $language_details['language_code'] ) ) {
            $language = $language_details['language_code'];
        }
    }

    if ( ! $language && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
        $language = ICL_LANGUAGE_CODE;
    }

    if ( ! $language && function_exists( 'pll_current_language' ) ) {
        $pll_language = pll_current_language();

        if ( $pll_language ) {
            $language = $pll_language;
        }
    }

    if ( ! $language ) {
        $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

        if ( $locale ) {
            $language = substr( $locale, 0, 2 );
        }
    }

    $language = strtolower( substr( preg_replace( '/[^a-zA-Z]/', '', (string) $language ), 0, 2 ) );

    if ( ! $language ) {
        $language = 'en';
    }

    /**
     * Filters the language code that is sent to the JCC gateway.
     *
     * @since 1.0.1
     *
     * @param string $language     Two letter language code.
     * @param int    $give_form_id GiveWP form ID.
     */
    return apply_filters( 'jcc_givewp_payment_language', $language, $give_form_id );
}

/**
 * Determine the currency code that should be stored with the donation.
 *
 * @since 1.0.2
 *
 * @param int   $give_form_id GiveWP form ID.
 * @param array $post_data    Posted form data.
 *
 * @return string Three letter currency code.
 */
function jcc_givewp_get_donation_currency( $give_form_id, $post_data ) {
    $currency_candidates = array();

    if ( isset( $post_data['give-currency'] ) && '' !== $post_data['give-currency'] ) {
        $currency_candidates[] = $post_data['give-currency'];
    }

    if ( isset( $post_data['give_currency'] ) && '' !== $post_data['give_currency'] ) {
        $currency_candidates[] = $post_data['give_currency'];
    }

    if ( $give_form_id && function_exists( 'give_get_currency' ) ) {
        $currency_candidates[] = give_get_currency( $give_form_id );
    }

    if ( function_exists( 'give_get_currency' ) ) {
        $currency_candidates[] = give_get_currency();
    }

    if ( function_exists( 'give_get_option' ) ) {
        $currency_candidates[] = give_get_option( 'currency', 'EUR' );
    }

    $currency_candidates[] = 'EUR';

    $currency = 'EUR';

    foreach ( $currency_candidates as $candidate ) {
        if ( is_string( $candidate ) || is_numeric( $candidate ) ) {
            $candidate = strtoupper( trim( (string) $candidate ) );

            if ( '' !== $candidate ) {
                $currency = $candidate;
                break;
            }
        }
    }

    /**
     * Filters the currency code that is stored with the donation.
     *
     * @since 1.0.2
     *
     * @param string $currency     Currency code.
     * @param int    $give_form_id GiveWP form ID.
     * @param array  $post_data    Posted form data.
     */
    return apply_filters( 'jcc_givewp_donation_currency', $currency, $give_form_id, $post_data );
}



/**
 * Process payment when the form is submitted
 */
add_action( 'give_gateway_jcc-gateway-for-givewp', 'jcc_givewp_process_payment' );
function jcc_givewp_process_payment( $purchase_data ) {
    $post_data = isset( $purchase_data['post_data'] ) && is_array( $purchase_data['post_data'] ) ? $purchase_data['post_data'] : array();

    $give_form_id = 0;

    if ( isset( $purchase_data['give_form_id'] ) ) {
        $give_form_id = absint( $purchase_data['give_form_id'] );
    } elseif ( isset( $post_data['give-form-id'] ) ) {
        $give_form_id = absint( $post_data['give-form-id'] );
    } elseif ( isset( $post_data['give_form_id'] ) ) {
        $give_form_id = absint( $post_data['give_form_id'] );
    }

    $currency = jcc_givewp_get_donation_currency( $give_form_id, $post_data );

    $gateway_settings = jcc_givewp_get_gateway_settings();
    $is_test_mode     = ! empty( $gateway_settings['givewp_jcc_payment_gateway_test_mode'] );
    $mode             = $is_test_mode ? 'test' : 'live';

    $card_type = isset( $post_data['card-type'] ) ? $post_data['card-type'] : '';

    $amount            = isset( $purchase_data['price'] ) ? $purchase_data['price'] : 0;
    $formatted_amount  = give_format_amount( $amount );

    jcc_givewp_log_event(
        'Processing GiveWP donation via the JCC gateway.',
        array(
            'give_form_id' => $give_form_id,
            'currency'     => $currency,
            'amount'       => $formatted_amount,
            'mode'         => $mode,
        )
    );

    // Retrieve the payment data
    $payment_data = array(
        'price'           => $amount,
        'give_form_title' => isset( $post_data['give-form-title'] ) ? $post_data['give-form-title'] : '',
        'give_form_id'    => $give_form_id,
        'give_price_id'   => isset( $post_data['give-price-id'] ) ? $post_data['give-price-id'] : '',
        'date'            => isset( $purchase_data['date'] ) ? $purchase_data['date'] : '',
        'user_email'      => isset( $purchase_data['user_email'] ) ? $purchase_data['user_email'] : '',
        'purchase_key'    => isset( $purchase_data['purchase_key'] ) ? $purchase_data['purchase_key'] : '',
        'currency'        => $currency,
        'user_info'       => isset( $purchase_data['user_info'] ) ? $purchase_data['user_info'] : array(),
        'status'          => 'publish',
        'gateway'         => 'jcc-gateway-for-givewp',
        'mode'            => $mode,
    );

    // Record the pending payment
    $donation_id = give_insert_payment( $payment_data );

    // Check if the payment was processed successfully
    if ( ! $donation_id ) {
        jcc_givewp_log_event(
            'Failed to insert the pending GiveWP donation record for JCC processing.',
            array(
                'give_form_id' => $give_form_id,
                'currency'     => $currency,
                'amount'       => $formatted_amount,
                'mode'         => $mode,
            ),
            'error'
        );

        // Payment failed, show an error
        give_set_error( 'jcc_givewp_payment_failed', __( 'Payment failed while processing. Please try again.', 'jcc-gateway-for-givewp' ) );
        give_send_back_to_checkout();

        return;
    }

    jcc_givewp_log_event(
        'Created pending JCC donation awaiting gateway redirection.',
        array(
            'donation_id' => $donation_id,
            'give_form_id'=> $give_form_id,
            'currency'    => $currency,
            'amount'      => $formatted_amount,
            'mode'        => $mode,
        )
    );

    if ( function_exists( 'give_update_payment_meta' ) ) {
        give_update_payment_meta( $donation_id, '_give_payment_currency', $currency );
    } else {
        update_post_meta( $donation_id, '_give_payment_currency', $currency );
    }

    // Prepare the payment parameters
    $merchant_id = $is_test_mode
        ? ( isset( $gateway_settings['givewp_jcc_payment_gateway_test_merchant_id'] ) ? $gateway_settings['givewp_jcc_payment_gateway_test_merchant_id'] : '' )
        : ( isset( $gateway_settings['givewp_jcc_payment_gateway_production_merchant_id'] ) ? $gateway_settings['givewp_jcc_payment_gateway_production_merchant_id'] : '' );

    $payment_params = array(
        'total'      => $formatted_amount,
        'currency'   => $currency,
        'language'   => jcc_givewp_get_payment_language( $give_form_id ),
        'method'     => $card_type,
        'merchantID' => $merchant_id,
    );

    // Include additional information if billing is enabled
    if ( isset( $purchase_data['post_data']['billing_info_enabled'] ) && $purchase_data['post_data']['billing_info_enabled'] ) {
        $payment_params['billing_info'] = array(
            'first_name' => $purchase_data['post_data']['give-billing-first-name'],
            'last_name'  => $purchase_data['post_data']['give-billing-last-name'],
            'address'    => $purchase_data['post_data']['give-billing-address'],
            'city'       => $purchase_data['post_data']['give-billing-city'],
            'state'      => $purchase_data['post_data']['give-billing-state'],
            'zip'        => $purchase_data['post_data']['give-billing-zip'],
            'country'    => $purchase_data['post_data']['give-billing-country'],
            'email'      => $purchase_data['post_data']['give-billing-email'],
        );
    }

    // Include additional information if shipping is enabled
    if ( isset( $purchase_data['post_data']['shipping_info_enabled'] ) && $purchase_data['post_data']['shipping_info_enabled'] ) {
        $payment_params['shipping_info'] = array(
            'first_name' => $purchase_data['post_data']['give-shipping-first-name'],
            'last_name'  => $purchase_data['post_data']['give-shipping-last-name'],
            'address'    => $purchase_data['post_data']['give-shipping-address'],
            'city'       => $purchase_data['post_data']['give-shipping-city'],
            'state'      => $purchase_data['post_data']['give-shipping-state'],
            'zip'        => $purchase_data['post_data']['give-shipping-zip'],
            'country'    => $purchase_data['post_data']['give-shipping-country'],
        );
    }

    // Add custom parameters if needed
    $custom_params = apply_filters( 'jcc_givewp_custom_payment_params', array(), $purchase_data );

    if ( ! empty( $custom_params ) && is_array( $custom_params ) ) {
        $payment_params = array_merge( $payment_params, $custom_params );
    }

    // Redirect to JCC gateway for payment
    jcc_givewp_redirect_to_gateway( $donation_id, $payment_params );
}

/**
 * Redirect to JCC gateway for payment
 */
function jcc_givewp_redirect_to_gateway( $donation_id, $payment_params ) {
    // Build the redirect URL
    $redirect_url = jcc_givewp_build_redirect_url( $donation_id, $payment_params );

    // Redirect to the gateway
    wp_redirect( $redirect_url );
    exit;
}

/**
 * Build the redirect URL for JCC gateway
 */
function jcc_givewp_build_redirect_url( $donation_id, $payment_params ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Set the gateway URL based on the test mode
    $gateway_url = $gateway_settings['givewp_jcc_payment_gateway_test_mode'] ? $gateway_settings['givewp_jcc_payment_gateway_test_request_url'] : $gateway_settings['givewp_jcc_payment_gateway_production_request_url'];

    // Set the redirect parameters
    $redirect_params = array(
        'total'      => $payment_params['total'],
        'currency'   => $payment_params['currency'],
        'language'   => $payment_params['language'],
        'method'     => $payment_params['method'],
        'merchantID' => $payment_params['merchantID'],
        'orderID'    => jcc_givewp_get_order_id( $donation_id ),
        'successURL' => jcc_givewp_get_success_url( $donation_id ),
        'failURL'    => jcc_givewp_get_fail_url( $donation_id ),
        'cancelURL'  => jcc_givewp_get_cancel_url( $donation_id ),
    );

    // Include billing information if available
    if ( isset( $payment_params['billing_info'] ) && is_array( $payment_params['billing_info'] ) ) {
        $redirect_params['billing_info'] = $payment_params['billing_info'];
    }

    // Include shipping information if available
    if ( isset( $payment_params['shipping_info'] ) && is_array( $payment_params['shipping_info'] ) ) {
        $redirect_params['shipping_info'] = $payment_params['shipping_info'];
    }

    // Include custom parameters if available
    if ( isset( $payment_params['custom_params'] ) && is_array( $payment_params['custom_params'] ) ) {
        $redirect_params = array_merge( $redirect_params, $payment_params['custom_params'] );
    }

    // Build the final redirect URL
    //$redirect_url = add_query_arg( $redirect_params, $gateway_url );
    $redirect_url = 'https://www.jccsmart.com/e-bill/invoices/9150/pay';
    // Allow filtering of the redirect URL
    return apply_filters( 'jcc_givewp_redirect_url', $redirect_url, $donation_id, $payment_params );
}

/**
 * Get the order ID for JCC gateway
 */
function jcc_givewp_get_order_id( $donation_id ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Get the order ID format
    $order_id_format = $gateway_settings['givewp_jcc_payment_gateway_custom_order_id'];

    // Get the order ID prefix
    $order_id_prefix = $gateway_settings['givewp_jcc_payment_gateway_merchant_order_id_prefix'];

    // Generate the order ID based on the selected format
    switch ( $order_id_format ) {
        case 'Alphanumeric1':
            $order_id = 'wc_order_' . $donation_id;
            break;
        case 'Alphanumeric2':
            $order_id = 'wc_order_' . uniqid();
            break;
        case 'Numeric':
            $order_id = $donation_id;
            break;
        default:
            $order_id = '';
            break;
    }

    // Add the prefix to the order ID
    $order_id_with_prefix = $order_id_prefix ? $order_id_prefix . $order_id : $order_id;

    // Allow filtering of the order ID
    return apply_filters( 'jcc_givewp_order_id', $order_id_with_prefix, $donation_id );
}

/**
 * Get the success URL for JCC gateway
 */
function jcc_givewp_get_success_url( $donation_id ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Get the base success URL
    $success_url = $gateway_settings['givewp_jcc_payment_gateway_success_url'];

    // Append the donation ID as a parameter
    $success_url .= strpos( $success_url, '?' ) !== false ? '&' : '?';
    $success_url .= 'donation_id=' . $donation_id;

    // Allow filtering of the success URL
    return apply_filters( 'jcc_givewp_success_url', $success_url, $donation_id );
}

/**
 * Get the fail URL for JCC gateway
 */
function jcc_givewp_get_fail_url( $donation_id ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Get the base fail URL
    $fail_url = $gateway_settings['givewp_jcc_payment_gateway_fail_url'];

    // Append the donation ID as a parameter
    $fail_url .= strpos( $fail_url, '?' ) !== false ? '&' : '?';
    $fail_url .= 'donation_id=' . $donation_id;

    // Allow filtering of the fail URL
    return apply_filters( 'jcc_givewp_fail_url', $fail_url, $donation_id );
}

/**
 * Get the cancel URL for JCC gateway
 */
function jcc_givewp_get_cancel_url( $donation_id ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Get the base cancel URL
    $cancel_url = $gateway_settings['givewp_jcc_payment_gateway_cancel_url'];

    // Append the donation ID as a parameter
    $cancel_url .= strpos( $cancel_url, '?' ) !== false ? '&' : '?';
    $cancel_url .= 'donation_id=' . $donation_id;

    // Allow filtering of the cancel URL
    return apply_filters( 'jcc_givewp_cancel_url', $cancel_url, $donation_id );
}

/**
 * Get the IPN URL for JCC gateway
 */
function jcc_givewp_get_ipn_url() {
    return home_url( '/give-jcc-ipn' );
}

/**
 * Add custom query variables for IPN
 */
add_filter( 'query_vars', 'jcc_givewp_add_query_vars' );
function jcc_givewp_add_query_vars( $vars ) {
    $vars[] = 'give-jcc-ipn';
    return $vars;
}

/**
 * Handle IPN requests from JCC gateway
 */
add_action( 'template_redirect', 'jcc_givewp_process_ipn' );
function jcc_givewp_process_ipn() {
    if ( get_query_var( 'give-jcc-ipn' ) ) {
        // Process IPN here
        // ...

        exit;
    }
}

add_action( 'plugins_loaded', 'jcc_givewp_backfill_missing_currency', 1 );
add_action( 'init', 'jcc_givewp_backfill_missing_currency', 20 );

/**
 * Ensure historical donations recorded by this gateway have a valid currency code.
 *
 * GiveWP 3.0+ stores donations in the custom donations table. Prior versions stored donations
 * as a custom post type which does not trigger the fatal error reported by GiveWP 3.0 when the
 * currency column contains NULL. To remain backwards compatible, this routine runs once after
 * the plugin update to backfill the currency for existing donations that were created before
 * the validation fix introduced in version 1.0.2.
 *
 * @since 1.0.2
 * @since 1.0.3 Also repopulates the donation currency meta to prevent GiveWP 3.0 fatal errors.
 *
 * @return void
 */
function jcc_givewp_backfill_missing_currency() {
    if ( get_option( 'jcc_givewp_backfill_missing_currency', false ) ) {
        return;
    }

    global $wpdb;

    $donations_table = $wpdb->prefix . 'give_donations';

    // Confirm the donations table exists before attempting to update.
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $donations_table ) );

    if ( $table_exists !== $donations_table ) {
        return;
    }

    $default_currency = jcc_givewp_get_donation_currency( 0, array() );

    if ( ! is_string( $default_currency ) || '' === $default_currency ) {
        $default_currency = 'EUR';
    }

    jcc_givewp_log_event(
        'Running currency backfill for historical JCC donations.',
        array(
            'default_currency' => $default_currency,
        )
    );

    $donationmeta_table = $wpdb->prefix . 'give_donationmeta';
    $meta_table_exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $donationmeta_table ) );

    $queries_failed                   = false;
    $donation_currency_updates        = 0;
    $currency_meta_updates            = 0;
    $currency_meta_backfills          = 0;
    $legacy_donation_currency_updates = 0;

    if ( $meta_table_exists === $donationmeta_table ) {
        $currency_column_updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$donations_table} donations"
                . " INNER JOIN {$donationmeta_table} meta ON donations.id = meta.donation_id"
                . " SET donations.currency = %s"
                . " WHERE (donations.currency IS NULL OR donations.currency = '')"
                . " AND meta.meta_key = %s"
                . " AND meta.meta_value = %s",
                $default_currency,
                '_give_payment_gateway',
                'jcc-gateway-for-givewp'
            )
        );

        if ( false === $currency_column_updated ) {
            $queries_failed = true;
            jcc_givewp_log_event( 'Failed to normalise the donations currency column for JCC payments.', array(), 'error' );
        } else {
            $donation_currency_updates = (int) $currency_column_updated;
        }

        $currency_meta_updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$donationmeta_table} meta"
                . " INNER JOIN {$donations_table} donations ON donations.id = meta.donation_id"
                . " SET meta.meta_value = %s"
                . " WHERE (meta.meta_value IS NULL OR meta.meta_value = '')"
                . " AND meta.meta_key = %s"
                . " AND donations.gateway = %s",
                $default_currency,
                '_give_payment_currency',
                'jcc-gateway-for-givewp'
            )
        );

        if ( false === $currency_meta_updated ) {
            $queries_failed = true;
            jcc_givewp_log_event( 'Failed to normalise the donation currency meta for JCC payments.', array(), 'error' );
        } else {
            $currency_meta_updates = (int) $currency_meta_updated;
        }

        $missing_currency_meta_donations = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT donations.id"
                . " FROM {$donations_table} donations"
                . " LEFT JOIN {$donationmeta_table} meta"
                . " ON donations.id = meta.donation_id"
                . " AND meta.meta_key = %s"
                . " WHERE meta.meta_id IS NULL"
                . " AND donations.gateway = %s",
                '_give_payment_currency',
                'jcc-gateway-for-givewp'
            )
        );

        if ( is_array( $missing_currency_meta_donations ) && ! empty( $missing_currency_meta_donations ) ) {
            foreach ( $missing_currency_meta_donations as $donation_id ) {
                $donation_id = (int) $donation_id;

                if ( function_exists( 'give_update_payment_meta' ) ) {
                    give_update_payment_meta( $donation_id, '_give_payment_currency', $default_currency );
                } else {
                    update_post_meta( $donation_id, '_give_payment_currency', $default_currency );
                }
            }

            $currency_meta_backfills = count( $missing_currency_meta_donations );
        } elseif ( ! is_array( $missing_currency_meta_donations ) ) {
            $queries_failed = true;
            jcc_givewp_log_event( 'Failed to identify donations that require currency meta backfill.', array(), 'error' );
        }
    } else {
        $currency_column_updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$donations_table}"
                . " SET currency = %s"
                . " WHERE (currency IS NULL OR currency = '')"
                . " AND gateway = %s",
                $default_currency,
                'jcc-gateway-for-givewp'
            )
        );

        if ( false === $currency_column_updated ) {
            $queries_failed = true;
            jcc_givewp_log_event( 'Failed to normalise the legacy donation currency column for JCC payments.', array(), 'error' );
        } else {
            $legacy_donation_currency_updates = (int) $currency_column_updated;
        }
    }

    if ( ! $queries_failed ) {
        update_option( 'jcc_givewp_backfill_missing_currency', 1, false );

        jcc_givewp_log_event(
            'Completed the JCC donation currency backfill.',
            array(
                'default_currency'         => $default_currency,
                'donation_rows_normalised' => $donation_currency_updates,
                'currency_meta_normalised' => $currency_meta_updates,
                'currency_meta_backfilled' => $currency_meta_backfills,
                'legacy_rows_normalised'   => $legacy_donation_currency_updates,
            )
        );
    } else {
        jcc_givewp_log_event(
            'The JCC donation currency backfill did not finish successfully.',
            array(
                'default_currency' => $default_currency,
            ),
            'error'
        );
    }
}


/**
 * Display donation details in the admin
 */
add_filter( 'give_payment_details_payment_information', 'jcc_givewp_display_payment_details', 10, 2 );
function jcc_givewp_display_payment_details( $payment_content, $payment_id ) {
    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Get the order ID
    $order_id = give_get_payment_meta( $payment_id, '_jcc_givewp_order_id', true );

    // Get the transaction ID
    $transaction_id = give_get_payment_transaction_id( $payment_id );

    // Add order ID and transaction ID to the payment details
    $payment_content .= '<p><strong>' . __( 'Order ID:', 'jcc-gateway-for-givewp' ) . '</strong> ' . esc_html( $order_id ) . '</p>';
    $payment_content .= '<p><strong>' . __( 'Transaction ID:', 'jcc-gateway-for-givewp' ) . '</strong> ' . esc_html( $transaction_id ) . '</p>';

    // Add any additional information you want to display
    // ...

    return $payment_content;
}

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  George Nicolaou
 * @since   1.0.0
 * @return  object|Jcc_Gateway_For_Givewp
 */
function JCCGATEWAY() {
	return Jcc_Gateway_For_Givewp::instance();
}

JCCGATEWAY();