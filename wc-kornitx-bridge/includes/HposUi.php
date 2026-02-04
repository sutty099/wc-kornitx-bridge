<?php
if (!defined('ABSPATH')) exit;

class WCKX_HposUi {
    public static function init() {
        // Hook into order preview details and append our status text (HPOS compatible)
        add_filter('woocommerce_admin_order_preview_get_order_details', [__CLASS__, 'preview_badge'], 10, 2);
    }

    public static function preview_badge($details, $order) {
        if (is_a($order, 'WC_Order')) {
            $is_sent = (bool) $order->get_meta('_wckx_sent');
            $details['wckx_status'] = WCKX_StatusUi::badge_html(!empty($is_sent));
        }
        return $details;
    }
}
