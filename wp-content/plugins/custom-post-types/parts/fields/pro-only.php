<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

$proFields = [
    'range' => [
        'label' => __('Range', 'custom-post-types'),
        'order' => 45,
    ],
    'switch' => [
        'label' => __('Switch ON/OFF', 'custom-post-types'),
        'order' => 75,
    ],
    'password' => [
        'label' => __('Password', 'custom-post-types'),
        'order' => 95,
    ],
    'link' => [
        'label' => __('Link', 'custom-post-types'),
        'order' => 95,
    ],
    'user_rel' => [
        'label' => __('User relationship', 'custom-post-types'),
        'order' => 155,
    ],
    'separator' => [
        'label' => __('Separator', 'custom-post-types'),
        'order' => 165,
    ],
];
foreach ($proFields as $key => $args) {
    add_filter($utils->getHookName('field_types'), function ($fields) use ($utils, $key, $args) {
        $fields[$key] = [
            'label' => $args['label'] . ' <sup>[' . __('PRO only', 'custom-post-types') . ']</sup>',
            'templateCallback' => function ($name, $id, $config) use ($utils) {
                return $utils->getProBanner();
            },
        ];
        return $fields;
    }, $args['order']);
}