<?php

namespace CustomPostTypesPlugin\Includes;

if (!defined('ABSPATH')) {
    die('Invalid request.');
}

class Fields extends Component
{
    /**
     * @var array
     */
    public $screensWithFields = [];

    /**
     * @var array
     */
    public $uiRegistrationArgsTitleField = [];

    /**
     * @var array
     */
    public $uiRegistrationLabelsTitleField = [];

    /**
     * @var array
     */
    public $uiRegistrationViewSwitchField = [];

    public function __construct()
    {
        $this->uiRegistrationArgsTitleField = [
            'key' => 'args_title',
            'label' => '',
            'info' => '',
            'required' => false,
            'type' => 'html',
            'extra' => [
                'content' => sprintf(
                    '<h2>%s</h2>',
                    __('Registration args', 'custom-post-types')
                )
            ],
            'wrap' => [
                'width' => '',
                'class' => 'advanced-field',
                'id' => '',
                'layout' => ''
            ]
        ];
        $this->uiRegistrationLabelsTitleField = [
            'key' => 'labels_title',
            'label' => '',
            'info' => '',
            'required' => false,
            'type' => 'html',
            'extra' => [
                'content' => sprintf(
                    '<h2>%s</h2>',
                    __('Registration label', 'custom-post-types')
                )
            ],
            'wrap' => [
                'width' => '',
                'class' => 'advanced-field',
                'id' => '',
                'layout' => ''
            ]
        ];
        $this->uiRegistrationViewSwitchField = [
            'key' => 'advanced_fields',
            'label' => '',
            'info' => '',
            'required' => false,
            'type' => 'html',
            'extra' => [
                'content' => sprintf(
                    '<button class="button button-primary"><span class="dashicons dashicons-insert"></span><span class="label">%s</span></button>',
                    __('Advanced view', 'custom-post-types')
                )
            ],
            'wrap' => [
                'width' => '',
                'class' => 'advanced-field-btn',
                'id' => '',
            ]
        ];
    }

    /**
     * @param $optionsString
     * @return array
     */
    private function getOptionsFromString($optionsString = '')
    {
        $rows = explode(PHP_EOL, $optionsString);
        $optionsArray = [];
        foreach ($rows as $row) {
            if (strpos($row, '|') !== false) {
                $optionsArray[trim(explode('|', $row)[0])] = trim(explode('|', $row)[1]);
            } else {
                $optionsArray[trim($row)] = trim($row);
            }
        }
        return $optionsArray;
    }

    /**
     * @param $field
     * @return array
     */
    private function sanitizeFieldArgs($field = [])
    {
        $field['required'] = !empty($field['required']) && $field['required'] == 'true' ? true : false;
        if (!empty($field['extra']['options']) && !is_array($field['extra']['options'])) {
            $field['extra']['options'] = $this->getOptionsFromString($field['extra']['options']);
        }
        foreach ($field as $key => $value) {
            if (substr($key, 0, 5) === "wrap_") {
                if (!empty($value)) {
                    $field['wrap'][str_replace("wrap_", "", $key)] = $value;
                }
                unset($field[$key]);
            }
        }
        return $field;
    }

    /**
     * @return array
     */
    private function getRegisteredGroups()
    {
        $registeredGroups = get_posts([
            'posts_per_page' => -1,
            'post_type' => $this->getInfo('ui_prefix') . '_field',
            'post_status' => 'publish'
        ]);

        $groupsByUi = [];

        foreach ($registeredGroups as $group) {
            $groupId = sanitize_title($group->post_title);
            $groupLabel = $group->post_title;
            $groupSupports = !empty(get_post_meta($group->ID, 'supports', true)) ? array_map(
                function ($support) {
                    $contentType = 'cpt';
                    $contentId = $support;
                    if (strpos($support, '/') !== false) {
                        $contentType = explode('/', $support)[0];
                        $contentId = explode('/', $support)[1];
                    }
                    return [
                        'type' => $contentType,
                        'id' => $contentId,
                    ];
                },
                get_post_meta($group->ID, 'supports', true)
            ) : [];
            $groupPosition = !empty(get_post_meta($group->ID, 'position', true)) ? get_post_meta($group->ID, 'position', true) : 'normal';
            $groupOrder = get_post_meta($group->ID, 'order', true);
            $groupAdminOnly = get_post_meta($group->ID, 'admin_only', true) == 'true' ? true : false;
            $groupFields = !empty(get_post_meta($group->ID, 'fields', true)) ? array_map(
                function ($field) {
                    return $this->sanitizeFieldArgs($field);
                },
                get_post_meta($group->ID, 'fields', true)
            ) : [];

            $groupsByUi[] = [
                'id' => $groupId,
                'label' => $groupLabel,
                'supports' => $groupSupports,
                'position' => $groupPosition,
                'order' => $groupOrder,
                'admin_only' => $groupAdminOnly,
                'fields' => $groupFields,
            ];
        }

        unset($registeredGroups);

        return (array)apply_filters($this->getHookName('register_fields'), $groupsByUi);
    }

