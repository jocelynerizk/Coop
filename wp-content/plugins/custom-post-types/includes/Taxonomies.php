<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Taxonomies extends Component
{
    /**
     * @var array
     */
    private $defaultArgs = [];

    /**
     * @var array
     */
    private $defaultLabels = [];

    /**
     * @return void
     */
    private function initDefaults()
    {
        if(empty($this->defaultArgs)){
            $this->defaultArgs = [
                'description' => __('Taxonomy created with the "Custom post types" plugin.', 'custom-post-types'),
                'public' => true,
                'hierarchical' => false,
                // 'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                // 'show_in_nav_menus' => true,
                // 'show_in_admin_bar' => false,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'capabilities' => [
                    'manage_terms' => 'edit_posts',
                    'edit_terms' => 'edit_posts',
                    'delete_terms' => 'edit_posts',
                    'assign_terms' => 'edit_posts'
                ],
                'rewrite' => [
                    'with_front' => false,
                    'hierarchical' => true
                ],
                // 'query_var' =>  true,
            ];
        }
        if(empty($this->defaultLabels)){
            $this->defaultLabels = [
                'name' => _x('%s', 'taxonomy general name', 'custom-post-types'),
                'menu_name' => __('%s', 'custom-post-types'),
                'singular_name' => _x('%s', 'taxonomy singular name', 'custom-post-types'),
                'all_items' => __('All %s', 'custom-post-types'),
                'edit_item' => __('Edit %s', 'custom-post-types'),
                'view_item' => __('View %s', 'custom-post-types'),
                'update_item' => __('Update %s', 'custom-post-types'),
                'add_new_item' => __('Add %s', 'custom-post-types'),
                'new_item_name' => __('%s name', 'custom-post-types'),
                'parent_item' => __('Parent %s', 'custom-post-types'),
                'parent_item_colon' => __('Parent %s', 'custom-post-types'),
                'search_items' => __('Search %s', 'custom-post-types'),
                'popular_items' => __('Popular %s', 'custom-post-types'),
                'separate_items_with_commas' => __('Separate %s with commas', 'custom-post-types'),
                'add_or_remove_items' => __('Add or remove %s', 'custom-post-types'),
                'choose_from_most_used' => __('Choose from the most used %s', 'custom-post-types'),
                'not_found' => __('No %s found.', 'custom-post-types'),
                'back_to_items' => __('← Back to %s', 'custom-post-types'),
            ];
        }
    }

    /**
     * @return array
     */
    private function getRegisteredTaxonomies()
    {
        $registeredTaxonomies = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . '_tax',
            'post_status' => 'publish'
        ]);

        $taxonomiesByUi = [];

        foreach ($registeredTaxonomies as $taxonomy) {
            $postMetas = get_post_meta($taxonomy->ID, '');

            $taxonomyId = $postMetas['id'][0];
            $taxonomySingular = $postMetas['singular'][0];
            $taxonomyPlural = $postMetas['plural'][0];
            $taxonomySlug = !empty($postMetas['slug'][0]) ? sanitize_title($postMetas['slug'][0]) : sanitize_title($taxonomyPlural);

            unset($postMetas['id'], $postMetas['singular'], $postMetas['plural'], $postMetas['slug']);

            $taxonomyLabels = [];
            $taxonomyArgs = [];

            foreach ($postMetas as $key => $value) {
                $singleMeta = get_post_meta($taxonomy->ID, $key, true);
                if (substr($key, 0, 7) === "labels_") {
                    if (!empty($singleMeta)) {
                        $taxonomyLabels[str_replace("labels_", "", $key)] = $singleMeta;
                    }
                } elseif (substr($key, 0, 1) === "_" || empty($singleMeta)) {
                    unset($postMetas[$key]);
                } else {
                    $taxonomyArgs[$key] = in_array($singleMeta, ['true', 'false']) ? ($singleMeta === 'true') : $singleMeta;
                }
                unset($postMetas[$key]);
            }

            $taxonomyPostTypes = !empty($taxonomyArgs['supports']) && is_array($taxonomyArgs['supports']) ? $taxonomyArgs['supports'] : [];
            unset($taxonomyArgs['supports']);

            $taxonomyArgs['rewrite']['slug'] = $taxonomySlug;

            $taxonomiesByUi[] = [
                'id' => $taxonomyId,
                'singular' => $taxonomySingular,
                'plural' => $taxonomyPlural,
                'post_types' => $taxonomyPostTypes,
                'args' => $taxonomyArgs,
                'labels' => $taxonomyLabels
            ];
        }

        return (array)apply_filters($this->getHookName('register_taxonomies'), $taxonomiesByUi);
    }

    /**
     * @param string $singular
     * @param string $plural
     * @return array
     */
    private function getTaxonomyDefaultLabels($singular = '', $plural = '')
    {
        $this->initDefaults();
        $labels = $this->defaultLabels;
        foreach ($labels as $key => $label) {
            $isSingular = !in_array($key, ['name', 'menu_name', 'popular_items', 'search_items', 'not_found', 'all_items', 'back_to_items', 'add_or_remove_items', 'separate_items_with_commas']);
            $labels[$key] = sprintf($label, ($isSingular ? $singular : $plural));
        }
        return $labels;
    }

    /**
     * @param bool $adminOnly
     * @return array
     */
    private function getTaxonomyDefaultArgs($adminOnly = false)
    {
        $this->initDefaults();
        $args = $this->defaultArgs;
        if ($adminOnly) {
            $args['capabilities'] = [
                'manage_terms' => 'update_core',
                'edit_terms' => 'update_core',
                'delete_terms' => 'update_core',
                'assign_terms' => 'update_core',
            ];
        }
        return $args;
    }

    /**
     * @return void
     */
    public function initRegisteredTaxonomies()
    {
        $taxonomies = $this->getRegisteredTaxonomies();

        foreach ($taxonomies as $i => $taxonomy) {
            $id = !empty($taxonomy['id']) && is_string($taxonomy['id']) ? $taxonomy['id'] : false;
            $singular = !empty($taxonomy['singular']) && is_string($taxonomy['singular']) ? $taxonomy['singular'] : false;
            $plural = !empty($taxonomy['plural']) && is_string($taxonomy['plural']) ? $taxonomy['plural'] : false;
            $postTypes = !empty($taxonomy['post_types']) && is_array($taxonomy['post_types']) ? $taxonomy['post_types'] : [];
            $args = !empty($taxonomy['args']) && is_array($taxonomy['args']) ? $taxonomy['args'] : [];
            $labels = !empty($taxonomy['labels']) && is_array($taxonomy['labels']) ? $taxonomy['labels'] : [];

            if (!$id || !$singular || !$plural) {
                $errorInfo = $this->getRegistrationErrorNoticeInfo($taxonomy, 'tax');

                add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                    $args[] = [
                        'id' => $errorInfo['id'],
                        'title' => $this->getNoticesTitle(),
                        'message' => __('Taxonomy registration was not successful ("id" "singular" and "plural" args are required).', 'custom-post-types') . $errorInfo['details'],
                        'type' => 'error',
                        'dismissible' => 3,
                        'buttons' => false,
                    ];
                    return $args;
                });
                unset($taxonomies[$i]);
                continue;
            }

            $adminOnly = isset($args['admin_only']) ? $args['admin_only'] : false;

            $registrationLabels = array_replace_recursive($this->getTaxonomyDefaultLabels($singular, $plural), $labels);
            $registrationLabels = apply_filters($this->getHookName('register_tax_labels_' . $id), $registrationLabels);

            $registrationArgs = array_replace_recursive($this->getTaxonomyDefaultArgs($adminOnly), $args);
            $registrationArgs['labels'] = $registrationLabels;
            $registrationArgs = apply_filters($this->getHookName('register_tax_args_' . $id), $registrationArgs);

            $register = register_taxonomy($id, $postTypes, $registrationArgs);

            if (is_wp_error($register)) {
                $errorInfo = $this->getRegistrationErrorNoticeInfo($taxonomy, 'tax');

                add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                    $args[] = [
                        'id' => $errorInfo['id'] . '_core',
                        'title' => $this->getNoticesTitle(),
                        'message' => __('Taxonomy registration was not successful.', 'custom-post-types') . $errorInfo['details'],
                        'type' => 'error',
                        'dismissible' => 3,
                        'buttons' => false,
                    ];
                    return $args;
                });
                unset($taxonomies[$i]);
            }
        }

        $this->flushRewriteRules($taxonomies);
    }
}