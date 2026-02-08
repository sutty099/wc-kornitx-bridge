<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Order {
    public static function init(){
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_meta_to_order_item' ], 10, 4 );
        add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'maybe_submit_order' ] );
        add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'maybe_submit_order' ] );
    }

    public static function add_meta_to_order_item( $item, $cart_item_key, $values, $order ){
        if ( ! empty( $values['kornitx_print_job_ref'] ) ) {
            $item->add_meta_data( '_kornitx_print_job_ref', sanitize_text_field( $values['kornitx_print_job_ref'] ), true );
        }
        if ( ! empty( $values['kornitx_thumbnails'] ) ) {
            $item->add_meta_data( '_kornitx_thumbnails', wp_kses_post( $values['kornitx_thumbnails'] ), true );
        }
        if ( ! empty( $values['kornitx_sku'] ) ) {
            $item->add_meta_data( '_kornitx_sku', sanitize_text_field( $values['kornitx_sku'] ), true );
        }
        if ( ! empty( $values['kornitx_variant_meta'] ) ) {
            $item->add_meta_data( '_kornitx_variant', wp_kses_post( wp_json_encode( $values['kornitx_variant_meta'] ) ), true );
        }
    }

    public static function maybe_submit_order( $order_id ){
        $opts = Settings::get_options();
        if ( empty( $opts['auto_submit'] ) ) return;
        self::submit_to_kornitx( $order_id );
    }

    protected static function get_auth_header(){
        $opts = Settings::get_options();
        $company = $opts['company_ref_id'];
        $apiKey  = $opts['api_key'];
        if ( $opts['use_basic_b64'] ) {
            $token = base64_encode( $company . ':' . $apiKey );
            return 'Basic ' . $token;
        }
        return 'Basic ' . $company . ':' . $apiKey;
    }

    public static function submit_to_kornitx( $order_id ){
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $opts = Settings::get_options();
        $company  = $opts['company_ref_id'];
        $callback = ! empty( $opts['status_callback_url'] ) ? $opts['status_callback_url'] : '';

        $items = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $pj = $item->get_meta( '_kornitx_print_job_ref', true );
            $product = $item->get_product();
            $sku = $product ? $product->get_sku() : '';
            $payload_item = [
                'sku'          => $sku ?: ( $item->get_meta( '_kornitx_sku', true ) ?: '' ),
                'external_ref' => $order->get_order_number() . '-' . $item_id,
                'description'  => $item->get_name(),
                'quantity'     => (int) $item->get_quantity(),
            ];

            if ( $pj ) {
                $payload_item['type'] = 2; // Smartlink print job
                $payload_item['print_job_ref'] = $pj;
            } else {
                $payload_item['type'] = 5; // default textual
                if ( $product && $product->is_type('variation') ) {
                    $vattrs = $product->get_attributes();
                    foreach ( $vattrs as $taxonomy => $term_slug ) {
                        $label = strtolower( wc_attribute_label( $taxonomy ) );
                        if ( false !== strpos($label,'colour') || false !== strpos($label,'color') ) {
                            $payload_item['colour'] = ucwords( str_replace('-', ' ', $term_slug) );
                        }
                        if ( false !== strpos($label,'size') || false !== strpos($label,'print size') ) {
                            $payload_item['size']   = strtoupper( $term_slug );
                        }
                    }
                }
            }

            $items[] = $payload_item;
        }

        if ( empty( $items ) ) return;

        $payload = [
            'external_ref'         => (string) $order->get_order_number(),
            'company_ref_id'       => (int) $company,
            'sale_datetime'        => gmdate('Y-m-d H:i:s', strtotime( $order->get_date_created()->date('c') ) ),
            'customer_name'        => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
            'customer_email'       => $order->get_billing_email(),
            'customer_telephone'   => $order->get_billing_phone(),
            'shipping_address_1'   => $order->get_shipping_address_1(),
            'shipping_address_2'   => $order->get_shipping_address_2(),
            'shipping_address_3'   => '',
            'shipping_address_4'   => $order->get_shipping_city(),
            'shipping_address_5'   => $order->get_shipping_state(),
            'shipping_postcode'    => $order->get_shipping_postcode(),
            'shipping_country_code'=> $order->get_shipping_country(),
            'shipping_method'      => $order->get_shipping_method(),
            'billing_address_1'    => $order->get_billing_address_1(),
            'billing_address_2'    => $order->get_billing_address_2(),
            'billing_address_3'    => '',
            'billing_address_4'    => $order->get_billing_city(),
            'billing_address_5'    => $order->get_billing_state(),
            'billing_postcode'     => $order->get_billing_postcode(),
            'billing_country_code' => $order->get_billing_country(),
            'payment_trans_id'     => $order->get_transaction_id(),
            'status_callback_url'  => $callback,
            'items'                => $items,
        ];

        $args = [
            'headers' => [
                'Authorization' => self::get_auth_header(),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 30,
        ];

        $endpoint = 'https://api-sl-2-2.kornitx.net/order';
        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            $order->add_order_note( 'KornitX submission error: ' . $response->get_error_message() );
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        if ( $code >= 200 && $code < 300 ) {
            $order->add_order_note( 'KornitX order submitted successfully. Response: ' . $body );
            $order->update_meta_data( '_kornitx_api_response', $body );
            $order->save();
        } else {
            $order->add_order_note( 'KornitX submission failed (HTTP ' . $code . '): ' . $body );
        }
    }
}
