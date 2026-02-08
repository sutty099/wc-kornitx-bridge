<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Frontend {
    public static function init(){
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_iframe_container' ], 6 );
        add_action( 'wp', [ __CLASS__, 'maybe_hide_native_add_to_cart' ] );
        add_action( 'wp_ajax_kx_iframe_add_to_cart', [ __CLASS__, 'ajax_add_to_cart' ] );
        add_action( 'wp_ajax_nopriv_kx_iframe_add_to_cart', [ __CLASS__, 'ajax_add_to_cart' ] );
        add_filter( 'woocommerce_cart_item_name', [ __CLASS__, 'cart_item_name_decorate' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_thumbnail', [ __CLASS__, 'cart_item_thumbnail' ], 10, 3 );
    }

    public static function get_current_product(){
        global $product;
        if ( $product instanceof \WC_Product ) return $product;
        $id = function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        return $id ? ensure_product( $id ) : false;
    }

    public static function has_smartlink( $product ){
        $p = ensure_product( $product );
        if ( ! $p ) return false;
        $url = $p->get_meta( Admin_Product::META_SMARTLINK_URL );
        return ! empty( $url );
    }

    public static function maybe_hide_native_add_to_cart(){
        if ( function_exists('is_product') && is_product() ) {
            $product = self::get_current_product();
            if ( $product && self::has_smartlink( $product ) ) {
                add_action( 'wp_print_footer_scripts', function(){
                    echo '<style>.single-product form.cart{display:none !important;}</style>';
                } );
            }
        }
    }

    public static function enqueue(){
        if ( ! function_exists('is_product') || ! is_product() ) return;
        $product = self::get_current_product();
        if ( ! $product || ! self::has_smartlink( $product ) ) return;

        wp_enqueue_script( 'kx-smartlink', KX_WC_URL . 'assets/js/kx-smartlink.js', [], KX_WC_VERSION, true );
        $smartlink_url = $product->get_meta( Admin_Product::META_SMARTLINK_URL );
        $pj = isset($_GET['kx_pj']) ? sanitize_text_field( wp_unslash($_GET['kx_pj']) ) : '';
        $opts = Settings::get_options();
        wp_localize_script( 'kx-smartlink', 'KX_SMARTLINK', [
            'product_id'   => $product->get_id(),
            'smartlink_url'=> $smartlink_url,
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'kx_iframe_add_to_cart' ),
            'pj'           => $pj,
            'debug'        => ! empty( $opts['debug_logging'] ) ? 1 : 0,
        ] );
        $css = '.kx-smartlink-wrap{margin:1rem 0;} .kx-smartlink-wrap iframe{width:100%; min-height:600px; border:0;}';
        wp_add_inline_style( 'woocommerce-inline', $css );
    }

    public static function render_iframe_container(){
        $product = self::get_current_product();
        if ( ! $product || ! self::has_smartlink( $product ) ) return;
        echo '<div class="kx-smartlink-wrap"><iframe id="kx-smartlink-iframe" src="about:blank" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
    }

    public static function cart_item_name_decorate( $item_name, $cart_item, $cart_item_key ){
        $thumb_html = '';
        $thumbs = safe_json_decode( $cart_item['kornitx_thumbnails'] ?? '[]', true );
        if ( is_array($thumbs) && ! empty($thumbs) ) {
            $url = '';
            foreach( $thumbs as $t ){
                if ( isset($t['name']) && $t['name'] === 'thumbnail' && ! empty($t['url']) ) { $url = $t['url']; break; }
            }
            if ( ! $url && ! empty($thumbs[0]['url']) ) $url = $thumbs[0]['url'];
            if ( $url ) {
                $thumb_html = sprintf('<img src="%s" alt="%s" width="48" height="48" style="margin-right:8px;vertical-align:middle;border-radius:4px;"/>', esc_url($url), esc_attr__('Preview','kx-wc'));
            }
        }

        $edit_link = '';
        if ( ! empty( $cart_item['kornitx_print_job_ref'] ) && ! empty( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ) {
            $pj  = rawurlencode( $cart_item['kornitx_print_job_ref'] );
            $url = add_query_arg( [ 'kx_pj' => $pj ], get_permalink( $cart_item['data']->get_id() ) );
            $edit_link = sprintf(' <a class="kx-edit-design" href="%s">%s</a>', esc_url($url), esc_html__('Edit design','kx-wc'));
        }
        return $thumb_html . $item_name . $edit_link;
    }

    public static function cart_item_thumbnail( $image, $cart_item, $cart_item_key ){
        $thumbs = safe_json_decode( $cart_item['kornitx_thumbnails'] ?? '[]', true );
        if ( ! is_array( $thumbs ) || empty( $thumbs ) ) return $image;
        $url = '';
        foreach( $thumbs as $t ){
            if ( isset($t['name']) && $t['name'] === 'thumbnail' && ! empty($t['url']) ) { $url = $t['url']; break; }
        }
        if ( ! $url && ! empty($thumbs[0]['url']) ) $url = $thumbs[0]['url'];
        if ( ! $url ) return $image;
        return sprintf( '<img src="%s" alt="%s" width="64" height="64" loading="lazy"/>', esc_url( $url ), esc_attr__( 'Preview', 'kx-wc' ) );
    }

    public static function ajax_add_to_cart(){
        check_ajax_referer( 'kx_iframe_add_to_cart', 'nonce' );
        $product_id    = intval( $_POST['product_id'] ?? 0 );
        $print_job_ref = sanitize_text_field( $_POST['print_job_ref'] ?? '' );
        $quantity      = max( 1, intval( $_POST['quantity'] ?? 1 ) );
        $thumbnails    = isset($_POST['thumbnails']) ? wp_unslash( $_POST['thumbnails'] ) : '[]';
        $sku           = sanitize_text_field( $_POST['sku'] ?? '' );
        $variant       = safe_json_decode( wp_unslash( $_POST['variant'] ?? '' ), true );

        if ( ! $product_id || empty( $print_job_ref ) ) {
            wp_send_json_error( [ 'message' => 'Missing product_id or print_job_ref' ], 400 );
        }

        $product = ensure_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( [ 'message' => 'Invalid product' ], 400 );
        }

        $variation_id   = 0;
        $variation_attrs= [];
        if ( $product->is_type('variable') && ! empty( $variant ) ) {
            $resolved = Variant_Resolver::resolve( $product, $variant );
            if ( $resolved ) {
                $variation_id    = $resolved['variation_id'];
                $variation_attrs = $resolved['attributes'];
            }
        }

        $cart_item_data = [
            'kornitx_print_job_ref' => $print_job_ref,
            'kornitx_thumbnails'    => $thumbnails,
            'kornitx_sku'           => $sku,
            'kornitx_variant_meta'  => $variant,
        ];

        $added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attrs, $cart_item_data );
        if ( ! $added ) {
            wp_send_json_error( [ 'message' => 'Failed to add to cart' ], 500 );
        }

        wp_send_json_success( [
            'cart_hash' => WC()->cart->get_cart_hash(),
            'cart_url'  => wc_get_cart_url(),
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [] ),
        ] );
    }
}
