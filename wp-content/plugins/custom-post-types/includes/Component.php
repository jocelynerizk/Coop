<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Component
{
    /**
     * @param $name
     * @return mixed|null
     */
    public function getInfo($name = '')
    {
        return defined('CPT_INFOS') && !empty(CPT_INFOS[$name]) ? CPT_INFOS[$name] : null;
    }

    /**
     * @param $name
     * @return string
     */
    public function getHookName($name = '')
    {
        return $this->getInfo('hook_prefix') . $name;
    }

    /**
     * @param $name
     * @return string
     */
    public function getOptionName($name = '')
    {
        return $this->getInfo('options_prefix') . $name;
    }

    /**
     * @param $postTypes
     * @return void
     */
    public function flushRewriteRules($postTypes = [])
    {
        $ids = [];
        foreach ($postTypes as $postType) {
            if (!empty($postType['id'])) $ids[] = $postType['id'];
        }
        if (!empty($ids)) {
            $registeredIds = get_option($this->getOptionName('registered_cpt_ids'), []);
            $idsAlreadyRegistered = !array_diff($ids, $registeredIds);
            if (empty($registeredIds) || !$idsAlreadyRegistered) {
                $newRegisteredIds = array_merge($registeredIds, $ids);
                update_option($this->getOptionName('registered_cpt_ids'), array_unique($newRegisteredIds));
                flush_rewrite_rules();
            }
        }
    }

    /**
     * @return array
     */
    public function getPostTypesOptions()
    {
        $registered_post_types = get_post_types(['_builtin' => false], 'objects');
        $exclude = [
            $this->getInfo('ui_prefix'),
            $this->getInfo('ui_prefix') . "_tax",
            $this->getInfo('ui_prefix') . "_field",
            $this->getInfo('ui_prefix') . "_template",
            $this->getInfo('ui_prefix') . "_page"
        ];
        $post_types = [
            'post' => __('Posts'),
            'page' => __('Pages'),
        ];
        foreach ($registered_post_types as $post_type) {
            if (in_array($post_type->name, $exclude)) continue;
            $post_types[$post_type->name] = $post_type->label;
        }
        unset($registered_post_types);
        return $post_types;
    }

    /**
     * @return array
     */
    public function getTaxonomiesOptions()
    {
        $registered_taxonomies = get_taxonomies(['_builtin' => false, 'show_ui' => true], 'objects');
        $taxonomies = [
            'category' => __('Categories'),
            'post_tag' => __('Tags'),
        ];
        foreach ($registered_taxonomies as $taxonomy) {
            $taxonomies[$taxonomy->name] = $taxonomy->label;
        }
        unset($registered_taxonomies);
        return $taxonomies;
    }

    /**
     * @return array[]
     */
    public function getCoreSettingsPagesOptions()
    {
        return [
            'general' => ['title' => __('Settings') . ' > ' . _x('General', 'settings screen'), 'url' => 'options-general.php'],
            'writing' => ['title' => __('Settings') . ' > ' . __('Writing'), 'url' => 'options-writing.php'],
            'reading' => ['title' => __('Settings') . ' > ' . __('Reading'), 'url' => 'options-reading.php'],
            'discussion' => ['title' => __('Settings') . ' > ' . __('Discussion'), 'url' => 'options-discussion.php'],
            'media' => ['title' => __('Settings') . ' > ' . __('Media'), 'url' => 'options-media.php']
        ];
    }

    /**
     * @return array[]
     */
    public function getSettingsPagesOptions()
    {
        $pages = $this->getCoreSettingsPagesOptions();
        $registeredPages = $this->getRegisteredPages();
        foreach ($registeredPages as $page) {
            $pages[$page['id']] = ['title' => $page['title']];
        }
        return $pages;
    }

    /**
     * @return array
     */
    public function getRegisteredPages()
    {
        $registeredPages = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . '_page',
            'post_status' => 'publish'
        ]);

        $pagesByUi = [];

        foreach ($registeredPages as $page) {
            $pageId = !empty(get_post_meta($page->ID, 'id', true)) ? sanitize_title(get_post_meta($page->ID, 'id', true)) : sanitize_title($page->post_title);
            $pageParent = !empty(get_post_meta($page->ID, 'parent', true)) ? get_post_meta($page->ID, 'parent', true) : null;
            $pageOrder = is_numeric(get_post_meta($page->ID, 'order', true)) ? get_post_meta($page->ID, 'order', true) : null;
            $pageIcon = !empty(get_post_meta($page->ID, 'menu_icon', true)) ? get_post_meta($page->ID, 'menu_icon', true) : '';
            $pageAdminOnly = get_post_meta($page->ID, 'admin_only', true) == 'true' ? true : false;
            if ($pageParent && stripos($pageParent, '/') !== false) {
                $pageParent = explode('/', $pageParent);
                $pageParent = end($pageParent);
            }
            $pagesByUi[] = [
                'id' => $pageId,
                'parent' => $pageParent,
                'order' => $pageOrder,
                'menu_icon' => $pageIcon,
                'title' => $page->post_title,
                'content' => $page->post_content,
                'admin_only' => $pageAdminOnly
            ];
        }

        unset($registeredPages);

        return $pagesByUi;
    }

    /**
     * @return array
     */
    public function getContentsOptions()
    {
        $options = [];

        $postTypes = $this->getPostTypesOptions();
        foreach ($postTypes as $id => $label) {
            $options['-- ' . __('Post types', 'custom-post-types') . ' --']['cpt/' . $id] = $label;
        }
        unset($postTypes);

        $taxonomies = $this->getTaxonomiesOptions();
        foreach ($taxonomies as $id => $label) {
            $options['-- ' . __('Taxonomies', 'custom-post-types') . ' --']['tax/' . $id] = $label;
        }
        unset($taxonomies);

        $settingsPages = $this->getSettingsPagesOptions();
        foreach ($settingsPages as $id => $args) {
            $options['-- ' . __('Admin pages', 'custom-post-types') . ' --']['options/' . $id] = $args['title'];
        }
        unset($settingsPages);

        $options['-- ' . __('Extra', 'custom-post-types') . ' --'] = [
            'extra/users' => __('Users'),
        ];

        return $options;
    }

    /**
     * @return array
     */
    public function getRolesOptions()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $registered_roles = get_editable_roles();
        $roles = [];
        foreach ($registered_roles as $role => $args) {
            $roles[$role] = $args['name'];
        }
        unset($registered_roles);
        return $roles;
    }

    /**
     * @param $post_id
     * @param $string
     * @return mixed|string
     */
    public function getPostTitleWithParents($post_id = 0, $string = '')
    {
        $post = get_post($post_id);
        if ($post_id == 0 || !$post) return $string;
        $string = empty($string) ? $post->post_title : $string;
        if ($post->post_parent == 0) return $string;
        $string = get_the_title($post->post_parent) . ' > ' . $string;
        return $this->getPostTitleWithParents($post->post_parent, $string);
    }

    /**
     * @param $term_id
     * @param $string
     * @return mixed|string
     */
    public function getTermTitleWithParents($term_id = 0, $string = '')
    {
        $term = get_term($term_id);
        if ($term_id == 0 || !$term) return $string;
        $string = empty($string) ? $term->name : $string;
        if ($term->parent == 0) return $string;
        $string = get_term($term->parent)->name . ' > ' . $string;
        return $this->getTermTitleWithParents($term->parent, $string);
    }

    /**
     * @return bool
     */
    public function isRest()
    {
        $prefix = rest_get_url_prefix();
        if (
            defined('REST_REQUEST') && REST_REQUEST
            || isset($_GET['rest_route'])
            && strpos(trim($_GET['rest_route'], '\\/'), $prefix, 0) === 0
        ) {
            return true;
        }
        global $wp_rewrite;
        if ($wp_rewrite === null) {
            $wp_rewrite = new WP_Rewrite();
        }
        $rest_url = wp_parse_url(trailingslashit(rest_url()));
        $current_url = wp_parse_url(add_query_arg(array()));
        return strpos($current_url['path'], $rest_url['path'], 0) === 0;
    }

    /**
     * @param $post_type
     * @return array
     */
    public function getFieldsByPostType($post_type = false)
    {
        if (!$post_type || !post_type_exists($post_type)) return [];
        $fields = [];
        if (post_type_supports($post_type, 'title')) $fields['title'] = ['label' => __('Post title', 'custom-post-types')];
        if (post_type_supports($post_type, 'editor')) $fields['content'] = ['label' => __('Post content', 'custom-post-types')];
        if (post_type_supports($post_type, 'excerpt')) $fields['excerpt'] = ['label' => __('Post excerpt', 'custom-post-types')];
        if (post_type_supports($post_type, 'thumbnail')) $fields['thumbnail'] = ['label' => __('Post image', 'custom-post-types')];
        if (post_type_supports($post_type, 'author')) $fields['author'] = ['label' => __('Post author', 'custom-post-types')];
        $fields['written_date'] = ['label' => __('Post date', 'custom-post-types')];
        $fields['modified_date'] = ['label' => __('Post modified date', 'custom-post-types')];
        $registered_fields = $this->getFieldsBySupports("cpt/$post_type");
        $fields = array_merge($fields, $registered_fields);
        return $fields;
    }

    /**
     * @param $taxonomy
     * @return array
     */
    public function getFieldsByTaxonomy($taxonomy = false)
    {
        if (!$taxonomy || !taxonomy_exists($taxonomy)) return [];
        $fields = [];
        $fields['name'] = ['label' => __('Term name', 'custom-post-types')];
        $fields['description'] = ['label' => __('Term description', 'custom-post-types')];
        $registered_fields = $this->getFieldsBySupports("tax/$taxonomy");
        $fields = array_merge($fields, $registered_fields);
        return $fields;
    }

    /**
     * @param $option
     * @return array
     */
    public function getFieldsByOption($option = false)
    {
        if (!$option) return [];
        $fields = $this->getFieldsBySupports("options/$option");
        return $fields;
    }

    /**
     * @param $support
     * @return array
     */
    public function getFieldsBySupports($support)
    {
        $created_fields_groups = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . "_field",
            'meta_query' => [[
                'key' => 'supports',
                'value' => $support,
                'compare' => 'LIKE'
            ]]
        ]);
        $fields = [];
        foreach ($created_fields_groups as $created_fields_group) {
            $fields_group_fields = get_post_meta($created_fields_group->ID, 'fields', true);
            if (!empty($fields_group_fields)) {
                foreach ($fields_group_fields as $field) {
                    $fields[$field['key']] = [
                        'label' => $field['label'],
                        'type' => $field['type'],
                    ];
                }
            }
        }
        return $fields;
    }

    /**
     * @param $postType
     * @return array[]
     */
    public function getTemplateShortcodes($postType)
    {
        $postTypeFields = $this->getFieldsByPostType($postType);
        $postTypeTaxs = get_object_taxonomies($postType);

        $fieldShortcodes = [];
        foreach ($postTypeFields as $key => $field) {
            $fieldShortcodes[] = sprintf(
                '<input type="text" value="%1$s" title="%2$s" aria-label="%2$s" class="copy-shortcode" readonly>',
                htmlentities(sprintf('[cpt-field key="%s"]', $key)),
                __('Click to copy', 'custom-post-types')
            );
        }

        $taxShortcodes = [];
        foreach ($postTypeTaxs as $tax) {
            $taxShortcodes[] = sprintf(
                '<input type="text" value="%1$s" title="%2$s" aria-label="%2$s" class="copy-shortcode" readonly>',
                htmlentities(sprintf('[cpt-terms key="%s"]', $tax)),
                __('Click to copy', 'custom-post-types')
            );
        }

        return [
            'fields' => !empty($fieldShortcodes) ? $fieldShortcodes : ["<span>" . __("No shortcodes available", "custom-post-types") . "</span>"],
            'taxonomies' => !empty($taxShortcodes) ? $taxShortcodes : ["<span>" . __("No shortcodes available", "custom-post-types") . "</span>"]
        ];
    }

    /**
     * @return bool
     */
    public function isProVersionActive()
    {
        return in_array('custom-post-types-pro/custom-post-types-pro.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    /**
     * @return string
     */
    public function getProBanner()
    {
        $output = '<p><strong>' . __('This feature is included in the <u>PRO version</u> only.', 'custom-post-types') . '</strong></p>';
        $output .= sprintf(
            '<p><a href="%1$s" class="button button-primary button-hero" target="_blank" title="%2$s" aria-label="%2$s">%2$s</a></p>',
            $this->getInfo('plugin_url'),
            __('Get PRO version', 'custom-post-types')
        );
        return '<div class="cpt-pro-banner">' . $output . '</div>';
    }

    /**
     * @return mixed
     */
    public function getNoticesTitle()
    {
        return __('<strong>Custom post types</strong> notice:', 'custom-post-types');
    }

    /**
     * @param $args
     * @param $type
     * @return array
     */
    public function getRegistrationErrorNoticeInfo($args = [], $type = 'post')
    {
        $idParts = [];
        foreach ($args as $arg) {
            $idParts[] = !empty($arg) ? (is_array($arg) ? count($arg) : $arg) : 'none';
        }
        return [
            'id' => $type . '_args_error_' . implode('_', $idParts),
            'details' => sprintf(
                '<pre class="error-code"><a href="#" title="%1$s" aria-label="%1$s">%1$s</a><code>%2$s</code></pre>',
                __('See registration args', 'custom-post-types'),
                json_encode($args, JSON_PRETTY_PRINT)
            )
        ];
    }

    /**
     * @return array
     */
    public function getJsVariables()
    {
        return [
            'js_fields_events_hook' => 'cpt-fields-events',
            'js_fields_events_namespace' => 'custom-post-types',
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce($this->getInfo('nonce_key')),
        ];
    }
}