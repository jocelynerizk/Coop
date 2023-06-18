<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['checkbox'] = [
        'label' => __('Checkbox', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            ob_start();
            foreach ($config['extra']['options'] as $value => $label){
                printf(
                    '<label><input type="checkbox" name="%s[]" value="%s"%s%s>%s<label><br>',
                    $name,
                    $value,
                    is_array($config['value']) && in_array($value, $config['value']) ? ' checked="checked"' : '',
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
        ],
        'getCallback' => function ($output) {
            if (empty($output)) return;
            return is_array($output) ? implode(', ', $output) : $output;
        }
    ];
    return $fields;
}, 50);