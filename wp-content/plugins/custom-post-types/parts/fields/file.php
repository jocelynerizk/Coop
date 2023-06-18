<?php

use CustomPostTypesPlugin\Includes\Component;

$utils = new Component();

add_filter($utils->getHookName('field_types'), function ($fields) {
    $fields['file'] = [
        'label' => __('File upload', 'custom-post-types'),
        'templateCallback' => function ($name, $id, $config) {
            $types = !empty($config['extra']['types']) ? $config['extra']['types'] : ['image'];
            ob_start();
            ?>
            <div class="cpt-file-section"
                 data-type="<?php echo htmlspecialchars(json_encode($types), ENT_QUOTES, 'UTF-8'); ?>">
                <input name="<?php echo $name; ?>" value="<?php echo $config['value']; ?>" class="cpt-hidden-input">
                <div class="cpt-file-wrap">
                    <div class="cpt-file-preview">
                        <?php
                        echo $config['value'] && wp_get_attachment_image($config['value'], 'thumbnail', false, []) ? wp_get_attachment_image($config['value'], 'thumbnail', false, []) : '<img width="150" height="150" style="display: none;" class="attachment-thumbnail size-thumbnail" alt="" loading="lazy">';
                        ?>
                    </div>
                    <div class="cpt-file-actions"
                         title="<?php echo $config['value'] && get_post($config['value']) ? basename(get_attached_file($config['value'])) : __('Choose', 'custom-post-types'); ?>">
                        <div class="file-name"
                             dir="rtl"><?php echo $config['value'] && get_post($config['value']) ? basename(get_attached_file($config['value'])) : ''; ?></div>
                        <div class="buttons">
                            <button class="button cpt-file-button button-primary cpt-file-upload" id="<?php echo $id; ?>"
                                    title="<?php _e('Choose', 'custom-post-types'); ?>"
                                    aria-label="<?php _e('Choose', 'custom-post-types'); ?>">
                                <span class="dashicons dashicons-upload"></span>
                            </button>
                            <button class="button cpt-file-button button-secondary cpt-file-remove" <?php echo empty($config['value']) ? ' disabled="disabled"' : ''; ?>
                                    title="<?php _e('Remove', 'custom-post-types'); ?>"
                                    aria-label="<?php _e('Remove', 'custom-post-types'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        },
        'extra' => [
            [ //types
                'key' => 'types',
                'label' => __('Type', 'custom-post-types'),
                'info' => false,
                'required' => false,
                'type' => 'select',
                'extra' => [
                    'placeholder' => __('Image (all extensions)', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                    'multiple' => true,
                    'options' => [
                        'image' => __('Image (all extensions)', 'custom-post-types') . ' - ' . __('Default', 'custom-post-types'),
                        'audio' => __('Audio (all extensions)', 'custom-post-types'),
                        'video' => __('Video (all extensions)', 'custom-post-types'),
                        'application/pdf' => __('.pdf', 'custom-post-types'),
                        'application/zip' => __('.zip', 'custom-post-types'),
                        'text/plain' => __('.txt', 'custom-post-types'),
                        'application/msword' => __('.doc', 'custom-post-types'),
                    ]
                ],
                'wrap' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                    'layout' => ''
                ]
            ],
        ],
        'sanitizeCallback' => function ($value) {
            return get_post($value) ? $value : '';
        },
        'getCallback' => function ($output) {
            $file_type = get_post_mime_type($output);
            $file_types = explode('/', $file_type);
            $main_type = isset($file_types[0]) ? $file_types[0] : false;
            if ($main_type && $main_type == 'image') {
                return wp_get_attachment_image($output, 'full');
            }
            return wp_get_attachment_url($output);
        }
    ];
    return $fields;
}, 130);