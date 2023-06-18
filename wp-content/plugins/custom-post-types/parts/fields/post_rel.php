<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) use ($utils) {
    $fields['post_rel'] = [
        'label' => __('Post relationship', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) use ($utils) {
            $options = '<option value=""></option>';

            if (isset($config['value'])) {
                $postIds = !empty($config['value']) ? (is_array($config['value']) ? $config['value'] : [$config['value']]) : [];
                foreach ($postIds as $postId) {
                    $post = get_post($postId);
                    if (!isset($post->post_title)) continue;
                    $options .= sprintf(
                        '<option value="%s" selected="selected">%s</option>',
                        $postId,
                        $utils->getPostTitleWithParents($postId)
                    );
                }
            }

            return sprintf(
                '<select name="%s" id="%s" autocomplete="off" aria-autocomplete="none" style="width: 100%%;"%s%s data-type="%s"%s>%s</select>',
                $name . (!empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? '[]' : ''),
                $id,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? ' multiple' : '',
                !empty($config['extra']['post_type']) ? $config['extra']['post_type'] : 'post',
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
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
            [ //post_type
                'key' => 'post_type',
                'label' => __('Post type', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'select',
                'extra' => [
                    'placeholder' => __('Posts', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                    'multiple' => false,
                    'options' => $utils->getPostTypesOptions(),
                ],
                'wrap' => [
                    'width' => '25',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
        ],
        'getCallback' => function ($output) {
            if (empty($output)) return;
            if (is_array($output)) {
                $posts = [];
                foreach ($output as $post_id) {
                    if (!get_post((int) $post_id)) continue;
                    $posts[] = sprintf('<a href="%1$s" title="%2$s" aria-label="%2$s">%2$s</a>', get_permalink((int) $post_id), get_the_title((int) $post_id));
                }
                return implode(', ', $posts);
            }
            if (!get_post((int) $output)) return;
            return sprintf('<a href="%1$s" title="%2$s" aria-label="%2$s">%2$s</a>', get_permalink((int) $output), get_the_title((int) $output));
        }
    ];
    return $fields;
}, 140);

add_filter($utils->getHookName('register_ajax_actions'), function ($actions) use ($utils) {
    $actions['cpt-get-post_rel-options'] = [
        'requiredParams' => ['post_type'],
        'callback' => function ($params) use ($utils) {
            $post_type = $params['post_type'];
            $search = isset($params['search']) ? $params['search'] : '';
            $posts = get_posts([
                'post_type' => $post_type,
                's' => $search,
                'numberposts' => 10
            ]);
            $result = [];
            foreach ($posts as $post) {
                $result[] = [
                    'id' => $post->ID,
                    'text' => $utils->getPostTitleWithParents($post->ID)
                ];
            }
            return $result;
        },
    ];
    return $actions;
});