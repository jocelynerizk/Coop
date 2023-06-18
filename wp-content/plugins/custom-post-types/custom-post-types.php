<?php
/*
Plugin Name: Custom post types
Plugin URI: https://totalpress.org/plugins/custom-post-types?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types
Description: Create / manage custom post types, custom taxonomies, custom admin pages, custom fields and custom templates easily, directly from the WordPress dashboard without writing code.
Author: TotalPress.org
Author URI: https://totalpress.org/?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types
Text Domain: custom-post-types
Domain Path: /languages/
Version: 4.0.4
*/

namespace CustomPostTypesPlugin;

use CustomPostTypesPlugin\Includes\Component;
use CustomPostTypesPlugin\Includes\Fields;
use CustomPostTypesPlugin\Includes\Notices;
use CustomPostTypesPlugin\Includes\PostTypes;
use CustomPostTypesPlugin\Includes\Taxonomies;
use CustomPostTypesPlugin\Includes\Templates;
use CustomPostTypesPlugin\Includes\Pages;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

define('CPT_INFOS', [
    'version' => get_file_data(__FILE__, ['Version' => 'Version'], false)['Version'],
    'path' => plugin_dir_path(__FILE__),
    'url' => plugin_dir_url(__FILE__),
    'plugin_url' => 'https://totalpress.org/plugins/custom-post-types?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types',
    'plugin_dev_url' => 'https://www.andreadegiovine.it/?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types',
    'plugin_doc_url' => 'https://totalpress.org/docs/custom-post-types.html?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types',
    'plugin_donate_url' => 'https://totalpress.org/donate?utm_source=wp-dashboard&utm_medium=installed-plugin&utm_campaign=custom-post-types',
    'plugin_wporg_url' => 'https://wordpress.org/plugin/custom-post-types',
    'plugin_support_url' => 'https://wordpress.org/support/plugin/custom-post-types',
    'plugin_review_url' => 'https://wordpress.org/support/plugin/custom-post-types/reviews/#new-post',
    'hook_prefix' => 'cpt_',
    'ui_prefix' => 'manage_cpt',
    'options_prefix' => 'custom_post_types_',
    'nonce_key' => 'cpt-nonce'
], false);

// Autoload
foreach (
    array_merge(
        glob(CPT_INFOS['path'] . "includes/*.php"),
        glob(CPT_INFOS['path'] . "parts/fields/*.php")
    ) as $filename
) {
    include_once $filename;
}

class Core extends Component
{
    /**
     * @var PostTypes
     */
    private $postTypes;

    /**
     * @var Taxonomies
     */
    private $taxonomies;

    /**
     * @var Fields
     */
    private $fields;

    /**
     * @var Templates
     */
    private $templates;

    /**
     * @var Pages
     */
    private $adminPages;

    /**
     * @var Notices
     */
    private $notices;

    public function __construct()
    {
        // UI
        $this->registerUiPostTypes();
        $this->manipulateUiPostTypeTitle();
        $this->registerUiPages();
        $this->registerUiFields();
        $this->registerWelcomeNotices();
        $this->enqueueUiAssets();
        $this->initPluginUi();
        // Utilities
        $this->registerShortcodes();
        $this->ajaxAction();
        $this->applyUpdates();
        $this->pluginActions();
        // Lets go
        $this->initRegisteredContents();
    }

    /**
     * @return PostTypes
     */
    private function getPostTypes()
    {
        if ($this->postTypes instanceof PostTypes) {
            return $this->postTypes;
        }

        $this->postTypes = new PostTypes();
        return $this->postTypes;
    }

    /**
     * @return Taxonomies
     */
    private function getTaxonomies()
    {
        if ($this->taxonomies instanceof Taxonomies) {
            return $this->taxonomies;
        }

        $this->taxonomies = new Taxonomies();
        return $this->taxonomies;
    }

    /**
     * @return Fields
     */
    private function getFields()
    {
        if ($this->fields instanceof Fields) {
            return $this->fields;
        }

        $this->fields = new Fields();
        return $this->fields;
    }

    /**
     * @return Templates
     */
    public function getTemplates()
    {
        if ($this->templates instanceof Templates) {
            return $this->templates;
        }

        $this->templates = new Templates();
        return $this->templates;
    }

    /**
     * @return Pages
     */
    private function getAdminPages()
    {
        if ($this->adminPages instanceof Pages) {
            return $this->adminPages;
        }

        $this->adminPages = new Pages();
        return $this->adminPages;
    }

