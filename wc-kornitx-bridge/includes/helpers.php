<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

function ensure_product( $maybe ){
    if ( $maybe instanceof \WC_Product ) return $maybe;
    if ( is_numeric( $maybe ) ) {
        $p = wc_get_product( (int) $maybe );
        return ( $p instanceof \WC_Product ) ? $p : false;
    }
    return false;
}

function normalize_slug( $value ){
    return sanitize_title( is_string($value) ? $value : strval($value) );
}

function safe_json_decode( $json, $assoc = true ){
    if ( ! is_string( $json ) || $json === '' ) return $assoc ? [] : null;
    $data = json_decode( $json, $assoc );
    return ( json_last_error() === JSON_ERROR_NONE ) ? $data : ( $assoc ? [] : null );
}
