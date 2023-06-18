<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['number'] = [
        'label' => __('Number', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            return sprintf(
                '<input type="number" name="%s" id="%s" value="%s" autocomplete="off" aria-autocomplete="none"%s%s%s%s>',
                $name,
                $id,
                $config['value'],
                isset($config['extra']['placeholder']) && is_string($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['min']) ? ' min="' . $config['extra']['min'] . '"' : '',
                !empty($config['extra']['max']) ? ' max="' . $config['extra']['max'] . '"' : '',
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
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
            [ //min
                'key' => 'min',
                'label' => __('Min', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'number',
                'extra' => [
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
                'key' => 'max',
                'label' => __('Max', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'number',
                'extra' => [
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
            return sanitize_text_field($value);
        }
    ];
    return $fields;
}, 20);