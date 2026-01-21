<?php
if (!defined('ABSPATH')) exit;

class WCKX_ProductPodMeta {
    public static function init(){ add_action('add_meta_boxes',[__CLASS__,'add_meta_box']); add_action('save_post_product',[__CLASS__,'save_meta']); }
    public static function add_meta_box(){ add_meta_box('wckx_pod_meta', __('Kornit X – Print On Demand','wc-kornitx-bridge'), function($post){ $val=get_post_meta($post->ID,'_wckx_pod_ref',true); wp_nonce_field('wckx_pod_meta','wckx_pod_nonce'); echo '<p>'.esc_html__('Paste the Print On Demand reference for this product (item type 3).','wc-kornitx-bridge').'</p>'; printf('<input type="text" name="wckx_pod_ref" value="%s" class="widefat" placeholder="e.g. POD-123456" />', esc_attr($val)); }, 'product','side','default'); }
    public static function save_meta($post_id){ if(!isset($_POST['wckx_pod_nonce'])||!wp_verify_nonce($_POST['wckx_pod_nonce'],'wckx_pod_meta'))return; if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return; if(!current_user_can('edit_product',$post_id))return; $val=isset($_POST['wckx_pod_ref'])?sanitize_text_field($_POST['wckx_pod_ref']):''; if($val) update_post_meta($post_id,'_wckx_pod_ref',$val); else delete_post_meta($post_id,'_wckx_pod_ref'); }
}
