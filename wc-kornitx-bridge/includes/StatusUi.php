<?php
if (!defined('ABSPATH')) exit;

class WCKX_StatusUi {
    public static function init(){
        add_filter('manage_edit-shop_order_columns', [__CLASS__,'add_column']);
        add_action('manage_shop_order_posts_custom_column',[__CLASS__,'render_column']);
        add_action('admin_enqueue_scripts',[__CLASS__,'styles']);
    }

    public static function add_column($columns){ $columns['wckx_status'] = __('Kornit X','wc-kornitx-bridge'); return $columns; }
    public static function render_column($column){ if($column!=='wckx_status')return; $order=wc_get_order(get_the_ID()); $is_sent = $order && $order->get_meta('_wckx_sent'); echo self::badge_html(!empty($is_sent)); }

    public static function styles(){
        $is_orders = (isset($_GET['post_type']) && $_GET['post_type']==='shop_order') || (isset($_GET['page']) && $_GET['page']==='wc-orders');
        if(!$is_orders) return;
        wp_register_style('wckx-inline', false); wp_enqueue_style('wckx-inline');
        $css='.wckx-badge{padding:2px 6px;border-radius:3px;font-size:11px;color:#fff;display:inline-block;line-height:1.6;margin-top:2px}.wckx-badge.sent{background:#2c8f2c}.wckx-badge.pending{background:#777}.wckx-badge-wrap{margin-top:2px}';
        wp_add_inline_style('wckx-inline',$css);
    }

    public static function badge_html($is_sent){
        $label=$is_sent?__('Sent','wc-kornitx-bridge'):__('Not Sent','wc-kornitx-bridge');
        $cls=$is_sent?'sent':'pending';
        $aria=$is_sent?__('Kornit X status: Sent','wc-kornitx-bridge'):__('Kornit X status: Not Sent','wc-kornitx-bridge');
        return '<span class="wckx-badge '.esc_attr($cls).'" aria-label="'.esc_attr($aria).'">'.esc_html($label).'</span>';
    }
}
