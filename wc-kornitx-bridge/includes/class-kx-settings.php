<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Settings {
    const OPTION_GROUP = 'kx_wc_settings';
    const OPTION_NAME  = 'kx_wc_options';

    public static function init(){
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
    }

    public static function admin_menu(){
        add_submenu_page(
            'woocommerce',
            __( 'KornitX Bridge', 'kx-wc' ),
            __( 'KornitX Bridge', 'kx-wc' ),
            'manage_woocommerce',
            'kx-wc-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function defaults(){
        return [
            'company_ref_id'      => '',
            'api_key'             => '',
            'auto_submit'         => 0,
            'status_callback_url' => '',
            'use_basic_b64'       => 1,
            'debug_logging'       => 0,
            'debug_log_limit'     => 50,
        ];
    }

    public static function get_options(){
        $opts = get_option( self::OPTION_NAME, [] );
        return wp_parse_args( $opts, self::defaults() );
    }

    public static function register(){
        register_setting( self::OPTION_GROUP, self::OPTION_NAME, [ __CLASS__, 'sanitize' ] );

        add_settings_section( 'kx_wc_main', __( 'KornitX API Settings', 'kx-wc' ), function(){
            echo '<p>' . esc_html__( 'Provide your KornitX Sales Channel credentials and preferences.', 'kx-wc' ) . '</p>';
        }, 'kx-wc-settings' );

        add_settings_field( 'company_ref_id', __( 'Company Ref ID', 'kx-wc' ), [ __CLASS__, 'text_field' ], 'kx-wc-settings', 'kx_wc_main', [ 'key' => 'company_ref_id' ] );
        add_settings_field( 'api_key', __( 'API Key', 'kx-wc' ), [ __CLASS__, 'text_field' ], 'kx-wc-settings', 'kx_wc_main', [ 'key' => 'api_key', 'type' => 'password' ] );
        add_settings_field( 'auto_submit', __( 'Auto-submit Orders to KornitX', 'kx-wc' ), [ __CLASS__, 'checkbox_field' ], 'kx-wc-settings', 'kx_wc_main', [ 'key' => 'auto_submit' ] );
        add_settings_field( 'status_callback_url', __( 'Status Callback URL (optional)', 'kx-wc' ), [ __CLASS__, 'text_field' ], 'kx-wc-settings', 'kx_wc_main', [ 'key' => 'status_callback_url' ] );
        add_settings_field( 'use_basic_b64', __( 'Use Basic Auth (Base64)', 'kx-wc' ), [ __CLASS__, 'checkbox_field' ], 'kx-wc-settings', 'kx_wc_main', [ 'key' => 'use_basic_b64' ] );

        add_settings_section( 'kx_wc_debug', __( 'Smartlink Debugging', 'kx-wc' ), function(){
            echo '<p>' . esc_html__( 'Enable logging of Smartlink postMessage payloads and view recent callbacks.', 'kx-wc' ) . '</p>';
        }, 'kx-wc-settings' );
        add_settings_field( 'debug_logging', __( 'Enable Smartlink debug logging', 'kx-wc' ), [ __CLASS__, 'checkbox_field' ], 'kx-wc-settings', 'kx_wc_debug', [ 'key' => 'debug_logging' ] );
        add_settings_field( 'debug_log_limit', __( 'Max callbacks to store', 'kx-wc' ), [ __CLASS__, 'number_field' ], 'kx-wc-settings', 'kx_wc_debug', [ 'key' => 'debug_log_limit', 'min'=>10, 'max'=>500 ] );
    }

    public static function sanitize( $input ){
        $out = [];
        $out['company_ref_id']      = sanitize_text_field( $input['company_ref_id'] ?? '' );
        $out['api_key']             = sanitize_text_field( $input['api_key'] ?? '' );
        $out['auto_submit']         = ! empty( $input['auto_submit'] ) ? 1 : 0;
        $out['status_callback_url'] = esc_url_raw( $input['status_callback_url'] ?? '' );
        $out['use_basic_b64']       = ! empty( $input['use_basic_b64'] ) ? 1 : 0;
        $out['debug_logging']       = ! empty( $input['debug_logging'] ) ? 1 : 0;
        $out['debug_log_limit']     = max( 10, min( 500, intval( $input['debug_log_limit'] ?? 50 ) ) );
        return $out;
    }

    public static function text_field( $args ){
        $key  = $args['key'];
        $type = $args['type'] ?? 'text';
        $opts = self::get_options();
        printf('<input type="%s" name="%s[%s]" value="%s" class="regular-text" />', esc_attr($type), esc_attr(self::OPTION_NAME), esc_attr($key), esc_attr($opts[$key] ?? '') );
    }

    public static function number_field( $args ){
        $key  = $args['key'];
        $min  = intval($args['min'] ?? 1);
        $max  = intval($args['max'] ?? 999);
        $opts = self::get_options();
        printf('<input type="number" min="%d" max="%d" name="%s[%s]" value="%s" class="small-text" />', $min, $max, esc_attr(self::OPTION_NAME), esc_attr($key), esc_attr($opts[$key] ?? '') );
    }

    public static function checkbox_field( $args ){
        $key  = $args['key'];
        $opts = self::get_options();
        printf('<label><input type="checkbox" name="%s[%s]" value="1" %s/> %s</label>', esc_attr(self::OPTION_NAME), esc_attr($key), checked( !empty($opts[$key]), true, false ), esc_html__( 'Enable', 'kx-wc' ));
    }

    public static function render_page(){
        echo '<div class="wrap"><h1>' . esc_html__( 'KornitX Bridge for WooCommerce', 'kx-wc' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( self::OPTION_GROUP );
        do_settings_sections( 'kx-wc-settings' );
        submit_button();
        echo '</form></div>';
    }
}
