<?php
/**
 * Plugin Name: WC Kornit X Bridge
 * Description: Sends WooCommerce orders to Kornit X (Create Orders API) and provides basic status/logs.
 * Version: 0.2.7a
 * Author: Your Team
 * Text Domain: wc-kornitx-bridge
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! defined('WCKX_PATH') ) define('WCKX_PATH', plugin_dir_path(__FILE__));
if ( ! defined('WCKX_URL') )  define('WCKX_URL',  plugin_dir_url(__FILE__));
if ( ! defined('WCKX_OPTION_GROUP') ) define('WCKX_OPTION_GROUP', 'wckx_settings');
if ( ! defined('WCKX_OPTION_NAME') )  define('WCKX_OPTION_NAME',  'wckx_settings');
if ( ! defined('WCKX_LOG_OPTION') )   define('WCKX_LOG_OPTION',   'wckx_logs');

register_activation_hook(__FILE__, function(){
    if ( false === get_option(WCKX_OPTION_NAME, false) ) {
        add_option(WCKX_OPTION_NAME, [], '', 'no'); // do not autoload secrets
    }
    if ( false === get_option(WCKX_LOG_OPTION, false) ) {
        add_option(WCKX_LOG_OPTION, [], '', 'no'); // do not autoload logs
    }
});

add_action('plugins_loaded', function(){
    if ( ! class_exists('WooCommerce') ) {
        add_action('admin_notices', function(){
            echo '<div class="notice notice-error"><p>'
               . esc_html__('WC Kornit X Bridge requires WooCommerce to be active.','wc-kornitx-bridge')
               . '</p></div>';
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
});
