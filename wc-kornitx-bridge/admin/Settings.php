<?php
if (!defined('ABSPATH')) exit;

class WCKX_Settings {
    public static function init(){
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu(){
        add_submenu_page('woocommerce', __('Kornit X Bridge','wc-kornitx-bridge'), __('Kornit X Bridge','wc-kornitx-bridge'), 'manage_woocommerce', 'wckx-settings', [__CLASS__,'render']);
    }

    public static function register_settings(){
        register_setting(WCKX_OPTION_GROUP, WCKX_OPTION_NAME, ['type'=>'array','sanitize_callback'=>[__CLASS__,'sanitize'],'default'=>[]]);

        add_settings_section('wckx_main', __('Connection','wc-kornitx-bridge'), '__return_false', 'wckx-settings');

        add_settings_field('api_base', __('API Base URL','wc-kornitx-bridge'), function(){
            $o = get_option(WCKX_OPTION_NAME, []);
            printf('<input type="url" name="%s[api_base]" value="%s" class="regular-text" placeholder="https://api-sl-2-2.kornitx.net" required />', esc_attr(WCKX_OPTION_NAME), esc_attr($o['api_base']??''));
        }, 'wckx-settings', 'wckx_main');

        add_settings_field('kornit_company_ref_id', __('Company Ref ID','wc-kornitx-bridge'), function(){
            $o = get_option(WCKX_OPTION_NAME, []);
            printf('<input type="text" name="%s[kornit_company_ref_id]" value="%s" class="regular-text" required />', esc_attr(WCKX_OPTION_NAME), esc_attr($o['kornit_company_ref_id']??''));
        }, 'wckx-settings', 'wckx_main');

        add_settings_field('api_key', __('API Key','wc-kornitx-bridge'), function(){
            // Intentionally do not re-print the key to avoid exposing secrets
            printf('<input type="password" name="%s[api_key]" value="" class="regular-text" placeholder="••••••••" />', esc_attr(WCKX_OPTION_NAME));
        }, 'wckx-settings', 'wckx_main');

        add_settings_section('wckx_orders', __('Orders','wc-kornitx-bridge'), '__return_false', 'wckx-settings');

        add_settings_field('send_on', __('Send Orders On','wc-kornitx-bridge'), function(){
            $o = get_option(WCKX_OPTION_NAME, []); $val = $o['send_on']??'thankyou';
            $choices = [
                'thankyou'         => __('Order placement (thank you page)','wc-kornitx-bridge'),
                'payment_complete' => __('After payment complete','wc-kornitx-bridge'),
                'manual'           => __('Manual only (admin action)','wc-kornitx-bridge'),
            ];
            echo '<select name="'.esc_attr(WCKX_OPTION_NAME).'[send_on]">';
            foreach($choices as $k=>$label){
                printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($val,$k,false), esc_html($label));
            }
            echo '</select>';
        }, 'wckx-settings', 'wckx_orders');
    }

    public static function sanitize($input){
        $out = [];
        $out['api_base'] = isset($input['api_base']) ? esc_url_raw($input['api_base']) : '';
        $out['kornit_company_ref_id'] = isset($input['kornit_company_ref_id']) ? sanitize_text_field($input['kornit_company_ref_id']) : '';
        if ( isset($input['api_key']) && $input['api_key'] !== '' ) {
            $out['api_key'] = sanitize_text_field($input['api_key']);
        } else {
            $existing = get_option(WCKX_OPTION_NAME, []);
            $out['api_key'] = $existing['api_key'] ?? '';
        }
        $out['send_on'] = isset($input['send_on']) ? sanitize_key($input['send_on']) : 'thankyou';
        return $out;
    }

    public static function render(){
        echo '<div class="wrap"><h1>'.esc_html__('Kornit X Bridge','wc-kornitx-bridge').'</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(WCKX_OPTION_GROUP);
        do_settings_sections('wckx-settings');
        submit_button(__('Save Changes','wc-kornitx-bridge'));
        echo '</form></div>';
    }
}