    /**
     * @return Notices
     */
    public function getNotices()
    {
        if ($this->notices instanceof Notices) {
            return $this->notices;
        }

        $this->notices = new Notices();
        return $this->notices;
    }

    /**
     * @return void
     */
    private function registerUiPostTypes()
    {
        // Register ui post types
        add_filter($this->getHookName('register_post_types'), function ($args) {
            $default_args = [
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_rest' => false,
                'query_var' => false,
                'rewrite' => false,
                'capabilities' => [
                    'edit_post' => 'update_core',
                    'read_post' => 'update_core',
                    'delete_post' => 'update_core',
                    'edit_posts' => 'update_core',
                    'edit_others_posts' => 'update_core',
                    'delete_posts' => 'update_core',
                    'publish_posts' => 'update_core',
                    'read_private_posts' => 'update_core'
                ],
                'has_archive' => false,
                'hierarchical' => false,
                'menu_position' => null,
                'supports' => [''],
                'menu_icon' => 'dashicons-index-card',
                'can_export' => false,
            ];
            // Create/edit new post type
            $args[] = [
                'id' => $this->getInfo('ui_prefix'),
                'singular' => __('Post type', 'custom-post-types'),
                'plural' => __('Post types', 'custom-post-types'),
                'labels' => [
                    'name' => _x('Custom post types', 'Dashboard menu', 'custom-post-types'),
                    'singular_name' => __('Post type', 'custom-post-types'),
                    'menu_name' => __('Extend / Manage', 'custom-post-types'),
                    'name_admin_bar' => __('Post type', 'custom-post-types'),
                    'add_new' => __('Add post type', 'custom-post-types'),
                    'add_new_item' => __('Add new post type', 'custom-post-types'),
                    'new_item' => __('New post type', 'custom-post-types'),
                    'edit_item' => __('Edit post type', 'custom-post-types'),
                    'view_item' => __('View post type', 'custom-post-types'),
                    'item_updated' => __('Post type updated', 'custom-post-types'),
                    'all_items' => _x('Post types', 'Dashboard menu', 'custom-post-types'),
                    'search_items' => __('Search post type', 'custom-post-types'),
                    'not_found' => __('No post type available.', 'custom-post-types'),
                    'not_found_in_trash' => __('No post type in the trash.', 'custom-post-types')
                ],
                'args' => array_replace_recursive($default_args, [
                    'description' => __('Create and manage custom post types.', 'custom-post-types'),
                ]),
                'columns' => [
                    'title' => [
                        'label' => __('Plural', 'custom-post-types'),
                    ],
                    'item_key' => [
                        'label' => __('Key', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            echo get_post_meta($post_id, 'id', true);
                        }
                    ],
                    'item_count' => [
                        'label' => __('Count', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            $key = get_post_meta($post_id, 'id', true);
                            if (empty($key) || !(isset(wp_count_posts($key)->publish) ? wp_count_posts($key)->publish : false)) {
                                echo "0";
                                return;
                            }
                            printf(
                                '<a href="%s" title="%s">%s</a>',
                                admin_url('edit.php?post_type=' . $key),
                                __('View', 'custom-post-types'),
                                wp_count_posts($key)->publish
                            );
                        }
                    ],
                    'date' => [],
                ]
            ];
            // Create/edit new tax
            $args[] = [
                'id' => $this->getInfo('ui_prefix') . '_tax',
                'singular' => __('Taxonomy', 'custom-post-types'),
                'plural' => __('Taxonomies', 'custom-post-types'),
                'labels' => [
                    'name' => __('Custom taxonomies', 'custom-post-types'),
                    'singular_name' => __('Taxonomy', 'custom-post-types'),
                    'menu_name' => __('Taxonomy', 'custom-post-types'),
                    'name_admin_bar' => __('Taxonomy', 'custom-post-types'),
                    'add_new' => __('Add taxonomy', 'custom-post-types'),
                    'add_new_item' => __('Add new taxonomy', 'custom-post-types'),
                    'new_item' => __('New taxonomy', 'custom-post-types'),
                    'edit_item' => __('Edit taxonomy', 'custom-post-types'),
                    'view_item' => __('View taxonomy', 'custom-post-types'),
                    'item_updated' => __('Taxonomy updated', 'custom-post-types'),
                    'all_items' => __('Taxonomies', 'custom-post-types'),
                    'search_items' => __('Search taxonomy', 'custom-post-types'),
                    'not_found' => __('No taxonomy available.', 'custom-post-types'),
                    'not_found_in_trash' => __('No taxonomy in the trash.', 'custom-post-types')
                ],
                'args' => array_replace_recursive($default_args, [
                    'description' => __('Create and manage custom taxonomies.', 'custom-post-types'),
                    'show_in_menu' => 'edit.php?post_type=' . $this->getInfo('ui_prefix')
                ]),
                'columns' => [
                    'title' => [
                        'label' => __('Plural', 'custom-post-types'),
                    ],
                    'item_key' => [
                        'label' => __('Key', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            echo get_post_meta($post_id, 'id', true);
                        }
                    ],
                    'item_count' => [
                        'label' => __('Count', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            $key = get_post_meta($post_id, 'id', true);
                            if (empty($key) || is_wp_error(wp_count_terms(['taxonomy' => $key]))) {
                                echo "0";
                                return;
                            }
                            printf(
                                '<a href="%s" title="%s">%s</a>',
                                admin_url('edit-tags.php?taxonomy=' . $key),
                                __('View', 'custom-post-types'),
                                wp_count_terms(['taxonomy' => $key])
                            );
                        }
                    ],
                    'used_by' => [
                        'label' => __('Used by', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            $supports = get_post_meta($post_id, 'supports', true);
                            if (empty($supports)) return;
                            $output = [];
                            foreach ($supports as $post_type) {
                                if (!get_post_type_object($post_type)) continue;
                                $output[] = sprintf(
                                    '<a href="%s" title="%s">%s</a>',
                                    admin_url('edit.php?post_type=' . $post_type),
                                    __('View', 'custom-post-types'),
                                    get_post_type_object($post_type)->labels->name
                                );
                            }
                            echo implode(', ', $output);
                        }
                    ],
                    'date' => [],
                ]
            ];
            // Create/edit new fieldsgroup
            $args[] = [
                'id' => $this->getInfo('ui_prefix') . '_field',
                'singular' => __('Field group', 'custom-post-types'),
                'plural' => __('Field groups', 'custom-post-types'),
                'labels' => [
                    'name' => __('Custom field groups', 'custom-post-types'),
                    'singular_name' => __('Field group', 'custom-post-types'),
                    'menu_name' => __('Field group', 'custom-post-types'),
                    'name_admin_bar' => __('Field group', 'custom-post-types'),
                    'add_new' => __('Add field group', 'custom-post-types'),
                    'add_new_item' => __('Add new field group', 'custom-post-types'),
                    'new_item' => __('New field group', 'custom-post-types'),
                    'edit_item' => __('Edit field group', 'custom-post-types'),
                    'view_item' => __('View field group', 'custom-post-types'),
                    'item_updated' => __('Field group updated', 'custom-post-types'),
                    'all_items' => __('Field groups', 'custom-post-types'),
                    'search_items' => __('Search field group', 'custom-post-types'),
                    'not_found' => __('No field group available.', 'custom-post-types'),
                    'not_found_in_trash' => __('No field group in the trash.', 'custom-post-types')
                ],
                'args' => array_replace_recursive($default_args, [
                    'description' => __('Create and manage custom field groups.', 'custom-post-types'),
                    'show_in_menu' => 'edit.php?post_type=' . $this->getInfo('ui_prefix'),
                    'supports' => ['title']
                ]),
                'columns' => [
                    'title' => [
                        'label' => __('Field group name', 'custom-post-types'),
                    ],
                    'item_count' => [
                        'label' => __('Fields', 'custom-post-types') . ' (' . __('Key', 'custom-post-types') . ')',
                        'callback' => function ($post_id) {
                            $fields = get_post_meta($post_id, 'fields', true);
                            if (empty($fields)) return;
                            $fields_labels_array = array_map(
                                function ($field) {
                                    return $field['label'] . ' (' . $field['key'] . ')';
                                },
                                $fields
                            );
                            echo implode(', ', $fields_labels_array);
                        }
                    ],
                    'item_position' => [
                        'label' => __('Position', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            $available = [
                                '' => __('NORMAL', 'custom-post-types'),
                                'normal' => __('NORMAL', 'custom-post-types'),
                                'side' => __('SIDEBAR', 'custom-post-types'),
                                'advanced' => __('ADVANCED', 'custom-post-types'),
                            ];
                            echo $available[get_post_meta($post_id, 'position', true)];
                        }
                    ],
                    'used_by' => [
                        'label' => __('Used by', 'custom-post-types'),
                        'callback' => function ($post_id) {
                            $supports = get_post_meta($post_id, 'supports', true);
                            if (empty($supports)) return;
                            $output = [];
                            foreach ($supports as $post_type) {
                                $content_type = 'cpt';
                                $content = $post_type;

                                if (strpos($post_type, '/') !== false) {
                                    $content_type = explode('/', $post_type)[0];
                                    $content = explode('/', $post_type)[1];
                                }

                                switch ($content_type) {
                                    case 'cpt':
                                        if (get_post_type_object($content)) {
                                            $output[] = sprintf(
                                                '<a href="%s" title="%s">%s</a>',
                                                admin_url('edit.php?post_type=' . $content),
                                                __('View', 'custom-post-types'),
                                                get_post_type_object($content)->labels->name
                                            );
                                        }
                                        break;
                                    case 'tax':
                                        if (get_taxonomy($content)) {
                                            $output[] = sprintf(
                                                '<a href="%s" title="%s">%s</a>',
                                                admin_url('edit-tags.php?taxonomy=' . $content),
                                                __('View', 'custom-post-types'),
                                                get_taxonomy($content)->labels->name
                                            );
                                        }
                                        break;
                                    case 'extra':
                                        if ($content == 'users') {
                                            $output[] = sprintf(
                                                '<a href="%s" title="%s">%s</a>',
                                                admin_url('users.php'),
                                                __('View', 'custom-post-types'),
                                                __('Users')
                                            );
                                        }
                                        break;
                                    case 'options':
                                        if (isset($this->getSettingsPagesOptions()[$content])) {
                                            $pageUrl = !empty($this->getSettingsPagesOptions()[$content]['url']) ? admin_url($this->getSettingsPagesOptions()[$content]['url']) : menu_page_url($content, false);
                                            $output[] = sprintf(
                                                '<a href="%s" title="%s">%s</a>',
                                                $pageUrl,
                                                __('View', 'custom-post-types'),
                                                $this->getSettingsPagesOptions()[$content]['title']
                                            );
                                        }
                                        break;
                                }
                            }
                            echo implode(', ', $output);
                        }
                    ],
                    'date' => [],
                ]
            ];
            return $args;
        });

        // Remove quick edit links
        add_filter('post_row_actions', function ($actions, $post) {
            $postType = $post->post_type;
            if (stripos($postType, $this->getInfo('ui_prefix')) !== false) {
                unset($actions['inline hide-if-no-js']);
            }
            return $actions;
        }, 1, 2);

        // Update ui notices
        add_filter('post_updated_messages', function ($messages) {
            $messages[$this->getInfo('ui_prefix')] = [
                1 => __('Post type updated', 'custom-post-types'),
                4 => __('Post type updated', 'custom-post-types'),
                6 => __('Post type published', 'custom-post-types'),
                7 => __('Post type saved', 'custom-post-types'),
                8 => __('Post type submitted', 'custom-post-types'),
                9 => __('Post type scheduled', 'custom-post-types'),
                10 => __('Post type draft updated', 'custom-post-types'),
            ];
            $messages[$this->getInfo('ui_prefix') . '_tax'] = [
                1 => __('Taxonomy updated', 'custom-post-types'),
                4 => __('Taxonomy updated', 'custom-post-types'),
                6 => __('Taxonomy published', 'custom-post-types'),
                7 => __('Taxonomy saved', 'custom-post-types'),
                8 => __('Taxonomy submitted', 'custom-post-types'),
                9 => __('Taxonomy scheduled', 'custom-post-types'),
                10 => __('Taxonomy draft updated', 'custom-post-types'),
            ];
            $messages[$this->getInfo('ui_prefix') . '_field'] = [
                1 => __('Field group updated', 'custom-post-types'),
                4 => __('Field group updated', 'custom-post-types'),
                6 => __('Field group published', 'custom-post-types'),
                7 => __('Field group saved', 'custom-post-types'),
                8 => __('Field group submitted', 'custom-post-types'),
                9 => __('Field group scheduled', 'custom-post-types'),
                10 => __('Field group draft updated', 'custom-post-types'),
            ];
            $messages[$this->getInfo('ui_prefix') . '_template'] = [
                1 => __('Template updated', 'custom-post-types'),
                4 => __('Template updated', 'custom-post-types'),
                6 => __('Template published', 'custom-post-types'),
                7 => __('Template saved', 'custom-post-types'),
                8 => __('Template submitted', 'custom-post-types'),
                9 => __('Template scheduled', 'custom-post-types'),
                10 => __('Template draft updated', 'custom-post-types'),
            ];
            $messages[$this->getInfo('ui_prefix') . '_page'] = [
                1 => __('Admin page updated', 'custom-post-types'),
                4 => __('Admin page updated', 'custom-post-types'),
                6 => __('Admin page published', 'custom-post-types'),
                7 => __('Admin page saved', 'custom-post-types'),
                8 => __('Admin page submitted', 'custom-post-types'),
                9 => __('Admin page scheduled', 'custom-post-types'),
                10 => __('Admin page draft updated', 'custom-post-types'),
            ];
            return $messages;
        });
    }

