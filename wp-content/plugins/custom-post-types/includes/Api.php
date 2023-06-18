<?php

if(!function_exists('cpt_field')){
    function cpt_field($key = false, $post_id = false){
        $shortcode = sprintf('[cpt-field key="%s" post-id="%s"]', $key, $post_id);
        return do_shortcode($shortcode);
    }
}

if(!function_exists('cpt_terms')){
    function cpt_terms($key = false, $post_id = false){
        $shortcode = sprintf('[cpt-terms key="%s" post-id="%s"]', $key, $post_id);
        return do_shortcode($shortcode);
    }
}

if(!function_exists('cpt_term_field')){
    function cpt_term_field($key = false, $term_id = false){
        $shortcode = sprintf('[cpt-term-field key="%s" term-id="%s"]', $key, $term_id);
        return do_shortcode($shortcode);
    }
}

if(!function_exists('cpt_option_field')){
    function cpt_option_field($key = false, $option_id = false){
        $shortcode = sprintf('[cpt-option-field key="%s" option-id="%s"]', $key, $option_id);
        return do_shortcode($shortcode);
    }
}