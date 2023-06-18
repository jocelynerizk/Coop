<?php

use CustomPostTypesPlugin\Includes\Fields;

$fieldsClass = new Fields();

add_filter($fieldsClass->getHookName('field_types'), function ($fields) use ($fieldsClass) {
    $callback = function ($fields, $parent, $value = []) use ($fieldsClass) {
        ob_start();
        ?>
        <div class="cpt-repeater-group">
            <div class="cpt-repeater-buttons">
                <div class="order"></div>
                <button class="button cpt-repeater-button button-secondary move"
                        title="<?php _e('Reorder', 'custom-post-types'); ?>"
                        aria-label="<?php _e('Reorder', 'custom-post-types'); ?>">
                    <span class="dashicons dashicons-move"></span>
                </button>
                <button class="button cpt-repeater-button button-secondary remove"
                        title="<?php _e('Remove', 'custom-post-types'); ?>"
                        aria-label="<?php _e('Remove', 'custom-post-types'); ?>">
                    <span class="dashicons dashicons-remove"></span>
                </button>
            </div>
            <div class="cpt-repeater-fields">
                <?php
                foreach ($fields as $i => $field) {
                    if ($i == 5) {
                        ?>
                        <div class="cpt-repeater-extra">
                            <?php
                            $fieldType = !empty($value['type']) ? $value['type'] : false;
                            if ($fieldType || !empty($fieldsClass->getAvailableFields()[$fieldType])) {
                                $extraFields = $fieldsClass->getAvailableFieldsExtra()[$fieldType];
                                foreach ($extraFields as $extraField) {
                                    $extraField['value'] = isset($value['extra'][$extraField['key']]) ? $value['extra'][$extraField['key']] : '';
                                    $extraField['parent'] = $parent . '[extra]';
                                    echo $fieldsClass->getFieldTemplate($extraField);
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                    $field['value'] = isset($value[$field['key']]) ? $value[$field['key']] : '';
                    $field['parent'] = $parent;
                    echo $fieldsClass->getFieldTemplate($field);
                }
                ?>
            </div>
            <div class="cpt-repeater-remove" aria-hidden="true">
                <button class="button button-secondary abort"
                        title="<?php _e('Cancel', 'custom-post-types'); ?>"
                        aria-label="<?php _e('Cancel', 'custom-post-types'); ?>">
                    <?php _e('Cancel', 'custom-post-types'); ?>
                </button>
                <button class="button button-primary confirm"
                        title="<?php _e('Confirm', 'custom-post-types'); ?>"
                        aria-label="<?php _e('Confirm', 'custom-post-types'); ?>">
                    <?php _e('Confirm', 'custom-post-types'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    };
    $fields['repeater'] = [
        'label' => __('Repeater', 'custom-post-types'),
        'groupTemplateCallback' => $callback,
        'templateCallback' => function ($name, $id, $config) use ($callback) {
            $fields = !empty($config['extra']['fields']) && is_array($config['extra']['fields']) ? $config['extra']['fields'] : [];
            $values = !empty($config['value']) && is_array($config['value']) ? $config['value'] : [];
            $parentBase = (!empty($config['parent']) ? $config['parent'] : '') . '[' . $config['key'] . ']';
            ob_start();
            ?>
            <div class="cpt-repeater-section"
                 data-fields="<?php echo htmlspecialchars(json_encode($fields), ENT_QUOTES, 'UTF-8'); ?>"
                 data-parent="<?php echo $parentBase; ?>"
            >
                <?php foreach ($values as $i => $value) {
                    $parent = $parentBase . '[' . $i . ']';
                    echo $callback($fields, $parent, $value);
                } ?>
            </div>
            <button class="cpt-repeater-add" title="<?php _e('Add', 'custom-post-types'); ?>">
                <span class="dashicons dashicons-insert"></span>
            </button>
            <?php
            return ob_get_clean();
        },
        'sanitizeCallback' => function ($value) {
            return array_values($value);
        }
    ];
    return $fields;
}, 170);

add_filter($fieldsClass->getHookName('register_ajax_actions'), function ($actions) use ($fieldsClass) {
    $actions['cpt-get-repeater-group'] = [
        'requiredParams' => ['fields'],
        'callback' => function ($params) use ($fieldsClass) {
            $fields = is_array(json_decode(stripslashes($params['fields']), true)) ? json_decode(stripslashes($params['fields']), true) : [];
            if (empty($fields)) wp_send_json_error();
            $parent = !empty($params['parent']) ? $params['parent'] : '';
            $renderCallback = $fieldsClass->getAvailableFields();
            if (empty($renderCallback['repeater']['groupTemplateCallback']) || !is_callable($renderCallback['repeater']['groupTemplateCallback'])) wp_send_json_error();
            $result = $renderCallback['repeater']['groupTemplateCallback']($fields, $parent);
            return $result;
        },
    ];
    $actions['cpt-get-repeater-extra-fields'] = [
        'requiredParams' => ['field-type'],
        'callback' => function ($params) use ($fieldsClass) {
            $fieldType = $params['field-type'];
            if (empty($fieldsClass->getAvailableFields()[$fieldType])) wp_send_json_error();
            $parent = !empty($params['parent']) ? $params['parent'] : '';
            $fields = $fieldsClass->getAvailableFieldsExtra()[$fieldType];
            ob_start();
            foreach ($fields as $field) {
                $field['value'] = '';
                $field['parent'] = $parent . '[extra]';//[' . $fieldType . ']';
                echo $fieldsClass->getFieldTemplate($field);
            }
            $result = ob_get_clean();
            return $result;
        },
    ];
    return $actions;
});