    /**
     * @return void
     */
    private function manipulateUiPostTypeTitle()
    {
        $no_title_ui_cpts = [$this->getInfo('ui_prefix'), $this->getInfo('ui_prefix') . '_tax'];

        // Override ui post title using singular label
        add_action('save_post', function ($post_id) use ($no_title_ui_cpts) {
            $post_type = get_post($post_id)->post_type;
            $post_status = get_post($post_id)->post_status;
            if (!in_array($post_type, $no_title_ui_cpts) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || $post_status == 'trash') return $post_id;
            $new_title = isset($_POST['meta-fields']['plural']) && !empty($_POST['meta-fields']['plural']) ? $_POST['meta-fields']['plural'] : 'CPT_' . $post_id;
            global $wpdb;
            $wpdb->update($wpdb->posts, ['post_title' => $new_title], ['ID' => $post_id]);
            return $post_id;
        });

        // Show on ui post types title
        add_action('edit_form_after_title', function () use ($no_title_ui_cpts) {
            $screen = get_current_screen();
            $post = isset($_GET['post']) && get_post($_GET['post']) ? get_post($_GET['post']) : false;
            if (!in_array($screen->post_type, $no_title_ui_cpts) || !in_array($screen->id, $no_title_ui_cpts) || !$post) return;
            printf('<h1 style="padding: 0;">%s</h1>', $post->post_title);
        });
    }

