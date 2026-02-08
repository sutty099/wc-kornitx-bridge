<?php
namespace KX_WC;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Variant_Resolver {
    public static function init(){}

    public static function resolve( \WC_Product $product, array $variant ){
        if ( ! $product->is_type('variable') ) return null;
        $children   = $product->get_children();
        $attributes = $product->get_attributes();

        $color_candidates = array_filter([
            $variant['aspect_option_1_name'] ?? null,
            $variant['aspect_option_2_name'] ?? null,
        ]);
        $size_candidates = array_filter([
            $variant['print_size_description'] ?? null,
            $variant['attribute_1_name'] ?? null,
            $variant['attribute_2_name'] ?? null,
        ]);

        $color_slugs = array_values( array_unique( array_map( __NAMESPACE__.'\normalize_slug', $color_candidates ) ) );
        $size_slugs  = array_values( array_unique( array_map( __NAMESPACE__.'\normalize_slug',  $size_candidates ) ) );

        $tax_map = [ 'color' => [], 'size' => [] ];
        foreach ( $attributes as $tax => $attr_obj ) {
            $label = strtolower( wc_attribute_label( $tax ) );
            if ( false !== strpos( $label, 'colour' ) || false !== strpos( $label, 'color' ) ) {
                $tax_map['color'][] = $tax;
            }
            if ( false !== strpos( $label, 'size' ) || false !== strpos( $label, 'print size' ) ) {
                $tax_map['size'][] = $tax;
            }
        }

        foreach ( $children as $vid ) {
            $v = wc_get_product( $vid ); if ( ! $v ) continue;
            $vattrs = wc_get_product_variation_attributes( $vid );
            $attrs_for_add = [];
            $ok = true;

            if ( ! empty($tax_map['color']) && ! empty($color_slugs) ) {
                $matched = false;
                foreach( $tax_map['color'] as $tax ){
                    $key = 'attribute_' . $tax;
                    $val = $vattrs[$key] ?? '';
                    if ( $val && in_array( $val, $color_slugs, true ) ) { $attrs_for_add[$tax] = $val; $matched = true; break; }
                }
                if ( ! $matched ) { $ok = false; }
            }
            if ( ! $ok ) continue;

            if ( ! empty($tax_map['size']) && ! empty($size_slugs) ) {
                $matched = false;
                foreach( $tax_map['size'] as $tax ){
                    $key = 'attribute_' . $tax;
                    $val = $vattrs[$key] ?? '';
                    if ( $val && in_array( $val, $size_slugs, true ) ) { $attrs_for_add[$tax] = $val; $matched = true; break; }
                }
                if ( ! $matched ) { $ok = false; }
            }
            if ( ! $ok ) continue;

            return [ 'variation_id' => $vid, 'attributes' => $attrs_for_add ];
        }
        return null;
    }
}
