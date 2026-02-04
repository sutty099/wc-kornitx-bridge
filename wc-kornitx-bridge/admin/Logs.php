<?php
if (!defined('ABSPATH')) exit;

class WCKX_Logs {
    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    public static function add_menu(){
        add_submenu_page('woocommerce', __('Kornit X Bridge Logs','wc-kornitx-bridge'), __('Kornit X Logs','wc-kornitx-bridge'), 'manage_woocommerce', 'wckx-logs', [__CLASS__,'render']);
    }

    public static function render(){
        if (!current_user_can('manage_woocommerce')) {
            wp_die( esc_html__('You do not have permission to view this page.', 'wc-kornitx-bridge') );
        }
        echo '<div class="wrap">';
        echo '<h1>'.esc_html__('Kornit X Bridge Logs','wc-kornitx-bridge').'</h1>';
        $logs = get_option(WCKX_LOG_OPTION, []);
        if (empty($logs)) {
            echo '<p>'.esc_html__('No logs yet.','wc-kornitx-bridge').'</p></div>';
            return;
        }
        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>'.esc_html__('Time','wc-kornitx-bridge').'</th>';
        echo '<th>'.esc_html__('Message','wc-kornitx-bridge').'</th>';
        echo '<th>'.esc_html__('Context','wc-kornitx-bridge').'</th>';
        echo '</tr></thead><tbody>';
        foreach($logs as $l){
            $time    = isset($l['time'])    ? (string)$l['time']    : '';
            $message = isset($l['message']) ? (string)$l['message'] : '';
            $context = isset($l['context']) ? $l['context']         : [];
            echo '<tr>';
            echo '<td>'.esc_html($time).'</td>';
            echo '<td>'.esc_html($message).'</td>';
            echo '<td><pre style="white-space:pre-wrap;word-break:break-word;">'.esc_html(wp_json_encode($context, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)).'</pre></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
