<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['time'] = [
        'label' => __('Time picker', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            $value = !empty($config['value']) && DateTime::createFromFormat('H:i', $config['value']) ? $config['value'] : '';
            $options = '<option value=""></option>';
            return sprintf(
                '<div class="cpt-time-section"><select name="%s" id="%s" autocomplete="off" aria-autocomplete="none" style="width: 100%%;"%s%s%s%s%s>%s</select></div>',
                $name,
                $id,
                !empty($value) ? ' data-value="' . $value . '"' : '',
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['min']) ? ' data-min="' . $config['extra']['min'] . '"' : '',
                !empty($config['extra']['max']) ? ' data-max="' . $config['extra']['max'] . '"' : '',
                !empty($config['required']) ? ' required' : '',
                $options
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
                'label' => __('Minimum limit to selectable time', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'time',
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
                'label' => __('Maximum limit to selectable time', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'time',
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
            $date = DateTime::createFromFormat('H:i', $value);
            return $date ? $value : '';
        },
        'getCallback' => function ($output) {
            if (empty($output)) return;
            $config_format = get_option('time_format');
            return !empty($config_format) ? date($config_format, strtotime($output)) : $output;
        }
    ];
    return $fields;
}, 110);