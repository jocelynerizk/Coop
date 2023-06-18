<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) use ($utils) {
    $fields['tax_rel'] = [
        'label' => __('Taxonomy relationship', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) use ($utils) {
            $options = '<option value=""></option>';

            if (isset($config['value'])) {
                $termIds = !empty($config['value']) ? (is_array($config['value']) ? $config['value'] : [$config['value']]) : [];
                foreach ($termIds as $termId) {
                    $term = get_term($termId);
                    if (!isset($term->name)) continue;
                    $options .= sprintf(
                        '<option value="%s" selected="selected">%s</option>',
                        $termId,
                        $utils->getTermTitleWithParents($termId)
                    );
                }
            }

            return sprintf(
                '<select name="%s" id="%s" autocomplete="off" aria-autocomplete="none" style="width: 100%%;"%s%s data-type="%s"%s>%s</select>',
                $name . (!empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? '[]' : ''),
                $id,
                !empty($config['extra']['placeholder']) ? ' placeholder="' . $config['extra']['placeholder'] . '"' : '',
                !empty($config['extra']['multiple']) && $config['extra']['multiple'] == 'true' ? ' multiple' : '',
                !empty($config['extra']['taxonomy']) ? $config['extra']['taxonomy'] : 'category',
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
            [ //taxonomy
                'key' => 'taxonomy',
                'label' => __('Taxonomy', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'select',
                'extra' => [
                    'placeholder' => __('Categories', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                    'multiple' => false,
                    'options' => $utils->getTaxonomiesOptions(),
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
                $terms = [];
                foreach ($output as $term_id) {
                    if (!get_term((int) $term_id)) continue;
                    $terms[] = sprintf('<a href="%1$s" title="%2$s" aria-label="%2$s">%2$s</a>', get_term_link((int) $term_id), get_term((int) $term_id)->name);
                }
                return implode(', ', $terms);
            }
            if (!get_term((int) $output)) return;
            return sprintf('<a href="%1$s" title="%2$s" aria-label="%2$s">%2$s</a>', get_term_link((int) $output), get_term((int) $output)->name);
        }
    ];
    return $fields;
}, 150);

add_filter($utils->getHookName('register_ajax_actions'), function ($actions) use ($utils) {
    $actions['cpt-get-tax_rel-options'] = [
        'requiredParams' => ['taxonomy'],
        'callback' => function ($params) use ($utils) {
            $taxonomy = $params['taxonomy'];
            $search = isset($params['search']) ? $params['search'] : '';
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'name__like' => $search,
                'hide_empty' => false,
                'number' => 10
            ]);
            $result = [];
            foreach ($terms as $term) {
                $result[] = [
                    'id' => $term->term_id,
                    'text' => $utils->getTermTitleWithParents($term->term_id)
                ];
            }
            return $result;
        },
    ];
    return $actions;
});