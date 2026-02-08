<?php
/**
 * Plugin Name: KornitX Bridge for WooCommerce
 * Description: Smartlink iFrame + postMessage bridge (Type 2 print job), variation mapping, thumbnails, edit link, and Create Orders API submission.
 * Version: 0.2.9d
 * Author: Paul Sutton + Copilot
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.3
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
if ( ! defined( 'KX_WC_VERSION' ) ) define( 'KX_WC_VERSION', '0.2.9d' );
if ( ! defined( 'KX_WC_FILE' ) ) define( 'KX_WC_FILE', __FILE__ );
if ( ! defined( 'KX_WC_DIR' ) ) define( 'KX_WC_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'KX_WC_URL' ) ) define( 'KX_WC_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'KX_WC_SMARTLINK_ORIGIN' ) ) define( 'KX_WC_SMARTLINK_ORIGIN', 'https://g3d-app.com' );

// Includes
require_once KX_WC_DIR . 'includes/helpers.php';
require_once KX_WC_DIR . 'includes/class-kx-settings.php';
require_once KX_WC_DIR . 'includes/class-kx-admin-product.php';
require_once KX_WC_DIR . 'includes/class-kx-variant-resolver.php';
require_once KX_WC_DIR . 'includes/class-kx-frontend.php';
require_once KX_WC_DIR . 'includes/class-kx-order.php';
require_once KX_WC_DIR . 'includes/class-kx-debug.php';

// Bootstrap
add_action( 'plugins_loaded', function(){
    \KX_WC\Settings::init();
    \KX_WC\Admin_Product::init();
    \KX_WC\Variant_Resolver::init();
    \KX_WC\Frontend::init();
    \KX_WC\Order::init();
    \KX_WC\Debug::init();
});
