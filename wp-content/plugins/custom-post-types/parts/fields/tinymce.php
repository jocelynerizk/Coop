<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['tinymce'] = [
        'label' => __('WYSIWYG editor', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            return sprintf(
                '<textarea name="%s" id="%s" autocomplete="off" aria-autocomplete="none"%s%s%s>%s</textarea>',
                $name,
                $id,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['autoresize']) ? ' autoresize' : '',
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
            [ //autoresize
                'key' => 'autoresize',
                'label' => __('Auto-resize', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'select',
                'extra' => [
                    'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                    'multiple' => false,
                    'options' => [
                        'true' => __('YES', 'custom-post-types'),
                        'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                    ]
                ],
                'wrap' => [
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ]
        ],
        'sanitizeCallback' => function ($value) {
            return wp_kses_post($value);
        }
    ];
    return $fields;
}, 40);