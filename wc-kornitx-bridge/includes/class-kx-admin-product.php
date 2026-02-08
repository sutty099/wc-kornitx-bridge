<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Product {
    const META_SMARTLINK_URL = '_kx_smartlink_url';

    public static function init(){
        add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'add_field' ] );
        add_action( 'woocommerce_admin_process_product_object', [ __CLASS__, 'save' ] );
    }

    public static function add_field(){
        echo '<div class="options_group">';
        woocommerce_wp_text_input( [
            'id'          => self::META_SMARTLINK_URL,
            'label'       => __( 'KornitX Smartlink URL', 'kx-wc' ),
            'description' => __( 'Paste the full Smartlink URL for this product. The plugin will append a2c, mei and meo automatically.', 'kx-wc' ),
            'desc_tip'    => true,
            'type'        => 'url',
        ] );
        echo '</div>';
    }

    public static function save( $product ){
        if ( isset( $_POST[self::META_SMARTLINK_URL] ) ) {
            $url = esc_url_raw( wp_unslash( $_POST[self::META_SMARTLINK_URL] ) );
            $product->update_meta_data( self::META_SMARTLINK_URL, $url );
        }
    }
}