    /**
     * @return void
     */
    private function registerUiPages()
    {
        // Remove new post type menu
        add_action('admin_menu', function () {
            remove_submenu_page('edit.php?post_type=' . $this->getInfo('ui_prefix'), 'post-new.php?post_type=' . $this->getInfo('ui_prefix'));
        });

        // Add settings page
        add_filter($this->getHookName('register_admin_pages'), function ($args) {

            if (!$this->isProVersionActive()) {
                $args[] = [
                    'id' => 'manage_template',
                    'parent' => 'edit.php?post_type=' . $this->getInfo('ui_prefix'),
                    'order' => null,
                    'menu_icon' => null,
                    'title' => __('Templates', 'custom-post-types'),
                    'content' => $this->getProBanner(),
                    'admin_only' => true
                ];
                $args[] = [
                    'id' => 'manage_admin_pages',
                    'parent' => 'edit.php?post_type=' . $this->getInfo('ui_prefix'),
                    'order' => null,
                    'menu_icon' => null,
                    'title' => __('Admin pages', 'custom-post-types'),
                    'content' => $this->getProBanner(),
                    'admin_only' => true
                ];
                $args[] = [
                    'id' => 'manage_admin_notices',
                    'parent' => 'edit.php?post_type=' . $this->getInfo('ui_prefix'),
                    'order' => null,
                    'menu_icon' => null,
                    'title' => __('Admin notices', 'custom-post-types'),
                    'content' => $this->getProBanner(),
                    'admin_only' => true
                ];
            }

            ob_start();
            require_once($this->getInfo('path') . 'parts/pages/tools.php');
            $template = ob_get_clean();

            $args[] = [
                'id' => 'tools',
                'parent' => 'edit.php?post_type=' . $this->getInfo('ui_prefix'),
                'order' => null,
                'menu_icon' => null,
                'title' => __('Tools & Infos', 'custom-post-types'),
                'content' => $template,
                'admin_only' => true
            ];
            return $args;
        });
    }

