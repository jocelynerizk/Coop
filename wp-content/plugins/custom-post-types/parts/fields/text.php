<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['text'] = [
        'label' => __('Text', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            return sprintf(
                '<input type="text" name="%s" id="%s" value="%s" autocomplete="off" aria-autocomplete="none"%s%s>',
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
            ],
        ],
        'sanitizeCallback' => function ($value) {
            return sanitize_text_field($value);
        }
    ];
    return $fields;
}, 10);