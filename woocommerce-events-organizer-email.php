<?php
/**
 * Plugin Name: Events Calendar Pro Event Organizer Email
 * Plugin URI: http://limbik.co.za
 * Description: Send a custom email to an event organizer specified within the structure of the Events Calendar Pro plugin
 * Author: Theunis Cilliers
 * Author URI: http://limbik.co.za
 * Version: 1.0.1
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('WC_EVENT_ORGANIZER_PLGN_DIR', plugin_dir_path( __FILE__ ) );
define('WC_EVENT_ORGANIZER_TEMPLATE_DIR', WC_EVENT_ORGANIZER_PLGN_DIR . 'templates/' );

/**
 *  Add a custom email to the list of emails WooCommerce should load
 *
 * @since 1.0.1
 * @param array $email_classes available email classes
 * @return array filtered available email classes
 */
function add_event_organizer_woocommerce_mail( $email_classes ) {
	
	// Include custom extended WC_Email class
	require_once( 'includes/class-wc-event-organizer-email.php' );

	// Include Custom Email Notification
	$email_classes['WC_Event_Organizer_Email'] = new WC_Event_Organizer_Email();
	
	return $email_classes;

}
add_filter( 'woocommerce_email_classes', 'add_event_organizer_woocommerce_mail' );