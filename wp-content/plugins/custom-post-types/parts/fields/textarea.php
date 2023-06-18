<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['textarea'] = [
        'label' => __('Textarea', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            return sprintf(
                '<textarea name="%s" id="%s" autocomplete="off" aria-autocomplete="none" rows="%s" cols="%s"%s%s>%s</textarea>',
                $name,
                $id,
                !empty($config['extra']['rows']) ? $config['extra']['rows'] : 5,
                !empty($config['extra']['cols']) ? $config['extra']['cols'] : 50,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['required']) ? ' required' : '',
                $config['value']
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
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
            [ //min
                'key' => 'rows',
                'label' => __('Rows', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'number',
                'extra' => [
                    'placeholder' => '5',
                    'min' => '0'
                ],
                'wrap' => [
                    'width' => 25,
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
            [ //max
                'key' => 'cols',
                'label' => __('Columns', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'number',
                'extra' => [
                    'placeholder' => '50',
                    'min' => '0'
                ],
                'wrap' => [
                    'width' => 25,
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ]
        ],
        'sanitizeCallback' => function ($value) {
            return sanitize_textarea_field($value);
        }
    ];
    return $fields;
}, 30);