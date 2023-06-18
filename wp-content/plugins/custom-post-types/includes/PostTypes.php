<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class PostTypes extends Component
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
        if (empty($this->defaultArgs)) {
            $this->defaultArgs = [
                'description' => __('Post type created with the "Custom post types" plugin.', 'custom-post-types'),
                'public' => true,
                'hierarchical' => false,
                'exclude_from_search' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => true,
                'menu_icon' => 'dashicons-tag',
                'capabilities' => [
                    'edit_post' => 'edit_posts',
                    'read_post' => 'edit_posts',
                    'delete_post' => 'edit_posts',
                    'edit_posts' => 'edit_posts',
                    'edit_others_posts' => 'edit_posts',
                    'delete_posts' => 'edit_posts',
                    'publish_posts' => 'edit_posts',
                    'read_private_posts' => 'edit_posts'
                ],
                'supports' => ['title'],
                'has_archive' => false,
                'rewrite' => [
                    'with_front' => false
                ],
            ];
        }
        if (empty($this->defaultLabels)) {
            $this->defaultLabels = [
                'name' => _x('%s', 'post type general name', 'custom-post-types'),
                'menu_name' => __('%s', 'custom-post-types'),
                'singular_name' => _x('%s', 'post type singular name', 'custom-post-types'),
                'add_new' => _x('Add New', 'post', 'custom-post-types'),
                'add_new_item' => __('Add New %s', 'custom-post-types'),
                'edit_item' => __('Edit %s', 'custom-post-types'),
                'new_item' => __('New %s', 'custom-post-types'),
                'view_item' => __('View %s', 'custom-post-types'),
                'view_items' => __('View %s', 'custom-post-types'),
                'search_items' => __('Search %s', 'custom-post-types'),
                'not_found' => __('No %s found.', 'custom-post-types'),
                'not_found_in_trash' => __('No %s found in Trash.', 'custom-post-types'),
                'parent_item_colon' => __('Parent %s', 'custom-post-types'),
                'all_items' => __('All %s', 'custom-post-types'),
                'archives' => __('%s Archives', 'custom-post-types'),
                'attributes' => __('%s Attributes', 'custom-post-types'),
                'insert_into_item' => __('Insert into %s', 'custom-post-types'),
                'uploaded_to_this_item' => __('Uploaded to this %s', 'custom-post-types'),
                'featured_image' => __('Featured image', 'custom-post-types'),
                'set_featured_image' => __('Set featured image', 'custom-post-types'),
                'remove_featured_image' => __('Remove featured image', 'custom-post-types'),
                'use_featured_image' => __('Use as featured image', 'custom-post-types'),
                'filter_items_list' => __('Filter %s list', 'custom-post-types'),
                'items_list_navigation' => __('%s list navigation', 'custom-post-types'),
                'items_list' => __('%s list', 'custom-post-types'),
                'item_published' => __('%s published.', 'custom-post-types'),
                'item_published_privately' => __('%s published privately.', 'custom-post-types'),
                'item_reverted_to_draft' => __('%s reverted to draft.', 'custom-post-types'),
                'item_scheduled' => __('%s scheduled.', 'custom-post-types'),
                'item_updated' => __('%s updated.', 'custom-post-types'),
            ];
        }
    }

    /**
     * @return array
     */
    private function getRegisteredPostTypes()
    {
        $registeredPostTypes = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix'),
            'post_status' => 'publish'
        ]);

        $postTypesByUi = [];

        foreach ($registeredPostTypes as $postType) {
            $postMetas = get_post_meta($postType->ID, '');

            $postTypeId = $postMetas['id'][0];
            $postTypeSingular = $postMetas['singular'][0];
            $postTypePlural = $postMetas['plural'][0];
            $postTypeSlug = !empty($postMetas['slug'][0]) ? sanitize_title($postMetas['slug'][0]) : sanitize_title($postTypePlural);

            unset($postMetas['id'], $postMetas['singular'], $postMetas['plural'], $postMetas['slug']);

            $postTypeLabels = [];
            $postTypeArgs = [];

            foreach ($postMetas as $key => $value) {
                $singleMeta = get_post_meta($postType->ID, $key, true);
                if (substr($key, 0, 7) === "labels_") {
                    if (!empty($singleMeta)) {
                        $postTypeLabels[str_replace("labels_", "", $key)] = $singleMeta;
                    }
                } elseif (substr($key, 0, 1) === "_" || empty($singleMeta)) {
                    unset($postMetas[$key]);
                } else {
                    $postTypeArgs[$key] = in_array($singleMeta, ['true', 'false']) ? ($singleMeta === 'true') : $singleMeta;
                }
                unset($postMetas[$key]);
            }

            $postTypeArgs['rewrite']['slug'] = $postTypeSlug;

            $postTypesByUi[] = [
                'id' => $postTypeId,
                'singular' => $postTypeSingular,
                'plural' => $postTypePlural,
                'args' => $postTypeArgs,
                'labels' => $postTypeLabels
            ];
        }

        unset($registeredPostTypes);

        return (array)apply_filters($this->getHookName('register_post_types'), $postTypesByUi);
    }

    /**
     * @param string $singular
     * @param string $plural
     * @return array
     */
    private function getPostTypeDefaultLabels($singular = '', $plural = '')
    {
        $this->initDefaults();
        $labels = $this->defaultLabels;
        foreach ($labels as $key => $label) {
            $isSingular = !in_array($key, ['name', 'menu_name', 'view_items', 'search_items', 'not_found', 'not_found_in_trash', 'all_items', 'filter_items_list', 'items_list_navigation', 'items_list']);
            $labels[$key] = sprintf($label, ($isSingular ? $singular : $plural));
        }
        return $labels;
    }

    /**
     * @param bool $adminOnly
     * @return array
     */
    private function getPostTypeDefaultArgs($adminOnly = false)
    {
        $this->initDefaults();
        $args = $this->defaultArgs;
        if ($adminOnly) {
            $args['capabilities'] = [
                'edit_post' => 'update_core',
                'read_post' => 'update_core',
                'delete_post' => 'update_core',
                'edit_posts' => 'update_core',
                'edit_others_posts' => 'update_core',
                'delete_posts' => 'update_core',
                'publish_posts' => 'update_core',
                'read_private_posts' => 'update_core'
            ];
        }
        return $args;
    }

    /**
     * @param $postType
     * @param $columns
     * @return void
     */
    private function addColumns($postType = 'post', $columns = [])
    {
        if (empty($postType) || empty($columns)) return;
        global $pagenow;
        if ('edit.php' === $pagenow && isset($_GET['post_type']) && $_GET['post_type'] === $postType) {
            add_filter('manage_posts_columns', function ($post_columns) use ($columns) {
                if (isset($columns['title'])) {
                    $stored_title_label = $post_columns['title'];
                }
                if (isset($columns['date'])) {
                    $stored_date_label = $post_columns['date'];
                }
                unset($post_columns['title']);
                unset($post_columns['date']);

                foreach ($columns as $key => $args) {
                    if ($key == 'title' && empty($args['label'])) $args['label'] = $stored_title_label;
                    if ($key == 'date' && empty($args['label'])) $args['label'] = $stored_date_label;
                    $post_columns[$key] = $args['label'];
                }
                return $post_columns;
            });
            add_action('manage_posts_custom_column', function ($post_column, $post_id) use ($columns) {
                if (isset($columns[$post_column]['callback'])) $columns[$post_column]['callback']($post_id);
            }, 10, 2);
        }
    }

    /**
     * @return void
     */
    public function initRegisteredPostTypes()
    {
        $postTypes = $this->getRegisteredPostTypes();

        foreach ($postTypes as $i => $postType) {
            $id = !empty($postType['id']) && is_string($postType['id']) ? $postType['id'] : false;
            $singular = !empty($postType['singular']) && is_string($postType['singular']) ? $postType['singular'] : false;
            $plural = !empty($postType['plural']) && is_string($postType['plural']) ? $postType['plural'] : false;
            $args = !empty($postType['args']) && is_array($postType['args']) ? $postType['args'] : [];
            $labels = !empty($postType['labels']) && is_array($postType['labels']) ? $postType['labels'] : [];

            if (!$id || !$singular || !$plural) {
                $errorInfo = $this->getRegistrationErrorNoticeInfo($postType);

                add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                    $args[] = [
                        'id' => $errorInfo['id'],
                        'title' => $this->getNoticesTitle(),
                        'message' => __('Post type registration was not successful ("id" "singular" and "plural" args are required).', 'custom-post-types') . $errorInfo['details'],
                        'type' => 'error',
                        'dismissible' => 3,
                        'buttons' => false,
                    ];
                    return $args;
                });
                unset($postTypes[$i]);
                continue;
            }

            $columns = !empty($postType['columns']) && is_array($postType['columns']) ? $postType['columns'] : false;
            if ($columns) {
                $this->addColumns($id, $columns);
            }

            $adminOnly = isset($args['admin_only']) ? $args['admin_only'] : false;

            $registrationLabels = array_replace_recursive($this->getPostTypeDefaultLabels($singular, $plural), $labels);
            $registrationLabels = apply_filters($this->getHookName('register_labels_' . $id), $registrationLabels);

            $registrationArgs = array_replace_recursive($this->getPostTypeDefaultArgs($adminOnly), $args);
            $registrationArgs['labels'] = $registrationLabels;
            $registrationArgs = apply_filters($this->getHookName('register_args_' . $id), $registrationArgs);

            $register = register_post_type($id, $registrationArgs);

            if (is_wp_error($register)) {
                $errorInfo = $this->getRegistrationErrorNoticeInfo($postType);

                add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                    $args[] = [
                        'id' => $errorInfo['id'] . '_core',
                        'title' => $this->getNoticesTitle(),
                        'message' => __('Post type registration was not successful.', 'custom-post-types') . $errorInfo['details'],
                        'type' => 'error',
                        'dismissible' => 3,
                        'buttons' => false,
                    ];
                    return $args;
                });
                unset($postTypes[$i]);
            }
        }

        $this->flushRewriteRules($postTypes);
    }
}