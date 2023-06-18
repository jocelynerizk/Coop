<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['radio'] = [
        'label' => __('Radio', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            ob_start();
            foreach ($config['extra']['options'] as $value => $label){
                printf(
                    '<label><input type="radio" name="%s" value="%s"%s%s>%s<label><br>',
                    $name,
                    $value,
                    $value == $config['value'] ? ' checked="checked"' : '',
                    !empty($config['required']) ? ' required' : '',
                    $label
                );
            }
            return ob_get_clean();
        },
        'extra' => [
            [ //options
                'key' => 'options',
                'label' => __('Options', 'custom-post-types'),
                'info' => __('One per row (value|label).', 'custom-post-types'),
                'required' => true,
                'type' => 'textarea',
                'extra' => [],
                'wrap' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ]
        ]
    ];
    return $fields;
}, 60);