<?php
/**
 * Plugin Name: WC Kornit X Bridge
 * Description: WooCommerce → Kornit X bridge with badges (HPOS Status column or classic column), order notes, logs.
 * Version: 0.2.6b
 * Author: Your Company
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 * Text Domain: wc-kornitx-bridge
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('WCKX_VERSION', '0.2.6b');
define('WCKX_PATH', plugin_dir_path(__FILE__));
define('WCKX_URL', plugin_dir_url(__FILE__));

define('WCKX_OPTION_GROUP', 'wckx_settings');
define('WCKX_OPTION_NAME', 'wckx_options');
define('WCKX_LOG_OPTION', 'wckx_logs');

add_action('before_woocommerce_init', function(){
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, false);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);
    }
});

add_action('init', function(){
    load_plugin_textdomain('wc-kornitx-bridge', false, dirname(plugin_basename(__FILE__)).'/languages');
});

class WCKX_Plugin {
    public function __construct(){ add_action('init', [$this,'init'], 1); }
    public function init(){
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function(){
                echo '<div class="notice notice-error"><p>'.esc_html__('WC Kornit X Bridge requires WooCommerce to be active.','wc-kornitx-bridge').'</p></div>';
            });
            return;
        }
        require_once WCKX_PATH.'includes/Helpers.php';
        require_once WCKX_PATH.'includes/OrderSync.php';
        require_once WCKX_PATH.'includes/ProductPodMeta.php';
        require_once WCKX_PATH.'includes/StatusUi.php';
        require_once WCKX_PATH.'includes/HposUi.php';
        require_once WCKX_PATH.'admin/Settings.php';
        require_once WCKX_PATH.'admin/Logs.php';

        WCKX_Settings::init();
        WCKX_ProductPodMeta::init();
        WCKX_OrderSync::init();
        WCKX_StatusUi::init();
        WCKX_HposUi::init();
        WCKX_Logs::init();
    }
}
new WCKX_Plugin();
