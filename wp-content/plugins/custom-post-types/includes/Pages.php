<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Pages extends Component
{

    /**
     * @return void
     */
    public function initRegisteredPages()
    {
        add_action('admin_menu', function () {
            $pagesByUi = $this->getRegisteredPages();

            $pages = (array)apply_filters($this->getHookName('register_admin_pages'), $pagesByUi);

            if (empty($pages)) return;

            foreach ($pages as $page) {
                $id = !empty($page['id']) && is_string($page['id']) ? $page['id'] : false;
                $parent = !empty($page['parent']) && is_string($page['parent']) ? $page['parent'] : false;
                $order = !empty($page['order']) && is_numeric($page['order']) ? $page['order'] : null;
                $icon = !empty($page['menu_icon']) && is_string($page['menu_icon']) ? $page['menu_icon'] : '';
                $title = !empty($page['title']) && is_string($page['title']) ? $page['title'] : false;
                $content = !empty($page['content']) && is_string($page['content']) ? $page['content'] : false;
                $capability = !empty($page['admin_only']) ? 'administrator' : 'edit_posts';

                if (!$id || !$title) {
                    $errorInfo = $this->getRegistrationErrorNoticeInfo($page, 'page');

                    add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                        $args[] = [
                            'id' => $errorInfo['id'],
                            'title' => $this->getNoticesTitle(),
                            'message' => __('Admin page registration was not successful ("id" and "title" args are required).', 'custom-post-types') . $errorInfo['details'],
                            'type' => 'error',
                            'dismissible' => 3,
                            'buttons' => false,
                        ];
                        return $args;
                    });
                    continue;
                }

                $callback = function () use ($id, $title, $content) {
                    $this->pageCallback($id, $title, $content);
                };

                if ($parent) {
                    $registeredAdminPage = \add_submenu_page($parent, $title, $title, $capability, $id, $callback, $order);
                } else {
                    $registeredAdminPage = \add_menu_page($title, $title, $capability, $id, $callback, $icon, $order);
                }

                if (!$registeredAdminPage) {
                    $errorInfo = $this->getRegistrationErrorNoticeInfo($page, 'page');

                    add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                        $args[] = [
                            'id' => $errorInfo['id'] . '_core',
                            'title' => $this->getNoticesTitle(),
                            'message' => __('Admin page registration was not successful.', 'custom-post-types') . $errorInfo['details'],
                            'type' => 'error',
                            'dismissible' => 3,
                            'buttons' => false,
                        ];
                        return $args;
                    });
                }
            }

            unset($pages);
        });
    }

    /**
     * @param $id
     * @param $title
     * @param $content
     * @return void
     */
    public function pageCallback($id, $title, $content = false)
    { ?>
        <div class="wrap cpt-admin-page">
            <h1 class="cpt-admin-page-title"><?php echo $title; ?></h1>
            <?php
            if (!empty($content)) {
                printf('<div class="cpt-admin-page-content">%s</div>', $id == 'tools' ? $content : apply_filters('the_content', $content));
            }
            ob_start();
            do_settings_sections($id);
            $fields = ob_get_clean();
            if(!empty($fields)) { ?>
                <form method="post" action="options.php" novalidate="novalidate">
                    <?php settings_fields($id);?>
                    <?php echo $fields;?>
                    <?php submit_button();?>
                </form>
            <?php }?>
        </div>
    <?php }
}