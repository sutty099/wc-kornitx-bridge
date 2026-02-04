<?php
if (!defined('ABSPATH')) exit;

class WCKX_Helpers {
    public static function post(string $endpoint, array $payload) : array {
        $opts = get_option(WCKX_OPTION_NAME, []);
        $base = rtrim($opts['api_base'] ?? '', '/');
        if (!$base) return ['ok' => false, 'error' => 'Missing API base'];

        $company_id = $opts['kornit_company_ref_id'] ?? '';
        $api_key    = $opts['api_key'] ?? '';
        $auth       = base64_encode($company_id . ':' . $api_key);

        $headers = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ];

        $url  = $base . $endpoint;
        $resp = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'timeout' => 25,
        ]);

        return self::parse_response($resp);
    }

    public static function parse_response($resp) : array {
        if (is_wp_error($resp)) {
            return ['ok' => false, 'error' => $resp->get_error_message(), 'transient' => true];
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);

        $ok  = ($code >= 200 && $code < 300);
        return [
            'ok'        => $ok,
            'code'      => $code,
            'body'      => is_array($json) ? $json : ['raw' => $body],
            'transient' => ($code >= 500) || ($code == 429),
            'error'     => $ok ? null : ('HTTP ' . $code),
        ];
    }

    public static function log_event($message, $context = []) {
        $entry = [
            'time'    => current_time('mysql'),
            'message' => $message,
            'context' => $context,
        ];
        $logs = get_option(WCKX_LOG_OPTION, []);
        array_unshift($logs, $entry);
        $logs = array_slice($logs, 0, 200);
        update_option(WCKX_LOG_OPTION, $logs);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WCKX] ' . $message . ' ' . wp_json_encode($context));
        }
    }
}
