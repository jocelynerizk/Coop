<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['date'] = [
        'label' => __('Date picker', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            $value = !empty($config['value']) && DateTime::createFromFormat('Y-m-d', $config['value']) ? DateTime::createFromFormat('Y-m-d', $config['value'])->format('d/m/Y') : $config['value'];
            return sprintf(
                '<div class="cpt-date-section"><input type="text" name="%s" id="%s" value="%s" autocomplete="off" aria-autocomplete="none"%s%s%s%s></div>',
                $name,
                $id,
                $value,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['min']) ? ' data-min="' . $config['extra']['min'] . '"' : '',
                !empty($config['extra']['max']) ? ' data-max="' . $config['extra']['max'] . '"' : '',
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
                'label' => __('Minimum limit to selectable date', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'date',
                'extra' => [],
                'wrap' => [
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
            [ //max
                'key' => 'max',
                'label' => __('Maximum limit to selectable date', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'date',
                'extra' => [],
                'wrap' => [
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
        ],
        'sanitizeCallback' => function ($value) {
            $date = DateTime::createFromFormat('d/m/Y', $value);
            return $date ? $date->format('Y-m-d') : '';
        },
        'getCallback' => function ($output) {
            if (empty($output)) return;
            $config_format = get_option('date_format');
            return !empty($config_format) ? date($config_format, strtotime($output)) : $output;
        }
    ];
    return $fields;
}, 100);