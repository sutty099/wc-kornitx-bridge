<?php
if (!defined('ABSPATH')) exit;

class WCKX_StatusUi {
    public static function init() {
        add_action('admin_enqueue_scripts', [__CLASS__, 'styles']);
    }

    public static function styles(){
        $is_orders = (isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order')
                  || (isset($_GET['page']) && $_GET['page'] === 'wc-orders');
        if (!$is_orders) return;

        wp_register_style('wckx-inline', false);
        wp_enqueue_style('wckx-inline');
        $css = '.wckx-badge{padding:2px 6px;border-radius:3px;font-size:11px;color:#fff;display:inline-block;line-height:1.6;margin-top:2px}'
             . '.wckx-badge.sent{background:#2c8f2c}.wckx-badge.pending{background:#777}.wckx-badge-wrap{margin-top:2px}';
        wp_add_inline_style('wckx-inline', $css);
    }

    public static function badge_html($is_sent){
        $label = $is_sent ? __('Sent','wc-kornitx-bridge') : __('Not Sent','wc-kornitx-bridge');
        $cls   = $is_sent ? 'sent' : 'pending';
        $aria  = $is_sent ? __('Kornit X status: Sent','wc-kornitx-bridge') : __('Kornit X status: Not Sent','wc-kornitx-bridge');
        return sprintf('<span class="wckx-badge %1$s" aria-label="%3$s">%2$s</span>',
            esc_attr($cls), esc_html($label), esc_attr($aria));
    }
}
