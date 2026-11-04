<?php
/**
 * Plugin Name: MON Portfolio Generator (Zero-Storage Edition)
 * Description: Клиентски генератор на учителско портфолио по шест тематични раздела. Личните данни и файловете не се изпращат към WordPress.
 * Version: 1.0.0
 * Author: Weburov
 * Text Domain: mon-portfolio-generator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MONPG_VERSION', '1.0.0');
define('MONPG_FILE', __FILE__);
define('MONPG_DIR', plugin_dir_path(__FILE__));
define('MONPG_URL', plugin_dir_url(__FILE__));

require_once MONPG_DIR . 'admin-panel.php';

final class MON_Portfolio_Generator {
    const OPTION_KEY = 'monpg_aggregate_stats';
    const NONCE_ACTION = 'monpg_event';

    public function __construct() {
        add_shortcode('mon_portfolio_form', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('wp_ajax_monpg_track', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_monpg_track', array($this, 'track_event'));
        add_action('admin_menu', 'monpg_register_admin_page');
    }

    public function register_assets() {
        wp_register_style(
            'monpg-style',
            MONPG_URL . 'assets/css/style.css',
            array(),
            MONPG_VERSION
        );

        // jsPDF and html2canvas are loaded only on pages where the shortcode is rendered.
        wp_register_script(
            'monpg-jspdf',
            'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js',
            array(),
            '2.5.2',
            true
        );
        wp_register_script(
            'monpg-html2canvas',
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
            array(),
            '1.4.1',
            true
        );
        wp_register_script(
            'monpg-script',
            MONPG_URL . 'assets/js/script.js',
            array('monpg-jspdf', 'monpg-html2canvas'),
            MONPG_VERSION,
            true
        );
    }

    public function render_shortcode($atts = array()) {
        wp_enqueue_style('monpg-style');
        wp_enqueue_script('monpg-script');

        $config = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'maxFileBytes' => 5 * 1024 * 1024,
            'maxFiles' => 8,
            'strings' => array(
                'required' => __('Моля, попълнете задължителните полета.', 'mon-portfolio-generator'),
                'fileType' => __('Разрешени са само PDF, JPG, PNG и WEBP файлове.', 'mon-portfolio-generator'),
                'fileSize' => __('Всеки файл трябва да е до 5 MB.', 'mon-portfolio-generator'),
                'tooMany' => __('Можете да добавите до 8 файла.', 'mon-portfolio-generator'),
                'pdfError' => __('Възникна проблем при създаването на PDF. Опитайте отново.', 'mon-portfolio-generator'),
                'pdfReady' => __('PDF файлът е генериран и изтеглен на устройството Ви.', 'mon-portfolio-generator')
            )
        );

        wp_add_inline_script(
            'monpg-script',
            'window.MONPG_CONFIG = ' . wp_json_encode($config) . ';',
            'before'
        );

        ob_start();
        ?>
        <div class="monpg-app" data-monpg>
            <div class="monpg-heading">
                <span class="monpg-eyebrow">ПРОФЕСИОНАЛНО ПОРТФОЛИО</span>
                <h2>Учителско портфолио</h2>
                <p>Попълнете шестте раздела и генерирайте собствен PDF документ. Данните остават във Вашия браузър.</p>
            </div>

            <div class="monpg-privacy" role="note">
                <span class="monpg-privacy-icon" aria-hidden="true">✓</span>
                <span><strong>Локална обработка:</strong> личните данни и прикачените файлове не се изпращат и не се записват в WordPress.</span>
            </div>

            <div class="monpg-progress-wrap">
                <div class="monpg-progress-meta">
                    <span class="monpg-step-label">Стъпка <b data-current-step>1</b> от 6</span>
                    <span data-progress-percent>17%</span>
                </div>
                <div class="monpg-progress"><span data-progress-bar></span></div>
                <div class="monpg-step-nav" aria-label="Раздели на формуляра">
                    <?php
                    $step_names = array('Основна информация', 'Образование', 'Практика', 'Участие', 'Постижения', 'PDF');
                    foreach ($step_names as $index => $name) :
                    ?>
                        <button type="button" class="monpg-step-dot<?php echo 0 === $index ? ' is-active' : ''; ?>" data-jump-step="<?php echo esc_attr($index); ?>" aria-label="<?php echo esc_attr($name); ?>" title="<?php echo esc_attr($name); ?>">
                            <span><?php echo esc_html($index + 1); ?></span>
                            <small><?php echo esc_html($name); ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <form class="monpg-form" novalidate>
                <section class="monpg-panel is-active" data-step="0">
                    <div class="monpg-panel-heading"><span>01</span><div><h3>Основна информация</h3><p>Професионална идентичност и педагогически профил.</p></div></div>
                    <div class="monpg-grid monpg-grid-3">
                        <label>Име <em>*</em><input type="text" name="first_name" autocomplete="given-name" required maxlength="100"></label>
                        <label>Презиме<input type="text" name="middle_name" maxlength="100"></label>
                        <label>Фамилия <em>*</em><input type="text" name="last_name" autocomplete="family-name" required maxlength="100"></label>
                    </div>
                    <div class="monpg-grid monpg-grid-2">
                        <label>Длъжност <em>*</em>
                            <select name="position" required>
                                <option value="">Изберете длъжност</option>
                                <option>Учител</option><option>Старши учител</option><option>Главен учител</option>
                                <option>Учител в детска градина</option><option>Старши учител в детска градина</option>
                                <option>Главен учител в детска градина</option><option>Директор</option><option>Заместник-директор</option><option>Друга педагогическа длъжност</option>
                            </select>
                        </label>
                        <label>Учебен предмет / група<input type="text" name="subject" maxlength="180" placeholder="Напр. Математика, Подготвителна група"></label>
                        <label>Училище / детска градина<input type="text" name="institution" maxlength="180"></label>
                        <label>Населено място<input type="text" name="location" maxlength="120"></label>
                        <label>Общ трудов стаж (години)<input type="number" name="total_experience" min="0" max="70" step="0.1"></label>
                        <label>Педагогически стаж (години)<input type="number" name="teaching_experience" min="0" max="70" step="0.1"></label>
                    </div>
                    <label>Любимо мото / педагогически цитат<textarea name="motto" rows="3" maxlength="1200" placeholder="Думи, които отразяват Вашата педагогическа мисия..."></textarea></label>
                </section>

                <section class="monpg-panel" data-step="1" hidden>
                    <div class="monpg-panel-heading"><span>02</span><div><h3>Образование и квалификация</h3><p>Академична подготовка, ПКС и продължаващо обучение.</p></div></div>
                    <div class="monpg-grid monpg-grid-2">
                        <label>Образователна степен<select name="education"><option value="">Изберете</option><option>Бакалавър</option><option>Магистър</option><option>Доктор</option><option>Професионален бакалавър</option></select></label>
                        <label>Специалност<input type="text" name="specialty" maxlength="180"></label>
                        <label>Придобита професионално-квалификационна степен<select name="pqs"><option value="">Изберете</option><option>Няма</option><option>I степен</option><option>II степен</option><option>III степен</option><option>IV степен</option><option>V степен</option></select></label>
                        <label>Владеене на чужди езици<textarea name="languages" rows="3" maxlength="1500" placeholder="Език и ниво на владеене"></textarea></label>
                    </div>
                    <div class="monpg-subsection">
                        <div class="monpg-subsection-head"><div><h4>Квалификационни кредити и обучения</h4><p>Добавяйте отделен ред за всяко обучение.</p></div><button type="button" class="monpg-button monpg-button-light monpg-add-row" data-add-row="credits">＋ Добави обучение</button></div>
                        <div class="monpg-dynamic-list" data-list="credits">
                            <div class="monpg-dynamic-row" data-row>
                                <label>Тема<input type="text" data-field="topic" maxlength="180"></label>
                                <label>Кредити<input type="number" data-field="credits" min="0" max="1000" step="0.5"></label>
                                <label>Код по МОН<input type="text" data-field="code" maxlength="80"></label>
                                <label>Организация<input type="text" data-field="organization" maxlength="180"></label>
                                <button type="button" class="monpg-remove-row" aria-label="Премахни ред" disabled>×</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="monpg-panel" data-step="2" hidden>
                    <div class="monpg-panel-heading"><span>03</span><div><h3>Практическа дейност и философия</h3><p>Вашият подход към преподаването и развитието на учениците.</p></div></div>
                    <label>Методи на преподаване и дигитални инструменти<textarea name="methods" rows="6" maxlength="5000" placeholder="Опишете методи, стратегии, платформи и дигитални инструменти..."></textarea></label>
                    <label>Педагогическа философия / верую<textarea name="philosophy" rows="6" maxlength="5000" placeholder="Какво е водещо във Вашата работа с децата и учениците?"></textarea></label>
                    <label>Добри практики и открити уроци<textarea name="practices" rows="6" maxlength="5000" placeholder="Опишете добри практики, инициативи и открити уроци..."></textarea></label>
                </section>

                <section class="monpg-panel" data-step="3" hidden>
                    <div class="monpg-panel-heading"><span>04</span><div><h3>Участие и членство</h3><p>Професионална активност, партньорства и общности.</p></div></div>
                    <label>Участие в проекти и програми<textarea name="projects" rows="6" maxlength="5000" placeholder="Напр. Еразъм+, национални и европейски програми..."></textarea></label>
                    <label>Научни изяви, конференции и форуми<textarea name="events" rows="5" maxlength="5000"></textarea></label>
                    <label>Членство в професионални организации<textarea name="memberships" rows="4" maxlength="3000"></textarea></label>
                </section>

                <section class="monpg-panel" data-step="4" hidden>
                    <div class="monpg-panel-heading"><span>05</span><div><h3>Постижения и авторство</h3><p>Отличия, резултати и създадени образователни материали.</p></div></div>
                    <label>Лични награди и отличия<textarea name="awards" rows="5" maxlength="4000"></textarea></label>
                    <label>Постижения и спечелени награди от ученици<textarea name="student_achievements" rows="5" maxlength="4000"></textarea></label>
                    <label>Авторство на учебници, помагала и статии<textarea name="publications" rows="5" maxlength="4000"></textarea></label>
                </section>

                <section class="monpg-panel" data-step="5" hidden>
                    <div class="monpg-panel-heading"><span>06</span><div><h3>Документи и генериране</h3><p>Добавете доказателствени материали и прегледайте резултата.</p></div></div>
                    <div class="monpg-upload">
                        <div class="monpg-upload-icon">↥</div><strong>Добавете сертификати и снимки</strong>
                        <p>Файловете остават в браузъра. PDF, JPG, PNG или WEBP. До 8 файла, максимум 5 MB всеки.</p>
                        <input type="file" name="attachments" data-attachments accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" multiple>
                        <div class="monpg-file-list" data-file-list aria-live="polite"></div>
                    </div>
                    <div class="monpg-consent">
                        <label class="monpg-check"><input type="checkbox" name="confirm_local" required> <span>Потвърждавам, че имам право да използвам въведените данни и прикачените документи и разбирам, че PDF се генерира локално в браузъра. <em>*</em></span></label>
                    </div>
                    <div class="monpg-preview-card">
                        <div><strong>Готови ли сте?</strong><p>Прегледайте оформлението, след което свалете PDF документа.</p></div>
                        <div class="monpg-action-row"><button type="button" class="monpg-button monpg-button-light" data-preview>Преглед на портфолиото</button><button type="button" class="monpg-button monpg-button-primary" data-download>Свали готово портфолио (PDF) <span>↓</span></button></div>
                    </div>
                    <p class="monpg-disclaimer">Документът е помощен шаблон, структуриран по зададените тематични раздели. Проверете съдържанието и приложимите изисквания на институцията/работодателя преди официално представяне. Генераторът не представлява становище за правно съответствие.</p>
                </section>

                <div class="monpg-form-error" role="alert" hidden></div>
                <div class="monpg-footer-nav">
                    <button type="button" class="monpg-button monpg-button-light" data-prev disabled>← Назад</button>
                    <span>Промените се пазят само докато страницата е отворена.</span>
                    <button type="button" class="monpg-button monpg-button-primary" data-next>Продължи →</button>
                </div>
            </form>
            <div class="monpg-preview-modal" data-modal hidden>
                <div class="monpg-modal-backdrop" data-close-modal></div>
                <div class="monpg-modal-dialog" role="dialog" aria-modal="true" aria-label="Преглед на портфолиото">
                    <div class="monpg-modal-head"><strong>Преглед на портфолиото</strong><button type="button" data-close-modal aria-label="Затвори">×</button></div>
                    <div class="monpg-modal-body" data-preview-body></div>
                    <div class="monpg-modal-actions"><button type="button" class="monpg-button monpg-button-primary" data-modal-download>Свали PDF</button></div>
                </div>
            </div>
            <div class="monpg-status" data-status role="status" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_event() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        // Deliberately persist aggregate counters only. Do not store IP, user agent,
        // names, form fields, attachment metadata, cookies or a per-user identifier.
        $allowed_events = array('generated', 'previewed');
        $event = isset($_POST['event']) ? sanitize_key(wp_unslash($_POST['event'])) : '';

        if (!in_array($event, $allowed_events, true)) {
            wp_send_json_error(array('message' => 'Invalid event.'), 400);
        }

        $stats = get_option(self::OPTION_KEY, array());
        if (!is_array($stats)) {
            $stats = array();
        }

        $stats['generated'] = isset($stats['generated']) ? absint($stats['generated']) : 0;
        $stats['previewed'] = isset($stats['previewed']) ? absint($stats['previewed']) : 0;
        $stats['generated' === $event ? 'generated' : 'previewed']++;
        $stats['last_event_at'] = current_time('mysql');

        update_option(self::OPTION_KEY, $stats, false);
        wp_send_json_success(array('recorded' => true));
    }
}

new MON_Portfolio_Generator();
