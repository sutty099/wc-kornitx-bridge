<?php
if (!defined('ABSPATH')) exit;

class WCKX_HposUi {
    public static function init(){
        add_filter('manage_woocommerce_page_wc-orders_columns', [__CLASS__, 'touch_columns'], 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [__CLASS__,'render_status_badge'], 20, 2);
    }

    public static function touch_columns($columns){ return $columns; }

    public static function render_status_badge($column, $order_id){
        if ($column !== 'order_status') return;
        $order = wc_get_order($order_id);
        $is_sent = $order && $order->get_meta('_wckx_sent');
        echo ' <div class="wckx-badge-wrap">' . WCKX_StatusUi::badge_html(!empty($is_sent)) . '</div>';
    }
}
