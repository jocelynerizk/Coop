<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Templates extends Component
{
    /**
     * @return void
     */
    public function ajaxAction()
    {
        // Template ajax get shortcodes
        add_filter($this->getHookName('register_ajax_actions'), function ($actions) {
            $actions['cpt-get-template-shortcodes'] = [
                'requiredParams' => ['post-type'],
                'callback' => function ($params) {
                    $result = $this->getTemplateShortcodes($params['post-type']);
                    if (empty($result)) {
                        wp_send_json_error();
                    }
                    return $result;
                },
            ];
            return $actions;
        });
    }

    /**
     * @return array
     */
    private function getRegisteredTemplates()
    {
        $registeredTemplates = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . '_template',
            'post_status' => 'publish'
        ]);

        $templatesByUi = [];

        foreach ($registeredTemplates as $template) {
            $templateSupport = get_post_meta($template->ID, 'supports', true);
            if (empty($templateSupport) || !post_type_exists($templateSupport)) continue;

            $templatesByUi[$templateSupport] = [
                'content' => $template->post_content
            ];
        }

        unset($registeredTemplates);

        return (array)apply_filters($this->getHookName('register_templates'), $templatesByUi);
    }

    /**
     * @return void
     */
    public function initRegisteredTemplates()
    {
        add_filter('the_content', [$this, 'filterTheContent'] , PHP_INT_MAX);
    }

    /**
     * @param $content
     * @return mixed
     */
    public function filterTheContent($content)
    {
        if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) return $content;
        global $post;
        $templates = $this->getRegisteredTemplates();
        if (empty($templates[$post->post_type])) return $content;
        remove_filter('the_content', [$this, 'filterTheContent'] , PHP_INT_MAX);
        return apply_filters('the_content', $templates[$post->post_type]['content']);
    }
}