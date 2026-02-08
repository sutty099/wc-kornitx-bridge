<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Debug {
    const LOG_OPTION = 'kx_wc_callback_log';

    public static function init(){
        add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
        add_action( 'wp_ajax_kx_log_smartlink_message', [ __CLASS__, 'ajax_log_message' ] );
    }

    public static function admin_menu(){
        add_submenu_page(
            'woocommerce',
            __( 'KornitX Debug', 'kx-wc' ),
            __( 'KornitX Debug', 'kx-wc' ),
            'manage_woocommerce',
            'kx-wc-debug',
            [ __CLASS__, 'render_debug_page' ]
        );
    }

    public static function get_log(){
        $log = get_option( self::LOG_OPTION, [] );
        return is_array($log) ? $log : [];
    }

    public static function push_log( $entry ){
        $opts  = Settings::get_options();
        $limit = max(10, intval($opts['debug_log_limit'] ?? 50));
        $log   = self::get_log();
        $entry['ts'] = current_time( 'mysql' );
        array_unshift( $log, $entry );
        if ( count($log) > $limit ) $log = array_slice( $log, 0, $limit );
        update_option( self::LOG_OPTION, $log, false );
    }

    public static function ajax_log_message(){
        $opts = Settings::get_options();
        if ( empty( $opts['debug_logging'] ) ) { wp_die(); }
        $payload = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
        if ( $payload ) {
            error_log( 'Smartlink postMessage payload: ' . $payload );
            self::push_log( [ 'raw' => $payload ] );
        }
        wp_die();
    }

    public static function render_debug_page(){
        if ( isset($_POST['kx_wc_clear_log']) && check_admin_referer('kx_wc_clear_log') ) {
            update_option( self::LOG_OPTION, [] );
            echo '<div class="updated"><p>' . esc_html__('Cleared Smartlink callback log.','kx-wc') . '</p></div>';
        }

        echo '<div class="wrap"><h1>' . esc_html__('KornitX Debug Tools','kx-wc') . '</h1>';

        echo '<h2>' . esc_html__('Recent Smartlink Callbacks','kx-wc') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field('kx_wc_clear_log');
        echo '<p><input type="submit" name="kx_wc_clear_log" class="button" value="' . esc_attr__('Clear Log','kx-wc') . '"/></p>';
        echo '</form>';

        $log = self::get_log();
        if ( empty( $log ) ) {
            echo '<p>' . esc_html__('No callbacks logged yet. Enable debug logging in settings and interact with a Smartlink.','kx-wc') . '</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr>';
            echo '<th>' . esc_html__('Timestamp','kx-wc') . '</th>';
            echo '<th>' . esc_html__('Summary','kx-wc') . '</th>';
            echo '<th>' . esc_html__('Raw','kx-wc') . '</th>';
            echo '</tr></thead><tbody>';
            foreach( $log as $row ){
                $ts   = esc_html( $row['ts'] ?? '' );
                $raw  = $row['raw'] ?? '';
                $summary = '';
                $decoded = json_decode( $raw, true );
                if ( is_array($decoded) ) {
                    $name = $decoded['data']['name'] ?? '';
                    $origin = $decoded['origin'] ?? '';
                    $summary = esc_html( $name . ' @ ' . $origin );
                }
                echo '<tr><td>' . $ts . '</td><td>' . $summary . '</td><td><textarea readonly style="width:100%;height:150px;">' . esc_textarea($raw) . '</textarea></td></tr>';
            }
            echo '</tbody></table>';
        }

        echo '<hr/><h2>' . esc_html__('Variation Mapping Diagnostics','kx-wc') . '</h2>';
        echo '<p>' . esc_html__('Paste a Smartlink ADD_TO_CART_CALLBACK payload and a Woo product ID to test how the plugin resolves variations.','kx-wc') . '</p>';

        if ( isset($_POST['kx_wc_diag_test']) && check_admin_referer('kx_wc_diag_test') ) {
            $product_id = intval( $_POST['kx_diag_product_id'] ?? 0 );
            $payload    = wp_unslash( $_POST['kx_diag_payload'] ?? '' );
            $result = '';
            if ( $product_id && $payload ) {
                $p = wc_get_product( $product_id );
                $data = json_decode( $payload, true );
                $variant = [];
                if ( isset($data['data']['body']['items'][0]['variant']) ) {
                    $variant = $data['data']['body']['items'][0]['variant'];
                } elseif ( isset($data['variant']) ) {
                    $variant = $data['variant'];
                }
                if ( $p && $variant ) {
                    if ( ! $p->is_type('variable') ) {
                        $result = '<p>' . esc_html__('Product is not variable.','kx-wc') . '</p>';
                    } else {
                        $resolved = \KX_WC\Variant_Resolver::resolve( $p, $variant );
                        ob_start();
                        echo '<pre>';
                        echo esc_html( print_r( [ 'product_id'=>$product_id, 'variant'=>$variant, 'resolved'=>$resolved ], true ) );
                        echo '</pre>';
                        $result = ob_get_clean();
                    }
                } else {
                    $result = '<p>' . esc_html__('Could not parse variant or product not found.','kx-wc') . '</p>';
                }
            } else {
                $result = '<p>' . esc_html__('Please provide both a Product ID and payload.','kx-wc') . '</p>';
            }
            echo '<div class="notice notice-info">' . $result . '</div>';
        }

        echo '<form method="post">';
        wp_nonce_field('kx_wc_diag_test');
        echo '<p><label>' . esc_html__('Woo Product ID','kx-wc') . '</label><br/>';
        echo '<input type="number" name="kx_diag_product_id" min="1" class="small-text"/></p>';
        echo '<p><label>' . esc_html__('Smartlink callback payload (JSON)','kx-wc') . '</label><br/>';
        echo '<textarea name="kx_diag_payload" style="width:100%;height:200px;"></textarea></p>';
        echo '<p><input type="submit" name="kx_wc_diag_test" class="button button-primary" value="' . esc_attr__('Run Test','kx-wc') . '"/></p>';
        echo '</form>';

        echo '</div>';
    }
}
