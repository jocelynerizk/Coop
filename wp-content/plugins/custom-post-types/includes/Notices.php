<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Notices extends Component
{
    /**
     * @var bool
     */
    public $hasNotices = false;

    /**
     * @return void
     */
    public function ajaxAction()
    {
        // Notice ajax dismiss
        add_filter($this->getHookName('register_ajax_actions'), function ($actions) {
            $actions['cpt-dismiss-notice'] = [
                'requiredParams' => ['key'],
                'callback' => function ($params) {
                    $notice = $params['key'];
                    $duration = $params['duration'];
                    $this->dismissNotice($notice, ($duration == 'lifetime' ? -1 : intval($duration)));
                    return 'OK';
                },
            ];
            return $actions;
        });
    }

    /**
     * @return array
     */
    private function getDismissedNotices()
    {
        $dismissed_notices = get_option($this->getOptionName('dismissed_notices'), []);
        return is_array($dismissed_notices) ? $dismissed_notices : [];
    }

    /**
     * @param $id
     * @param $days
     * @return void
     */
    private function dismissNotice($id, $days = 2)
    {
        $dismissed_notices = $this->getDismissedNotices();
        if ($days < 0) $days = 36500;
        $dismissed_notices[$id] = strtotime("+$days day", time());
        update_option($this->getOptionName('dismissed_notices'), $dismissed_notices);
    }

    /**
     * @param $id
     * @return bool
     */
    private function isDismissed($id)
    {
        $dismissed_notices = $this->getDismissedNotices();
        return !empty($dismissed_notices[$id]) && time() < intval($dismissed_notices[$id]);
    }

    /**
     * @param $key
     * @param $title
     * @param $message
     * @param $type
     * @param $dismissible
     * @param $buttons
     * @return void
     */
    private function initNotice($key = false, $title = false, $message = '', $type = 'warning', $dismissible = false, $buttons = [])
    {
        if (!$key) return;
        $key = sanitize_title($key);
        if ($this->isDismissed($key)) return;
        $type = in_array($type, ['error', 'warning', 'success', 'info']) ? $type : 'info';
        $class = "notice notice-$type cpt-notice" . ($dismissible ? ' is-dismissible' : '');
        $notice_buttons = [];
        if(is_array($buttons)) {
            foreach ($buttons as $button) {
                $is_cta = !empty($button['cta']) ? $button['cta'] : false;
                $notice_buttons[] = sprintf(
                    '<a href="%1$s"%2$s target="%3$s" title="%4$s" aria-label="%4$s">%4$s%5$s</a>',
                    !empty($button['link']) ? $button['link'] : '',
                    $is_cta ? ' class="button button-secondary"' : '',
                    !empty($button['target']) ? $button['target'] : '_self',
                    !empty($button['label']) ? $button['label'] : '',
                    !empty($button['target']) && $button['target'] == '_blank' && !$is_cta ? '<span class="dashicons dashicons-external"></span>' : ''
                );
            }
        }
        if ($dismissible) {
            $button_label = $dismissible === true ? __('Dismiss notice', 'custom-post-types') : sprintf(__('Dismiss notice for %s days', 'custom-post-types'), (int)$dismissible);
            $notice_buttons[] = sprintf(
                '<a href="#" class="cpt-dismiss-notice" data-notice="%1$s" data-duration="%2$s" title="%3$s" aria-label="%3$s">%3$s</a>',
                $key,
                ($dismissible === true ? 'lifetime' : $dismissible),
                $button_label
            );
        }

        $title = !empty($title) ? sprintf('<p class="notice-title">%s</p>', $title) : '';

        add_action('admin_notices', function () use ($class, $message, $notice_buttons, $title) {
            printf(
                '<div class="%s">%s<div class="message">%s</div>%s</div>',
                $class,
                $title,
                $message,
                !empty($notice_buttons) ? '<p class="actions">' . implode('', $notice_buttons) . '</p>' : ''
            );
        });

        $this->hasNotices = true;
    }

    /**
     * @return array
     */
    private function getRegisteredNotices(){
        $registeredNotices = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . '_notice',
            'post_status' => 'publish'
        ]);

        $noticesByUi = [];

        foreach ($registeredNotices as $notice) {
            $noticeId = !empty(get_post_meta($notice->ID, 'id', true)) ? sanitize_title(get_post_meta($notice->ID, 'id', true)) : sanitize_title($notice->post_title);
            $noticeType = !empty(get_post_meta($notice->ID, 'type', true)) ? get_post_meta($notice->ID, 'type', true) : 'info';
            $noticeDismissable = !empty(get_post_meta($notice->ID, 'dismissible', true)) ? get_post_meta($notice->ID, 'dismissible', true) : false;
            if($noticeDismissable < 0){
                $noticeDismissable = true;
            }
            $noticeButtons = !empty(get_post_meta($notice->ID, 'buttons', true)) ? get_post_meta($notice->ID, 'buttons', true) : false;
            $noticesByUi[] = [
                'id' => $noticeId,
                'title' => $notice->post_title,
                'message' => wpautop($notice->post_content),
                'type' => $noticeType,
                'dismissible' => $noticeDismissable,
                'buttons' => $noticeButtons
            ];
        }

        unset($registeredPages);

        return (array)apply_filters($this->getHookName('register_notices'), $noticesByUi);
    }

    /**
     * @return void
     */
    public function initRegisteredNotices(){
        $notices = $this->getRegisteredNotices();

        foreach($notices as $notice){
            if(
                !isset($notice['id']) ||
                !isset($notice['message'])
            ){
                $this->initNotice(
                    'notice_args_error',
                    $this->getNoticesTitle(),
                    __('Notice registration was not successful ("id" and "message" args are required).', 'custom-post-types'),
                    'error'
                );
                continue;
            }

            $title = !empty($notice['title']) ? $notice['title'] : false;
            $type = !empty($notice['type']) ? $notice['type'] : false;
            $dismissible = !empty($notice['dismissible']) ? $notice['dismissible'] : false;
            $buttons = !empty($notice['buttons']) ? $notice['buttons'] : false;

            $this->initNotice($notice['id'], $title, $notice['message'], $type, $dismissible, $buttons);
        }
    }
}