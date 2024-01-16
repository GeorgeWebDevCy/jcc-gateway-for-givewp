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
