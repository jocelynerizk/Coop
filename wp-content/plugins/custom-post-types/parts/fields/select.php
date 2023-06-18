<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['select'] = [
        'label' => __('Dropdown', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            $options = '<option value=""></option>';
            foreach ($config['extra']['options'] as $value => $label) {
                if (is_array($label)) {
                    $childOptions = '';
                    foreach ($label as $childValue => $childLabel) {
                        $childOptions .= sprintf(
                            '<option value="%s"%s>%s</option>',
                            $childValue,
                            (is_array($config['value']) && in_array($childValue, $config['value'])) ||
                            (!is_array($config['value']) && $childValue == $config['value']) ?
                                '  selected="selected"' :
                                '',
                            $childLabel
                        );
                    }
                    $options .= sprintf(
                            '<optgroup label="%s">%s</optgroup>',
                        $value,
                        $childOptions
                    );
                } else {
                    $options .= sprintf(
                        '<option value="%s"%s>%s</option>',
                        $value,
                        (is_array($config['value']) && in_array($value, $config['value'])) ||
                        (!is_array($config['value']) && $value == $config['value']) ?
                            '  selected="selected"' :
                            '',
                        $label
                    );
                }
            }
            return sprintf(
                '<select name="%s" id="%s" autocomplete="off" aria-autocomplete="none" style="width: 100%%;"%s%s%s>%s</select>',
                $name . (!empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? '[]' : ''),
                $id,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? ' multiple' : '',
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
            [ //multiple
                'key' => 'multiple',
                'label' => __('Multiple', 'custom-post-types'),
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
            ],
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
}, 70);