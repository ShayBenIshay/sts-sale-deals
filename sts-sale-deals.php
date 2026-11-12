<?php
/**
 * Plugin Name: STS Sale Deals
 * Plugin URI: https://shaytechsolutions.com
 * Description: Create sale deals as a hidden product taxonomy, design a label per deal, and show it on products. The same deal can drive Discount Rules PRO.
 * Version: 0.1.0
 * Author: Shay Tech Solutions
 * Author URI: https://shaytechsolutions.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sts-sale-deals
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

define( 'STS_SD_VERSION', '0.1.0' );
define( 'STS_SD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STS_SD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once STS_SD_PLUGIN_DIR . 'includes/class-sts-sale-deals.php';
require_once STS_SD_PLUGIN_DIR . 'includes/class-sts-sale-deals-admin.php';

STS_Sale_Deals::get_instance();
STS_Sale_Deals_Admin::get_instance();

/**
 * Template helper: badge markup for a product, or '' when it is in no deal.
 * Use this from a theme template that bypasses the standard loop hooks.
 *
 * @param WC_Product|int|null $product Product (defaults to the global product).
 * @return string
 */
function sts_sale_deals_badge_html( $product = null ) {
	return STS_Sale_Deals::get_instance()->get_badge_html( $product );
}
