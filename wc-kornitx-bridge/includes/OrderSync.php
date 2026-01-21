<?php
if (!defined('ABSPATH')) exit;

class WCKX_OrderSync {
    public static function init() {
        $opts    = get_option(WCKX_OPTION_NAME, []);
        $send_on = isset($opts['send_on']) ? $opts['send_on'] : 'thankyou';

        if ($send_on === 'thankyou') {
            add_action('woocommerce_thankyou', [__CLASS__, 'handle_order'], 20, 1);
        }
        add_filter('woocommerce_admin_order_actions', [__CLASS__, 'add_admin_action'], 10, 2);
        add_action('admin_action_wckx_send_order', [__CLASS__, 'manual_send_action']);

        add_action('woocommerce_order_actions', [__CLASS__, 'register_order_action']);
        add_action('woocommerce_order_action_wckx_send_order', [__CLASS__, 'order_edit_action_handler']);
    }

    public static function add_admin_action($actions, $order) {
        if (!is_a($order, 'WC_Order')) return $actions;
        $sent = (bool) $order->get_meta('_wckx_sent');
        $actions['wckx_send'] = [
            'url'    => wp_nonce_url(admin_url('admin.php?action=wckx_send_order&order_id=' . $order->get_id()), 'wckx_send_order'),
            'name'   => __('Send to Kornit X', 'wc-kornitx-bridge'),
            'action' => $sent ? 'wckx-sent' : 'wckx-send',
        ];
        return $actions;
    }

    public static function register_order_action($actions) {
        $actions['wckx_send_order'] = __('Send to Kornit X', 'wc-kornitx-bridge');
        return $actions;
    }

    public static function order_edit_action_handler($order) {
        if (is_a($order, 'WC_Order')) self::send_order($order->get_id());
    }

    public static function manual_send_action() {
        if (!current_user_can('manage_woocommerce')) wp_die(__('Access denied', 'wc-kornitx-bridge'));
        check_admin_referer('wckx_send_order');
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        if ($order_id) self::send_order($order_id);
        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=wc-orders'));
        exit;
    }

    public static function handle_order($order_id) { self::send_order($order_id); }

    public static function send_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        if ((bool) $order->get_meta('_wckx_sent')) return; // Avoid duplicate sends

        $payload = self::serialize_order($order);
        $resp    = WCKX_Helpers::post('/order', $payload);

        if (!empty($resp['ok'])) {
            $order->update_meta_data('_wckx_sent', current_time('mysql'));
            if (!empty($resp['body']['ref'])) {
                $order->update_meta_data('_wckx_remote_ref', sanitize_text_field($resp['body']['ref']));
            }
            $order->save();
            $order->add_order_note(__('Pushed to Kornit X successfully.', 'wc-kornitx-bridge'));
            WCKX_Helpers::log_event('Order sent successfully', ['order_id' => $order_id, 'resp' => $resp]);
        } else {
            $order->add_order_note(__('Kornit X push failed. See Kornit X Bridge Logs.', 'wc-kornitx-bridge'));
            WCKX_Helpers::log_event('Order send failed', ['order_id' => $order_id, 'resp' => $resp]);
        }
    }

    private static function serialize_order($order) {
        $opts = get_option(WCKX_OPTION_NAME, []);
        $sale_dt           = $order->get_date_created();
        $sale_datetime_utc = $sale_dt ? gmdate('Y-m-d H:i:s', $sale_dt->getTimestamp()) : gmdate('Y-m-d H:i:s');

        $ship = $order->get_address('shipping');
        $bill = $order->get_address('billing');

        $customer_name = trim(($ship['first_name'] ?? '') . ' ' . ($ship['last_name'] ?? ''));
        if ($customer_name === '') {
            $customer_name = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $pod_ref = $product ? get_post_meta($product->get_id(), '_wckx_pod_ref', true) : '';
            $items[] = [
                'external_ref'        => (string) $item->get_id(),
                'sku'                 => $product ? $product->get_sku() : '',
                'description'         => $item->get_name(),
                'quantity'            => (int) $item->get_quantity(),
                'sale_price'          => (float) $order->get_item_subtotal($item, true),
                'sale_price_inc_tax'  => (float) $order->get_line_total($item, true),
                'type'                => 3,
                'print_on_demand_ref' => $pod_ref ?: '',
            ];
        }

        $shipping_method = '';
        $shipping_items  = $order->get_items('shipping');
        if (!empty($shipping_items)) {
            $first = reset($shipping_items);
            if ($first && is_object($first) && method_exists($first, 'get_name')) {
                $shipping_method = $first->get_name();
            }
        }

        return [
            'external_ref'           => (string) $order->get_order_number(),
            'company_ref_id'         => (int) ($opts['kornit_company_ref_id'] ?? 0),
            'sale_datetime'          => $sale_datetime_utc,
            'customer_name'          => $customer_name,
            'customer_email'         => $order->get_billing_email(),
            'customer_telephone'     => $order->get_billing_phone(),
            'shipping_company'       => $ship['company'] ?? '',
            'shipping_address_1'     => $ship['address_1'] ?? '',
            'shipping_address_2'     => $ship['address_2'] ?? '',
            'shipping_address_3'     => '',
            'shipping_address_4'     => $ship['city'] ?? '',
            'shipping_address_5'     => $ship['state'] ?? '',
            'shipping_postcode'      => $ship['postcode'] ?? '',
            'shipping_country_code'  => $ship['country'] ?? '',
            'shipping_method'        => $shipping_method,
            'billing_company'        => $bill['company'] ?? '',
            'billing_address_1'      => $bill['address_1'] ?? '',
            'billing_address_2'      => $bill['address_2'] ?? '',
            'billing_address_3'      => '',
            'billing_address_4'      => $bill['city'] ?? '',
            'billing_address_5'      => $bill['state'] ?? '',
            'billing_postcode'       => $bill['postcode'] ?? '',
            'billing_country'        => $bill['country'] ?? '',
            'shipping_price'         => (float) $order->get_shipping_total(),
            'shipping_price_inc_tax' => (float) ($order->get_shipping_total() + $order->get_shipping_tax()),
            'currency_code'          => $order->get_currency(),
            'items'                  => $items,
        ];
    }
}
