<?php
/**
 * JCC Gateway For GiveWP
 *
 * @package       JCCGATEWAY
 * @author        George Nicolaou
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   JCC Gateway For GiveWP
 * Plugin URI:    https://www.georgenicolaou.me/plugins/gncy-jcc-give-wp
 * Description:   JCC Payment Gateway for GiveWP
 * Version:       1.0.0
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
define( 'JCCGATEWAY_VERSION',		'1.0.0' );

// Plugin Root File
define( 'JCCGATEWAY_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'JCCGATEWAY_PLUGIN_BASE',	plugin_basename( JCCGATEWAY_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'JCCGATEWAY_PLUGIN_DIR',	plugin_dir_path( JCCGATEWAY_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'JCCGATEWAY_PLUGIN_URL',	plugin_dir_url( JCCGATEWAY_PLUGIN_FILE ) );

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
		'givewp_jcc_payment_gateway_version' => '1.0.0',
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
 * Process payment when the form is submitted
 */
add_action( 'give_gateway_jcc-gateway-for-givewp', 'jcc_givewp_process_payment' );
function jcc_givewp_process_payment( $purchase_data ) {
    // Retrieve the payment data
    $payment_data = array(
        'price'           => $purchase_data['price'],
        'give_form_title' => $purchase_data['post_data']['give-form-title'],
        'give_form_id'    => $purchase_data['post_data']['give-form-id'],
        'give_price_id'   => $purchase_data['post_data']['give-price-id'],
        'date'            => $purchase_data['date'],
        'user_email'      => $purchase_data['user_email'],
        'purchase_key'    => $purchase_data['purchase_key'],
        'currency'        => give_get_currency( $purchase_data['give_form_id'] ),
        'user_info'       => $purchase_data['user_info'],
        'status'          => 'publish',
    );

    // Record the pending payment
    $donation_id = give_insert_payment( $payment_data );

    // Check if the payment was processed successfully
    if ( ! $donation_id ) {
        // Payment failed, show an error
        give_set_error( 'jcc_givewp_payment_failed', __( 'Payment failed while processing. Please try again.', 'jcc-gateway-for-givewp' ) );
        give_send_back_to_checkout();

        return;
    }

    // Get the gateway settings
    $gateway_settings = jcc_givewp_get_gateway_settings();

    // Prepare the payment parameters
    $payment_params = array(
        'total'      => give_format_amount( $purchase_data['price'] ),
        'currency'   => $purchase_data['post_data']['give-currency'],
        'language'   => 'en',
        'method'     => $purchase_data['post_data']['card-type'],
        'merchantID' => $gateway_settings['givewp_jcc_payment_gateway_test_mode'] ? $gateway_settings['givewp_jcc_payment_gateway_test_merchant_id'] : $gateway_settings['givewp_jcc_payment_gateway_production_merchant_id'],
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