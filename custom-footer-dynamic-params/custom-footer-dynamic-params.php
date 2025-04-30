<?php
/*
Plugin Name: Custom Footer Dynamic Params
Description: Позволяет добавлять любое количество настраиваемых параметров в футер сайта через админ-панель.
Version: 1.1
Author: Alex Maker
*/

define('CFDP_OPTION', 'cfdp_fields');

function cfdp_plugin_row_meta($links, $file) {
    $plugin_file = plugin_basename(__FILE__);

    if ($file == $plugin_file) {
        $end_row_meta = array(
            'linkedin' => '<a href="https://www.linkedin.com/in/alex-maker-rachinskiy/" target="_blank">LinkedIn</a>',
        );
        $links = array_merge($links, $end_row_meta);
    }

    return $links;
}
add_filter('plugin_row_meta', 'cfdp_plugin_row_meta', 10, 2);

/**
 * Добавление страницы настроек
 */
add_action('admin_menu', function() {
    add_options_page(
        'Footer Dynamic Params',
        'Footer Dynamic Params',
        'manage_options',
        'cfdp-footer-params',
        'cfdp_render_settings_page'
    );
});

/**
 * Подключение JS на странице настроек плагина
 */
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook == 'settings_page_cfdp-footer-params') {
        wp_enqueue_script(
            'cfdp-admin-js',
            plugins_url('admin.js', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__).'admin.js'),
            true
        );
    }
});

/**
 * Рендер страницы настроек
 */
function cfdp_render_settings_page() {
    // Получение полей из базы данных
    $fields = get_option(CFDP_OPTION, []);

    // Обработка и сохранение данных формы
    if (isset($_POST['cfdp_save'])) {
        check_admin_referer('cfdp_save_fields');
        $labels = $_POST['cfdp_label'] ?? [];
        $values = $_POST['cfdp_value'] ?? [];
        $types  = $_POST['cfdp_type'] ?? [];
        $new_fields = [];
        foreach ($labels as $i => $label) {
            $label = sanitize_text_field($label);
            $type  = in_array($types[$i], ['text','phone','email']) ? $types[$i] : 'text';
            $value = sanitize_text_field($values[$i]);

            // Валидация по типу поля
            if ($label !== '') {
                if ($type == 'phone' && !cfdp_is_valid_phone($value)) {
                    add_settings_error('cfdp_fields', 'cfdp_phone_invalid', 'Некорректный номер телефона для "' . $label . '". Ожидаемый формат: (000)-000-000-00');
                    $value = '';
                }
                if ($type == 'email' && !is_email($value)) {
                    add_settings_error('cfdp_fields', 'cfdp_email_invalid', 'Некорректный email для "' . $label . '"');
                    $value = '';
                }
                $new_fields[] = [
                    'label' => $label,
                    'type'  => $type,
                    'value' => $value,
                ];
            }
        }
        update_option(CFDP_OPTION, $new_fields);
        $fields = $new_fields;
        echo '<div class="updated"><p>Параметры сохранены.</p></div>';
    }

    // Вывод ошибок валидации
    settings_errors('cfdp_fields');
    ?>
    <div class="wrap">
        <h1>Footer Dynamic Params</h1>
        <form method="post">
            <?php wp_nonce_field('cfdp_save_fields'); ?>
            <table class="form-table" id="cfdp-fields-table">
                <thead>
                    <tr>
                        <th>Лейбл</th>
                        <th>Тип</th>
                        <th>Значение</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($fields)): ?>
                    <?php foreach ($fields as $i => $field): ?>
                        <tr>
                            <td>
                                <input type="text" name="cfdp_label[]" value="<?php echo esc_attr($field['label']); ?>" class="regular-text" required>
                            </td>
                            <td>
                                <select name="cfdp_type[]" class="cfdp-type-select">
                                    <option value="text" <?php selected($field['type'] ?? 'text', 'text'); ?>>Текст</option>
                                    <option value="phone" <?php selected($field['type'] ?? '', 'phone'); ?>>Телефон</option>
                                    <option value="email" <?php selected($field['type'] ?? '', 'email'); ?>>Email</option>
                                </select>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="cfdp_value[]"
                                    value="<?php echo esc_attr($field['value']); ?>"
                                    class="regular-text"
                                    <?php echo (($field['type'] ?? '') === 'phone') ? 'placeholder="(000)-000-000-00"' : ''; ?>
                                >
                                <?php if (($field['type'] ?? '') === 'phone'): ?>
                                    <div class="cfdp-phone-hint" style="color:#888;font-size:12px;">Формат: (000)-000-000-00</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button cfdp-remove-row">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="cfdp-add-row">Добавить поле</button>
            </p>
            <?php submit_button('Сохранить', 'primary', 'cfdp_save'); ?>
        </form>
    </div>
    <?php
}

/**
 * Валидация номера телефона
 */
function cfdp_is_valid_phone($phone) {
    $phone = trim($phone);
    if (empty($phone)) return true;
    // Формат (000)-000-000-00
    return preg_match('/^\(\d{3}\)-\d{3}-\d{3}-\d{2}$/', $phone);
}

/**
 * Добавление стилей в хед
 */
add_action('wp_head', function(){
    ?>
    <style>
        .cfdp-footer-params {
            margin-top: 30px;
            padding: 15px;
            background: #f6f6f6;
            border-radius: 8px;
            font-size: 16px;
        }
        .cfdp-footer-params span {
            display: block;
            margin-bottom: 5px;
        }
        .cfdp-footer-params .cfdp-label {
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
    <?php
});

/**
 * Шорткод и функция вывода параметров
 */
function cfdp_footer_params_shortcode() {
    $fields = get_option(CFDP_OPTION, []);
    if (empty($fields)) return '';
    $out = '<div class="cfdp-footer-params">';
    foreach ($fields as $field) {
        $label = esc_html($field['label']);
        $value = esc_html($field['value']);
        $type  = $field['type'] ?? 'text';

        if ($value !== '') {
            if ($type === 'phone') {
                $out .= '<span><span class="cfdp-label">' . $label . ':</span> <a href="tel:' . preg_replace('/\D/', '', $value) . '">' . $value . '</a></span>';
            } elseif ($type === 'email') {
                $out .= '<span><span class="cfdp-label">' . $label . ':</span> <a href="mailto:' . esc_attr($value) . '">' . $value . '</a></span>';
            } else {
                $out .= '<span><span class="cfdp-label">' . $label . ':</span> ' . $value . '</span>';
            }
        }
    }
    $out .= '</div>';
    return $out;
}
add_shortcode('cfdp_footer_params', 'cfdp_footer_params_shortcode');