    /**
     * @return void
     */
    private function registerUiFields()
    {
        // Register ui fields
        add_filter($this->getHookName('register_fields'), function ($fields) {
            $fields[] = $this->getFields()->getPostTypeFields();
            $fields[] = $this->getFields()->getTaxonomyFields();
            $fields[] = $this->getFields()->getNewFieldGroupFields();
            return $fields;
        });
    }

    /**
     * @return void
     */
    private function registerWelcomeNotices()
    {
        // Register welcome notices
        add_filter($this->getHookName('register_notices'), function ($args) {
            $buttons = [
                [
                    'link' => $this->getInfo('plugin_review_url'),
                    'label' => __('Write a Review', 'custom-post-types'),
                    'target' => '_blank',
                    'cta' => true
                ],
                [
                    'link' => $this->getInfo('plugin_donate_url'),
                    'label' => __('Make a Donation', 'custom-post-types'),
                    'target' => '_blank'
                ]
            ];
            if (!$this->isProVersionActive()) {
                $buttons[] = [
                    'link' => $this->getInfo('plugin_url'),
                    'label' => __('Get PRO version', 'custom-post-types'),
                    'target' => '_blank'
                ];
            }

            // After installation notice
            $welcomeNotice = [
                'id' => 'welcome_notice_400',
                'title' => $this->getNoticesTitle(),
                'message' => __('Thanks for using this plugin! Do you want to help us grow to add new features?', 'custom-post-types') . '<br><br>' . sprintf(__('The new version %s introduces a lot of new features and improves the core of the plugin.<br>For any problems you can download the previous version %s from the official page of the plugin from WordPress.org (Advanced View > Previous version).', 'custom-post-types'), '<u>' . $this->getInfo('version') . '</u>', '<u>3.1.1</u>'),
                'type' => 'success',
                'dismissible' => true,
                'buttons' => $buttons,
            ];

            if (time() < 1688169599) { // 30-06-2023 23:59:59
                $welcomeNotice['message'] = $welcomeNotice['message'] . '<br><br>' . sprintf('Use the coupon <strong><u>%s</u></strong> and get the PRO version with special discount until %s.', 'WELCOME-CPT-4', '30/06/2023');
            }

            $args[] = $welcomeNotice;

            $installationTime = get_option($this->getOptionName('installation_time'), null);
            if ($installationTime && strtotime("+7 day", $installationTime) < time()) {
                // After 7 days notice
                $args[] = [
                    'id' => 'welcome_notice_400_1',
                    'title' => $this->getNoticesTitle(),
                    'message' => __('Wow! More than 7 days of using this amazing plugin. Your support is really important.', 'custom-post-types'),
                    'type' => 'success',
                    'dismissible' => true,
                    'buttons' => $buttons,
                ];
            }

            return $args;
        });
    }

