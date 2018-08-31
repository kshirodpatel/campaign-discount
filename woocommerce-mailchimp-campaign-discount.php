<?php
/*
Plugin Name: Woocommerce Mailchimp Campaign Discount
Plugin URI: http://magnigenie.com
Description: This plugin allows you to offer discounts based on the campagin type to the users when they subscribe to your mailing list.
Version: 1.0
Author: Magnigenie
Author URI: http://magnigenie.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wcmcd
*/

// No direct file access
! defined( 'ABSPATH' ) AND exit;

define('WCMCD_FILE', __FILE__);
define('WCMCD_PATH', plugin_dir_path(__FILE__));
define('WCMCD_BASE', plugin_basename(__FILE__));

add_action('plugins_loaded', 'wcmcd_load_textdomain');

function wcmcd_load_textdomain() {
	load_plugin_textdomain( 'wcmcd', false, dirname( plugin_basename( __FILE__ ) ). '/languages/' );
}

require WCMCD_PATH . '/includes/class-mailchimp.php';
require WCMCD_PATH . '/includes/class-wcmcd.php';

new WooCommerce_Mailchimp_Campaign_Discount();