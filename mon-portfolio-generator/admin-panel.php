<?php
if (!defined('ABSPATH')) {
    exit;
}

function monpg_register_admin_page() {
    add_menu_page(
        __('Портфолиа МОН', 'mon-portfolio-generator'),
        __('Портфолиа МОН', 'mon-portfolio-generator'),
        'manage_options',
        'monpg-statistics',
        'monpg_render_admin_page',
        'dashicons-welcome-learn-more',
        58
    );
}

function monpg_render_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Нямате права за достъп до тази страница.', 'mon-portfolio-generator'));
    }

    $stats = get_option(MON_Portfolio_Generator::OPTION_KEY, array());
    $generated = isset($stats['generated']) ? absint($stats['generated']) : 0;
    $previewed = isset($stats['previewed']) ? absint($stats['previewed']) : 0;
    $last_event = isset($stats['last_event_at']) ? sanitize_text_field($stats['last_event_at']) : '—';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Портфолиа МОН — анонимна статистика', 'mon-portfolio-generator'); ?></h1>
        <p><?php echo esc_html__('Системата не съхранява лични данни, текстове, имена на файлове или самите документи. Показаните стойности са общи броячи.', 'mon-portfolio-generator'); ?></p>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin:24px 0;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:22px;min-width:220px;">
                <div style="font-size:13px;color:#646970;"><?php echo esc_html__('Генерирани PDF документи', 'mon-portfolio-generator'); ?></div>
                <strong style="display:block;font-size:34px;margin-top:8px;"><?php echo esc_html(number_format_i18n($generated)); ?></strong>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:22px;min-width:220px;">
                <div style="font-size:13px;color:#646970;"><?php echo esc_html__('Отворени прегледи', 'mon-portfolio-generator'); ?></div>
                <strong style="display:block;font-size:34px;margin-top:8px;"><?php echo esc_html(number_format_i18n($previewed)); ?></strong>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:22px;min-width:260px;">
                <div style="font-size:13px;color:#646970;"><?php echo esc_html__('Последно събитие', 'mon-portfolio-generator'); ?></div>
                <strong style="display:block;font-size:18px;margin-top:12px;"><?php echo esc_html($last_event); ?></strong>
            </div>
        </div>
        <h2><?php echo esc_html__('Какво се записва?', 'mon-portfolio-generator'); ?></h2>
        <table class="widefat striped" style="max-width:780px;">
            <tbody>
                <tr><th><?php echo esc_html__('Тип данни', 'mon-portfolio-generator'); ?></th><th><?php echo esc_html__('Съхранение', 'mon-portfolio-generator'); ?></th></tr>
                <tr><td><?php echo esc_html__('Общ брой генерирания и прегледи', 'mon-portfolio-generator'); ?></td><td><?php echo esc_html__('Да — агрегирани броячи', 'mon-portfolio-generator'); ?></td></tr>
                <tr><td><?php echo esc_html__('Имена, длъжност, училище и свободен текст', 'mon-portfolio-generator'); ?></td><td><?php echo esc_html__('Не', 'mon-portfolio-generator'); ?></td></tr>
                <tr><td><?php echo esc_html__('Сертификати, снимки и PDF файлове', 'mon-portfolio-generator'); ?></td><td><?php echo esc_html__('Не', 'mon-portfolio-generator'); ?></td></tr>
                <tr><td><?php echo esc_html__('IP адрес, потребителски агент или профил на посетител', 'mon-portfolio-generator'); ?></td><td><?php echo esc_html__('Не се записват от този плъгин', 'mon-portfolio-generator'); ?></td></tr>
            </tbody>
        </table>
        <p style="margin-top:18px;color:#646970;"><?php echo esc_html__('Забележка: хостинг доставчикът, уеб сървърът, защитни плъгини или CDN могат независимо да водят технически логове. Настройте ги отделно според политиката си за поверителност.', 'mon-portfolio-generator'); ?></p>
    </div>
    <?php
}
