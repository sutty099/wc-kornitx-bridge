<?php
if (!defined('ABSPATH')) exit;

class WCKX_Logs {
    public static function init(){ add_submenu_page('woocommerce', __('Kornit X Bridge Logs','wc-kornitx-bridge'), __('Kornit X Bridge Logs','wc-kornitx-bridge'), 'manage_woocommerce', 'wckx-logs', [__CLASS__,'render']); }
    public static function render(){ echo '<div class="wrap"><h1>'.esc_html__('Kornit X Bridge Logs','wc-kornitx-bridge').'</h1>'; $logs=get_option(WCKX_LOG_OPTION,[]); if(empty($logs)){ echo '<p>'.esc_html__('No logs yet.','wc-kornitx-bridge').'</p>'; echo '</div>'; return; } echo '<table class="widefat"><thead><tr><th>'.esc_html__('Time','wc-kornitx-bridge').'</th><th>'.esc_html__('Message','wc-kornitx-bridge').'</th><th>'.esc_html__('Context','wc-kornitx-bridge').'</th></tr></thead><tbody>'; foreach($logs as $l){ echo '<tr><td>'.esc_html($l['time']).'</td><td>'.esc_html($l['message']).'</td><td><code>'.esc_html(wp_json_encode($l['context'])).'</code></td></tr>'; } echo '</tbody></table></div>'; }
}