    /**
     * @param $key
     * @param $parent
     * @return string|void
     */
    private function getFieldInputName($key = '', $parent = false)
    {
        if (empty($key)) return;
        return "meta-fields" . ($parent ? $parent : '') . '[' . $key . ']';
    }

    /**
     * @param $key
     * @param $parent
     * @return string|void
     */
    private function getFieldInputId($key = '', $parent = false)
    {
        if (empty($key)) return;
        $parent = $parent ? str_replace('][', '-', $parent) : '';
        $parent = str_replace('[', '-', $parent);
        $parent = str_replace(']', '', $parent);
        return "meta-fields" . $parent . '-' . $key;
    }

    /**
     * @param $fieldConfig
     * @return string|void
     */
    public function getFieldTemplate($fieldConfig = [])
    {
        $parent = !empty($fieldConfig['parent']) ? $fieldConfig['parent'] : false;
        $fieldId = $this->getFieldInputId($fieldConfig['key'], $parent);
        $fieldName = $this->getFieldInputName($fieldConfig['key'], $parent);
        $fieldTemplateCallback = $this->getAvailableFieldTemplateCallback($fieldConfig['type']);
        if (!$fieldTemplateCallback) {
            return;
        }
        ob_start();
        ?>
        <div class="cpt-field"<?php echo !empty($fieldConfig['wrap']['width']) ? ' style="width: ' . $fieldConfig['wrap']['width'] . '%"' : ''; ?>
             data-field-type="<?php echo $fieldConfig['type']; ?>">
            <div class="cpt-field-inner">
                <input type="hidden" name="<?php echo $fieldName; ?>" value="">
                <?php printf(
                    '<div class="cpt-field-wrap%s"%s><label for="%s">%s</label><div class="input">%s</div>%s</div>',
                    (!empty($fieldConfig['wrap']['layout']) ? ' ' . $fieldConfig['wrap']['layout'] : '') .
                    ($fieldConfig['required'] ? ' cpt-field-required' : '') .
                    (!empty($fieldConfig['wrap']['class']) ? ' ' . $fieldConfig['wrap']['class'] : ''),
                    !empty($fieldConfig['wrap']['id']) ? ' id="' . $fieldConfig['wrap']['id'] . '"' : '',
                    $fieldId,
                    $fieldConfig['label'],
                    $fieldTemplateCallback($fieldName, $fieldId, $fieldConfig),
                    !empty($fieldConfig['info']) ? '<div class="description"><p>' . $fieldConfig['info'] . '</p></div>' : ''
                ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @param $config
     * @param $getValueCallback
     * @return false|string
     */
    private function getFieldsSection($config = [], $getValueCallback = null)
    {
        $fields = !empty($config['fields']) ? $config['fields'] : [];
        ob_start();
        wp_nonce_field($this->getHookName('fields_nonce'), 'fields-nonce', true, true);
        ?>
        <div class="cpt-fields-section" data-id="<?php echo $config['id']; ?>">
            <?php
            foreach ($fields as $field) {
                $field['value'] = $getValueCallback($field['key']);
                $field = apply_filters($this->getHookName($field['type'] . '_field_args'), $field);
                echo $this->getFieldTemplate($field);
            }
            ?>
        </div>
        <?php
        $output = ob_get_clean();
        return $output;
    }

    /**
     * @param $fields
     * @param $saveValueCallback
     * @return void
     */
    private function saveMeta($fields = [], $saveValueCallback = null)
    {
        if (
            empty($_POST['fields-nonce']) ||
            !wp_verify_nonce($_POST['fields-nonce'], $this->getHookName('fields_nonce')) ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        ) {
            return;
        }
        $metaValues = isset($_POST['meta-fields']) ? $_POST['meta-fields'] : [];

        foreach ($fields as $field) {
            $metaKey = $field['key'];
            $fieldSanitizeCallback = $this->getAvailableFieldSanitizeCallback($field['type']);
            if(!isset($metaValues[$metaKey])){
                return;
            } elseif (!empty($metaValues[$metaKey])) {
                /*
                 * Sanitize using field registration callback
                 */
                $sanitizeValue = $fieldSanitizeCallback ? $fieldSanitizeCallback($metaValues[$metaKey]) : $metaValues[$metaKey];
            } else {
                $sanitizeValue = '';
            }
            if ($saveValueCallback) {
                $saveValueCallback($metaKey, $sanitizeValue);
            }
        }
    }

    /**
     * @param $taxonomy
     * @param $config
     * @return void
     */
    private function initTaxonomyFields($taxonomy = '', $config = [])
    {
        $config['fields'] = !empty($config['fields']) && is_array($config['fields']) ? $config['fields'] : [];

        add_action($taxonomy . '_add_form_fields', function ($term) use ($config) {
            echo $this->getFieldsSection($config, function ($key) use ($term) {
                return !empty($term->term_id) ? get_term_meta($term->term_id, $key, true) : null;
            });
        });
        add_action($taxonomy . '_edit_form', function ($term) use ($config) {
            echo $this->getFieldsSection($config, function ($key) use ($term) {
                return !empty($term->term_id) ? get_term_meta($term->term_id, $key, true) : null;
            });
        });

        $actions = [
            'edited_' . $taxonomy,
            'created_' . $taxonomy
        ];
        foreach ($actions as $action) {
            add_action($action, function ($termId) use ($config, $taxonomy) {
                $this->saveMeta($config['fields'], function ($key, $value) use ($termId, $taxonomy) {
                    $value = $this->getSanitizedValue($taxonomy, $key, $value);
                    return update_term_meta($termId, $key, $value);
                });
            });
        }
    }

    /**
     * @param $postType
     * @param $config
     * @return void
     */
    private function initPostTypeFields($postType = '', $config = [])
    {
        add_action('add_meta_boxes', function ($posttype) use ($postType, &$config) {
            if ($posttype !== $postType) {
                return;
            }
            $register_slug = sanitize_title($config['label']);
            add_meta_box(
                $register_slug,
                $config['label'],
                function ($post) use ($config) {
                    echo $this->getFieldsSection($config, function ($key) use ($post) {
                        return !empty($post->ID) ? get_post_meta($post->ID, $key, true) : null;
                    });
                },
                $postType,
                $config['position'],
                'default'
            );
        });
        add_action('save_post_' . $postType, function ($post_id) use ($config, $postType) {
            $this->saveMeta($config['fields'], function ($key, $value) use ($post_id, $postType) {
                $value = $this->getSanitizedValue($postType, $key, $value);
                return update_post_meta($post_id, $key, $value);
            });
        });
    }

    /**
     * @param $optionsPage
     * @param $config
     * @return void
     */
    private function initOptionsPageFields($optionsPage = '', $config = [])
    {
        add_action('admin_init', function () use ($optionsPage, $config) {
            register_setting($optionsPage, 'meta-fields');
            add_settings_section($config['id'], $config['label'], function ($args) use ($optionsPage, $config) {
                echo $this->getFieldsSection($config, function ($key) {
                    return get_option($key);
                });
            }, $optionsPage);
        });
        add_action('update_option_meta-fields', function () use ($optionsPage, $config) {
            $this->saveMeta($config['fields'], function ($key, $value) use ($optionsPage) {
                $value = $this->getSanitizedValue($optionsPage, $key, $value);
                return update_option($key, $value);
            });
        });
    }

    /**
     * @param $referenceId
     * @param $key
     * @param $value
     * @return mixed
     */
    private function getSanitizedValue($referenceId, $key, $value){
        /*
         * Sanitize using field based filter
         */
        $value = apply_filters($this->getHookName('sanitize') . '_field_' . $key, $value);
        /*
         * Sanitize using reference based filter
         */
        $value = apply_filters($this->getHookName('sanitize') . '_' . $referenceId, $value);
        /*
         * Sanitize using reference and field based filter
         */
        $value = apply_filters($this->getHookName('sanitize') . '_' . $referenceId . '_field_' . $key, $value);

        return $value;
    }

    /**
     * @return void
     */
    public function initRegisteredGroups()
    {
        $fieldGroups = $this->getRegisteredGroups();

        foreach ($fieldGroups as $fieldGroup) {
            $id = !empty($fieldGroup['id']) && is_string($fieldGroup['id']) ? $fieldGroup['id'] : false;
            $supports = !empty($fieldGroup['supports']) && is_array($fieldGroup['supports']) ? $fieldGroup['supports'] : false;
            $label = !empty($fieldGroup['label']) ? $fieldGroup['label'] : false;
            $adminOnly = !empty($fieldGroup['admin_only']) ? $fieldGroup['admin_only'] : false;
            unset($fieldGroup['supports'], $fieldGroup['admin_only']);
            if (
                ($adminOnly && !current_user_can('administrator')) ||
                (!$adminOnly && !current_user_can('edit_posts'))
            ) {
                continue;
            }
            if (!$id || !$supports || !$label) {
                $errorInfo = $this->getRegistrationErrorNoticeInfo($fieldGroup, 'field');

                add_filter($this->getHookName('register_notices'), function ($args) use ($errorInfo) {
                    $args[] = [
                        'id' => $errorInfo['id'],
                        'title' => $this->getNoticesTitle(),
                        'message' => __('Field group registration was not successful ("id" "label" and "supports" args are required).', 'custom-post-types') . $errorInfo['details'],
                        'type' => 'error',
                        'dismissible' => 3,
                        'buttons' => false,
                    ];
                    return $args;
                });
                continue;
            }
            foreach ($supports as $content) {
                $type = !empty($content['type']) ? $content['type'] : 'cpt';
                $id = !empty($content['id']) ? $content['id'] : false;
                if (!$id) {
                    continue;
                }
                switch ($type) {
                    case 'cpt':
                        $this->screensWithFields[] = $id;
                        $this->initPostTypeFields($id, $fieldGroup);
                        break;
                    case 'tax':
                        $this->screensWithFields[] = 'edit-' . $id;
                        $this->initTaxonomyFields($id, $fieldGroup);
                        break;
                    case 'options':
                        $coreOptionsPages = $this->getCoreSettingsPagesOptions();
                        if(isset($coreOptionsPages[$id])){
                            $this->screensWithFields[] = 'options-' . $id;
                        } else {
                            $this->screensWithFields[] = '_page_' . $id;
                        }
                        $optionPageFieldGroup = $fieldGroup;
                        foreach ($optionPageFieldGroup['fields'] as $i => $field){
                            $optionPageFieldGroup['fields'][$i]['key'] = $id . '-' . $optionPageFieldGroup['fields'][$i]['key'];
                        }
                        $this->initOptionsPageFields($id, $optionPageFieldGroup);
                        unset($optionPageFieldGroup);
                        break;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getAvailableFields()
    {
        return apply_filters($this->getHookName('field_types'), []);
    }

    /**
     * @return array
     */
    public function getAvailableFieldsLabel()
    {
        $options = [];
        $fieldTypes = $this->getAvailableFields();
        foreach ($fieldTypes as $fieldType => $args) {
            $options[$fieldType] = $args['label'];
        }
        unset($fieldTypes);
        return $options;
    }

    /**
     * @return array
     */
    public function getAvailableFieldsExtra()
    {
        $options = [];
        $fieldTypes = $this->getAvailableFields();
        foreach ($fieldTypes as $fieldType => $args) {
            $options[$fieldType] = !empty($args['extra']) ? $args['extra'] : [];
        }
        unset($fieldTypes);
        $options['repeater'] = [$this->getRepeaterFields()];
        return $options;
    }

    /**
     * @param $fieldType
     * @return false|mixed
     */
    private function getAvailableFieldTemplateCallback($fieldType = '')
    {
        $fieldTypes = $this->getAvailableFields();
        return
            !empty($fieldTypes[$fieldType]['templateCallback']) &&
            is_callable($fieldTypes[$fieldType]['templateCallback']) ?
                $fieldTypes[$fieldType]['templateCallback'] :
                false;
    }

    /**
     * @param $fieldType
     * @return false|mixed
     */
    private function getAvailableFieldSanitizeCallback($fieldType = '')
    {
        $fieldTypes = $this->getAvailableFields();
        return
            !empty($fieldTypes[$fieldType]['sanitizeCallback']) &&
            is_callable($fieldTypes[$fieldType]['sanitizeCallback']) ?
                $fieldTypes[$fieldType]['sanitizeCallback'] :
                false;
    }

    /**
     * @param $fieldType
     * @return false|mixed
     */
    public function getAvailableFieldGetCallback($fieldType = '')
    {
        $fieldTypes = $this->getAvailableFields();
        return
            !empty($fieldTypes[$fieldType]['getCallback']) &&
            is_callable($fieldTypes[$fieldType]['getCallback']) ?
                $fieldTypes[$fieldType]['getCallback'] :
                false;
    }

    /**
     * @return array
     */
    public function getRepeaterFields()
    {
        return [ // fields
            'key' => 'fields',
            'label' => __('Fields list', 'custom-post-types'),
            'info' => '',
            'required' => false,
            'type' => 'repeater',
            'extra' => [
                'fields' => [
                    [ //label
                        'key' => 'label',
                        'label' => __('Label', 'custom-post-types'),
                        'info' => false,
                        'required' => true,
                        'type' => 'text',
                        'extra' => [],
                        'wrap' => [
                            'width' => '40',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //key
                        'key' => 'key',
                        'label' => __('Key', 'custom-post-types'),
                        'info' => false,
                        'required' => true,
                        'type' => 'text',
                        'extra' => [],
                        'wrap' => [
                            'width' => '40',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //required
                        'key' => 'required',
                        'label' => __('Required', 'custom-post-types'),
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
                            'width' => '20',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //type
                        'key' => 'type',
                        'label' => __('Type', 'custom-post-types'),
                        'info' => false,
                        'required' => true,
                        'type' => 'select',
                        'extra' => [
                            'multiple' => false,
                            'options' => $this->getAvailableFieldsLabel(),
                        ],
                        'wrap' => [
                            'width' => '40',
                            'class' => 'cpt-repeater-field-type',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //info
                        'key' => 'info',
                        'label' => __('Info', 'custom-post-types'),
                        'info' => false,
                        'required' => false,
                        'type' => 'text',
                        'extra' => [],
                        'wrap' => [
                            'width' => '60',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //wrap_width
                        'key' => 'wrap_width',
                        'label' => __('Container width', 'custom-post-types') . ' (%)',
                        'info' => false,
                        'required' => false,
                        'type' => 'number',
                        'extra' => [
                            'placeholder' => '100',
                            'min' => 1,
                            'max' => 100
                        ],
                        'wrap' => [
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ],
                        'parent' => ''
                    ],
                    [ //wrap_layout
                        'key' => 'wrap_layout',
                        'label' => __('Container layout', 'custom-post-types'),
                        'info' => false,
                        'required' => false,
                        'type' => 'select',
                        'extra' => [
                            'placeholder' => __('VERTICAL', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                            'multiple' => false,
                            'options' => [
                                'vertical' => __('VERTICAL', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                                'horizontal' => __('HORIZONTAL', 'custom-post-types'),
                            ]
                        ],
                        'wrap' => [
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //wrap_class
                        'key' => 'wrap_class',
                        'label' => __('Container class', 'custom-post-types'),
                        'info' => false,
                        'required' => false,
                        'type' => 'text',
                        'extra' => [],
                        'wrap' => [
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                    [ //wrap_id
                        'key' => 'wrap_id',
                        'label' => __('Container id', 'custom-post-types'),
                        'info' => false,
                        'required' => false,
                        'type' => 'text',
                        'extra' => [],
                        'wrap' => [
                            'width' => '25',
                            'class' => '',
                            'id' => '',
                            'layout' => ''
                        ]
                    ],
                ]
            ],
            'wrap' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ]
        ];
    }

    /**
     * @return array
     */
    public function getNewFieldGroupFields()
    {
        return [
            'id' => $this->getInfo('ui_prefix') . '_field',
            'label' => __('Field group settings', 'custom-post-types'),
            'supports' => [[
                'type' => 'cpt',
                'id' => $this->getInfo('ui_prefix') . '_field'
            ]],
            'position' => 'normal',
            'order' => 0,
            'admin_only' => true,
            'fields' => [
                [ //position
                    'key' => 'position',
                    'label' => __('Position', 'custom-post-types'),
                    'info' => __('If set to "NORMAL" it will be shown at the bottom of the central column, if "SIDEBAR" it will be shown in the sidebar.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NORMAL', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'normal' => __('NORMAL', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                            'side' => __('SIDEBAR', 'custom-post-types'),
                            'advanced' => __('ADVANCED', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //order
                    'key' => 'order',
                    'label' => __('Order', 'custom-post-types'),
                    'info' => __('Field groups with a lower order will appear first', 'custom-post-types'),
                    'required' => false,
                    'type' => 'number',
                    'extra' => [
                        'placeholder' => __('ex: 10', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //supports
                    'key' => 'supports',
                    'label' => __('Assignment', 'custom-post-types'),
                    'info' => __('Choose for which CONTENT TYPE use this field group.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'multiple' => true,
                        'options' => $this->getContentsOptions(),
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //admin only
                    'key' => 'admin_only',
                    'label' => __('Administrators only', 'custom-post-types'),
                    'info' => __('If set to "YES" only the administrators can create / modify these contents, if "NO" all the roles with the minimum capacity of "edit_posts".', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                $this->getRepeaterFields()
            ]
        ];
    }

    /**
     * @return array
     */
    public function getTaxonomyFields()
    {
        return [
            'id' => $this->getInfo('ui_prefix') . '_tax',
            'label' => __('Taxonomy settings', 'custom-post-types'),
            'supports' => [[
                'type' => 'cpt',
                'id' => $this->getInfo('ui_prefix') . '_tax'
            ]],
            'position' => 'normal',
            'order' => 0,
            'admin_only' => true,
            'fields' => [
                $this->uiRegistrationArgsTitleField,
                [ //singular
                    'key' => 'singular',
                    'label' => __('Singular', 'custom-post-types'),
                    'info' => __('Singular name.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //plural
                    'key' => 'plural',
                    'label' => __('Plural', 'custom-post-types'),
                    'info' => __('Plural name.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Partners', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //id
                    'key' => 'id',
                    'label' => __('Key', 'custom-post-types'),
                    'info' => __('Taxonomy key.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //slug
                    'key' => 'slug',
                    'label' => __('Slug', 'custom-post-types'),
                    'info' => __('Permalink base for terms (if empty, plural is used).', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: partners', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'slug-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //supports
                    'key' => 'supports',
                    'label' => __('Assignment', 'custom-post-types'),
                    'info' => __('Choose for which POST TYPE use this taxonomy.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'multiple' => true,
                        'options' => $this->getPostTypesOptions(),
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //public
                    'key' => 'public',
                    'label' => __('Public', 'custom-post-types'),
                    'info' => __('If set to "YES" it will be shown in the frontend and will have a permalink and a archive template.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //admin only
                    'key' => 'admin_only',
                    'label' => __('Administrators only', 'custom-post-types'),
                    'info' => __('If set to "YES" only the administrators can create / modify these contents, if "NO" all the roles with the minimum capacity of "edit_posts".', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //hierarchical
                    'key' => 'hierarchical',
                    'label' => __('Hierarchical', 'custom-post-types'),
                    'info' => __('If set to "YES" it will be possible to set a parent TAXONOMY (as for the posts categories).', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                $this->uiRegistrationLabelsTitleField,
                [ //labels_add_new_item
                    'key' => 'labels_add_new_item',
                    'label' => __('Add new item', 'custom-post-types'),
                    'info' => __('The add new item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Add new partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_edit_item
                    'key' => 'labels_edit_item',
                    'label' => __('Edit item', 'custom-post-types'),
                    'info' => __('The edit item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Edit partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_new_item_name
                    'key' => 'labels_new_item_name',
                    'label' => __('New item name', 'custom-post-types'),
                    'info' => __('The new item name text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Partner name', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_view_item
                    'key' => 'labels_view_item',
                    'label' => __('View item', 'custom-post-types'),
                    'info' => __('The view item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: View partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_update_item
                    'key' => 'labels_update_item',
                    'label' => __('Update item', 'custom-post-types'),
                    'info' => __('The update item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Update partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_search_items
                    'key' => 'labels_search_items',
                    'label' => __('Search items', 'custom-post-types'),
                    'info' => __('The search item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Search partners', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_not_found
                    'key' => 'labels_not_found',
                    'label' => __('Not found', 'custom-post-types'),
                    'info' => __('The not found text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: No partner found', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_parent_item
                    'key' => 'labels_parent_item',
                    'label' => __('Parent item', 'custom-post-types'),
                    'info' => __('The parent item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Parent partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_parent_item_colon
                    'key' => 'labels_parent_item_colon',
                    'label' => __('Parent item', 'custom-post-types'),
                    'info' => __('The parent item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Parent partner', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_all_items
                    'key' => 'labels_all_items',
                    'label' => __('All items', 'custom-post-types'),
                    'info' => __('The all items text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: All partners', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                $this->uiRegistrationViewSwitchField
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPostTypeFields()
    {
        return [
            'id' => $this->getInfo('ui_prefix'),
            'label' => __('Post type settings', 'custom-post-types'),
            'supports' => [[
                'type' => 'cpt',
                'id' => $this->getInfo('ui_prefix')
            ]],
            'position' => 'normal',
            'order' => 0,
            'admin_only' => true,
            'fields' => [
                $this->uiRegistrationArgsTitleField,
                [ //singular
                    'key' => 'singular',
                    'label' => __('Singular', 'custom-post-types'),
                    'info' => __('Singular name.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //plural
                    'key' => 'plural',
                    'label' => __('Plural', 'custom-post-types'),
                    'info' => __('Plural name.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Products', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //id
                    'key' => 'id',
                    'label' => __('Key', 'custom-post-types'),
                    'info' => __('Post type key.', 'custom-post-types'),
                    'required' => true,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: products', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //slug
                    'key' => 'slug',
                    'label' => __('Slug', 'custom-post-types'),
                    'info' => __('Permalink base for posts (if empty, plural is used).', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'slug-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //supports
                    'key' => 'supports',
                    'label' => __('Supports', 'custom-post-types'),
                    'info' => __('Set the available components when editing a post.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'multiple' => true,
                        'options' => [
                            'title' => __('Title', 'custom-post-types'),
                            'editor' => __('Editor', 'custom-post-types'),
                            'comments' => __('Comments', 'custom-post-types'),
                            'revisions' => __('Revisions', 'custom-post-types'),
                            'trackbacks' => __('Trackbacks', 'custom-post-types'),
                            'author' => __('Author', 'custom-post-types'),
                            'excerpt' => __('Excerpt', 'custom-post-types'),
                            'page-attributes' => __('Page attributes', 'custom-post-types'),
                            'thumbnail' => __('Thumbnail', 'custom-post-types'),
                            'custom-fields' => __('Custom fields', 'custom-post-types'),
                            'post-formats' => __('Post formats', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //menu_icon
                    'key' => 'menu_icon',
                    'label' => __('Menu icon', 'custom-post-types'),
                    'info' => __('Url to the icon, base64-encoded SVG using a data URI, name of a <a href="https://developer.wordpress.org/resource/dashicons" target="_blank" rel="nofolow">Dashicons</a> e.g. \'dashicons-chart-pie\'.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('dashicons-tag', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //public
                    'key' => 'public',
                    'label' => __('Public', 'custom-post-types'),
                    'info' => __('If set to "YES" it will be shown in the frontend and will have a permalink and a single template.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //admin only
                    'key' => 'admin_only',
                    'label' => __('Administrators only', 'custom-post-types'),
                    'info' => __('If set to "YES" only the administrators can create / modify these contents, if "NO" all the roles with the minimum capacity of "edit_posts".', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //hierarchical
                    'key' => 'hierarchical',
                    'label' => __('Hierarchical', 'custom-post-types'),
                    'info' => __('If set to "YES" it will be possible to set a parent POST TYPE (as for pages).', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //has_archive
                    'key' => 'has_archive',
                    'label' => __('Has archive', 'custom-post-types'),
                    'info' => __('If set to "YES" the url of the post type archive will be reachable.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //exclude_from_search
                    'key' => 'exclude_from_search',
                    'label' => __('Exclude from search', 'custom-post-types'),
                    'info' => __('If set to "YES" these posts will be excluded from the search results.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //show_in_rest
                    'key' => 'show_in_rest',
                    'label' => __('Show in rest', 'custom-post-types'),
                    'info' => __('If set to "YES" API endpoints will be available (required for Gutenberg and other builders).', 'custom-post-types'),
                    'required' => false,
                    'type' => 'select',
                    'extra' => [
                        'placeholder' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'multiple' => false,
                        'options' => [
                            'true' => __('YES', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                            'false' => __('NO', 'custom-post-types'),
                        ],
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                $this->uiRegistrationLabelsTitleField,
                [ //labels_add_new_item
                    'key' => 'labels_add_new_item',
                    'label' => __('Add new item', 'custom-post-types'),
                    'info' => __('The add new item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Add new product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_edit_item
                    'key' => 'labels_edit_item',
                    'label' => __('Edit item', 'custom-post-types'),
                    'info' => __('The edit item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Edit product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_new_item
                    'key' => 'labels_new_item',
                    'label' => __('New item', 'custom-post-types'),
                    'info' => __('The new item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: New product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_view_item
                    'key' => 'labels_view_item',
                    'label' => __('View item', 'custom-post-types'),
                    'info' => __('The view item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: View product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_view_items
                    'key' => 'labels_view_items',
                    'label' => __('View items', 'custom-post-types'),
                    'info' => __('The view items text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: View products', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_search_items
                    'key' => 'labels_search_items',
                    'label' => __('Search items', 'custom-post-types'),
                    'info' => __('The search item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Search products', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_not_found
                    'key' => 'labels_not_found',
                    'label' => __('Not found', 'custom-post-types'),
                    'info' => __('The not found text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: No product found', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_not_found_in_trash
                    'key' => 'labels_not_found_in_trash',
                    'label' => __('Not found in trash', 'custom-post-types'),
                    'info' => __('The not found in trash text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: No product found in trash', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_parent_item_colon
                    'key' => 'labels_parent_item_colon',
                    'label' => __('Parent item', 'custom-post-types'),
                    'info' => __('The parent item text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Parent product', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_all_items
                    'key' => 'labels_all_items',
                    'label' => __('All items', 'custom-post-types'),
                    'info' => __('The all items text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: All products', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                [ //labels_archives
                    'key' => 'labels_archives',
                    'label' => __('Archivies', 'custom-post-types'),
                    'info' => __('The archives text.', 'custom-post-types'),
                    'required' => false,
                    'type' => 'text',
                    'extra' => [
                        'placeholder' => __('ex: Product archives', 'custom-post-types')
                    ],
                    'wrap' => [
                        'width' => '',
                        'class' => 'advanced-field',
                        'id' => '',
                        'layout' => 'horizontal'
                    ]
                ],
                $this->uiRegistrationViewSwitchField
            ]
        ];
    }
}