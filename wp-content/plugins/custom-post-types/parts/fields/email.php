<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    ob_start();
    ?>
    <div style="display: none;"><input type="email" name="{{data.name}}"></div>
    <input
            type="email"
            name="{{data.name}}"
            id="{{data.id}}"
            value="{{data.value}}"
            autocomplete="off"
            aria-autocomplete="none"
    <# if(data.extra.placeholder){ #>
    placeholder="{{data.extra.placeholder}}"
    <# } #>
    <# if(data.required){ #>
    required
    <# } #>
    >
    <?php
    $output = ob_get_clean();
    $fields['email'] = [
        'label' => __('Email', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            return sprintf(
                '<div style="display: none;"><input type="email" name="%s"></div><input type="email" name="%s" id="%s" value="%s" autocomplete="off" aria-autocomplete="none"%s%s>',
                $name,
                $name,
                $id,
                $config['value'],
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['required']) ? ' required' : ''
            );
        },
        'extra' => [
            [ //placeholder
                'key' => 'placeholder',
                'label' => __('Placeholder', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'text',
                'extra' => [],
                'wrap' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ]
        ],
        'sanitizeCallback' => function ($value) {
            return sanitize_email($value);
        }
    ];
    return $fields;
}, 90);

// TODO: a cosa serve?
add_filter($utils->getHookName('sanitize_field_email'), function ($value) {
    return sanitize_text_field($value);
});