    /**
     * @return void
     */
    private function enqueueUiAssets()
    {
        // Enqueue ui assets
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style($this->getInfo('ui_prefix'), $this->getInfo('url') . 'assets/css/backend.css');
            if ($this->loadJs()) {
                wp_enqueue_media();
                wp_enqueue_editor();
                wp_enqueue_script($this->getInfo('options_prefix'), $this->getInfo('url') . 'assets/js/backend.js', ['jquery', 'wp-i18n', 'wp-util', 'wp-hooks', 'wp-editor'], null, true);
                wp_localize_script($this->getInfo('options_prefix'), 'cpt', $this->getJsVariables());
                wp_set_script_translations($this->getInfo('options_prefix'), 'custom-post-types');
            }
        });
    }

    /**
     * @return void
     */
    private function initPluginUi()
    {
        add_filter('plugin_action_links', function ($links, $file) {
            if ($file == 'custom-post-types/custom-post-types.php') {
                $links[] = sprintf(
                    '<a href="%1$s" target="_blank" aria-label="%2$s"> %2$s </a>',
                    $this->getInfo('plugin_support_url'),
                    __('Support', 'custom-post-types')
                );
                if (!$this->isProVersionActive()) {
                    $links[] = sprintf(
                        '<a href="%1$s" target="_blank" aria-label="%2$s" style="font-weight: bold;"> %2$s </a>',
                        $this->getInfo('plugin_url'),
                        __('Get PRO', 'custom-post-types')
                    );
                }
            }
            return $links;
        }, PHP_INT_MAX, 2);
    }

    /**
     * @return void
     */
    private function registerShortcodes()
    {
        // Shortcodes
        add_action('wp', function () {
            if (!is_admin() && !$this->isRest()) {
                global $post;
                add_shortcode('cpt-field', function ($atts) {
                    $a = shortcode_atts([
                        'key' => false,
                        'post-id' => false
                    ], $atts);
                    $errors = false;
                    if (!$a['key']) {
                        $errors[] = __('Missing field "key".', 'custom-post-types');
                    }
                    if ($errors) {
                        return current_user_can('edit_posts') ? "<pre>" . implode("</pre><pre>", $errors) . "</pre>" : '';
                    }
                    return $this->getPostField($a['key'], $a['post-id']);
                });
                add_shortcode('cpt-terms', function ($atts) use ($post) {
                    $a = shortcode_atts([
                        'key' => false,
                        'post-id' => false
                    ], $atts);
                    $errors = false;
                    if (!$a['key']) {
                        $errors[] = __('Missing field "key".', 'custom-post-types');
                    }
                    if ($errors) {
                        return current_user_can('edit_posts') ? "<pre>" . implode("</pre><pre>", $errors) . "</pre>" : '';
                    }
                    $post = $a['post-id'] && get_post($a['post-id']) ? get_post($a['post-id']) : $post;
                    $get_terms = get_the_terms($post->ID, $a['key']);
                    $terms = [];
                    foreach ($get_terms as $term) {
                        $terms[] = sprintf('<a href="%1$s" title="%2$s" aria-title="%2$s">%2$s</a>', get_term_link($term->term_id), $term->name);
                    }
                    return implode(', ', $terms);
                });
                add_shortcode('cpt-term-field', function ($atts) {
                    $a = shortcode_atts([
                        'key' => false,
                        'term-id' => false
                    ], $atts);
                    $errors = false;
                    if (!$a['key']) {
                        $errors[] = __('Missing field "key".', 'custom-post-types');
                    }
                    if (!$a['term-id']) {
                        $errors[] = __('Missing field "term-id".', 'custom-post-types');
                    }
                    if ($errors) {
                        return current_user_can('edit_posts') ? "<pre>" . implode("</pre><pre>", $errors) . "</pre>" : '';
                    }
                    return $this->getTermField($a['key'], $a['term-id']);
                });
                add_shortcode('cpt-option-field', function ($atts) {
                    $a = shortcode_atts([
                        'key' => false,
                        'option-id' => false
                    ], $atts);
                    $errors = false;
                    if (!$a['key']) {
                        $errors[] = __('Missing field "key".', 'custom-post-types');
                    }
                    if (!$a['option-id']) {
                        $errors[] = __('Missing field "option-id".', 'custom-post-types');
                    }
                    if ($errors) {
                        return current_user_can('edit_posts') ? "<pre>" . implode("</pre><pre>", $errors) . "</pre>" : '';
                    }
                    return $this->getOptionField($a['key'], $a['option-id']);
                });
            }
        });
    }

    /**
     * @return void
     */
    private function ajaxAction()
    {
        $this->getNotices()->ajaxAction();
        $this->getTemplates()->ajaxAction();

        $ajaxActions = (array)apply_filters($this->getHookName('register_ajax_actions'), []);
        foreach ($ajaxActions as $action => $args) {
            add_action('wp_ajax_' . $action, function () use ($args) {
                $nonce = !empty($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], $this->getInfo('nonce_key')) ? true : false;
                if (!$nonce) {
                    wp_send_json_error();
                }
                foreach ($args['requiredParams'] as $param) {
                    if (empty($_REQUEST[$param])) {
                        wp_send_json_error();
                    }
                }
                if (empty($args['callback']) || !is_callable($args['callback'])) {
                    wp_send_json_error();
                }
                $result = $args['callback']($_REQUEST);
                wp_send_json_success($result);
            });
        }
    }

    /**
     * @return void
     */
    private function applyUpdates()
    {
        $installedVersion = get_option($this->getOptionName('version'), null);
        $currentVersion = $this->getInfo('version');

        if (version_compare($installedVersion, $currentVersion, '=')) {
            return;
        }

        if (version_compare($installedVersion, $currentVersion, '<')) {
            // Apply updates
        }

        update_option($this->getOptionName('version'), $currentVersion);
        update_option($this->getOptionName('installation_time'), time());

        if(!empty($installedVersion)){
            $request_url = add_query_arg(
                ['id' => 92, 'action' => 'updated', 'domain' => md5(get_home_url()), 'v' => $currentVersion],
                'https://totalpress.org/wp-json/totalpress/v1/plugin-growth'
            );
            wp_remote_get($request_url);
        }
    }

    private function pluginActions()
    {
        $currentVersion = $this->getInfo('version');
        register_activation_hook(__FILE__, function () use ($currentVersion) {
            $request_url = add_query_arg(
                ['id' => 92, 'action' => 'activate', 'domain' => md5(get_home_url()), 'v' => $currentVersion],
                'https://totalpress.org/wp-json/totalpress/v1/plugin-growth'
            );
            wp_remote_get($request_url);
        });
        register_deactivation_hook(__FILE__, function () use ($currentVersion) {
            $request_url = add_query_arg(
                ['id' => 92, 'action' => 'deactivate', 'domain' => md5(get_home_url()), 'v' => $currentVersion],
                'https://totalpress.org/wp-json/totalpress/v1/plugin-growth'
            );
            wp_remote_get($request_url);
        });
    }

    /**
     * @return void
     */
    private function initRegisteredContents()
    {
        // Init registered content
        add_action('init', function () {
            $this->getPostTypes()->initRegisteredPostTypes();
            $this->getTaxonomies()->initRegisteredTaxonomies();
            $this->getFields()->initRegisteredGroups();
            $this->getAdminPages()->initRegisteredPages();
            $this->getNotices()->initRegisteredNotices();
        });
    }

    /**
     * @return bool
     */
    public function loadJs()
    {
        $currentScreen = get_current_screen();
        if (
            (!empty($currentScreen->id) &&
                (
                    in_array($currentScreen->id, $this->getFields()->screensWithFields) ||
                    (
                        explode('_page_', $currentScreen->id) &&
                        !empty(explode('_page_', $currentScreen->id)[1]) &&
                        in_array('_page_' . explode('_page_', $currentScreen->id)[1], $this->getFields()->screensWithFields)
                    )
                )
            ) ||
            $this->getNotices()->hasNotices
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $value
     * @param $key
     * @param $type
     * @param $content_type
     * @param $content_id
     * @return mixed
     */
    private function applyFieldGetFilters($value, $key, $type, $content_type, $content_id)
    {
        $output = $value;
        $type_get_callback = $this->getFields()->getAvailableFieldGetCallback($type);
        if ($type_get_callback && !has_filter($this->getHookName("get_field_type_" . $type))) {
            add_filter($this->getHookName("get_field_type_" . $type), $type_get_callback);
        }
        $output = apply_filters($this->getHookName("get_field_type_" . $type), $output, $value, $content_type, $content_id);
        $output = apply_filters($this->getHookName("get_field_" . $key), $output, $value, $content_type, $content_id);
        return $output;
    }

    /**
     * @param $key
     * @param $post_id
     * @return string
     */
    private function getPostField($key, $post_id = false)
    {
        global $post;
        $post = $post_id && get_post($post_id) ? get_post($post_id) : $post;
        $core_fields = [
            'title' => get_the_title($post->ID),
            'content' => get_the_content($post->ID),
            'excerpt' => get_the_excerpt($post->ID),
            'thumbnail' => get_the_post_thumbnail($post->ID, 'full'),
            'author' => sprintf('<a href="%1$s" title="%2$s" aria-title="%2$s">%2$s</a>', get_author_posts_url(get_the_author_meta('ID')), get_the_author()),
            'written_date' => get_the_date(get_option('date_format', "d/m/Y"), $post->ID),
            'modified_date' => get_the_modified_date(get_option('date_format', "d/m/Y"), $post->ID),
        ];
        $value = isset($core_fields[$key]) ? $core_fields[$key] : get_post_meta($post->ID, $key, true);
        $post_type_fields = $this->getFieldsByPostType($post->post_type);
        $type = isset($post_type_fields[$key]['type']) ? $post_type_fields[$key]['type'] : $key;
        $output = $this->applyFieldGetFilters($value, $key, $type, $post->post_type, $post_id);
        $output = is_array($output) ? (current_user_can('edit_posts') ? '<pre>' . print_r($output, true) . '</pre>' : '') : $output;
        return $output;
    }

    /**
     * @param $key
     * @param $term_id
     * @return string
     */
    private function getTermField($key, $term_id = false)
    {
        $term = $term_id && get_term($term_id) ? get_term($term_id) : false;
        if (!$term) {
            return '';
        }
        $core_fields = [
            'name' => $term->name,
            'description' => $term->description
        ];
        $value = isset($core_fields[$key]) ? $core_fields[$key] : get_term_meta($term->term_id, $key, true);
        $taxonomy_fields = $this->getFieldsByTaxonomy($term->taxonomy);
        $type = isset($taxonomy_fields[$key]['type']) ? $taxonomy_fields[$key]['type'] : $key;
        $output = $this->applyFieldGetFilters($value, $key, $type, $term->taxonomy, $term_id);
        $output = is_array($output) ? (current_user_can('edit_posts') ? '<pre>' . print_r($output, true) . '</pre>' : '') : $output;
        return $output;
    }

    /**
     * @param $key
     * @param $option_id
     * @return string
     */
    private function getOptionField($key, $option_id = false)
    {
        $option = $option_id;
        if (!$option) {
            return '';
        }
        $value = get_option("$option-$key");
        $option_fields = $this->getFieldsByOption($option);
        $type = isset($option_fields[$key]['type']) ? $option_fields[$key]['type'] : $key;
        $output = $this->applyFieldGetFilters($value, $key, $type, 'option', $option);
        $output = is_array($output) ? (current_user_can('edit_posts') ? '<pre>' . print_r($output, true) . '</pre>' : '') : $output;
        return $output;
    }
}

if (!in_array('custom-post-types-pro/custom-post-types-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    new Core();
}