<?php
/*
Plugin Name: redirectME
Description: Mengalihkan pengunjung saat pertama kali membuka website ke halaman tertentu, dengan pengaturan redirect dan waktu timeout. Berguna untuk pengaturan "splash page" sebelum masuk.
Version: 1.0
Author: fahmiyufrizal
Author URI: https://github.com/fahmiyufrizal
*/

if (!defined('ABSPATH')) exit; // Stop direct access

class redirectME {

    private $option_name = 'fvr_settings';

    public function __construct() {
        add_action('template_redirect', [$this, 'maybe_redirect']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notice_missing_url']);
    }

    public function maybe_redirect() {
        if (is_admin() || is_user_logged_in()) {
            return;
        }

        $settings = get_option($this->option_name);

        $redirect_url = isset($settings['redirect_url']) ? esc_url($settings['redirect_url']) : '';
        $timeout_value = isset($settings['timeout_value']) ? intval($settings['timeout_value']) : 60;
        $timeout_unit = isset($settings['timeout_unit']) ? sanitize_text_field($settings['timeout_unit']) : 'minutes';
        $only_homepage = isset($settings['only_homepage']) ? boolval($settings['only_homepage']) : false;

        if (empty($redirect_url)) return;

        if ($only_homepage && !(is_front_page() || is_home())) {
            return;
        }

        if (!isset($_COOKIE['fvr_redirected'])) {
            $timeout_seconds = $this->convert_to_seconds($timeout_value, $timeout_unit);
            setcookie('fvr_redirected', '1', time() + $timeout_seconds, COOKIEPATH, COOKIE_DOMAIN);
            wp_redirect($redirect_url);
            exit;
        }
    }

    private function convert_to_seconds($value, $unit) {
        switch ($unit) {
            case 'hours':
                return $value * 3600;
            case 'days':
                return $value * 86400;
            case 'minutes':
            default:
                return $value * 60;
        }
    }

    public function add_settings_page() {
        add_options_page(
            'redirectME',
            'redirectME',
            'manage_options',
            'redirectme',
            [$this, 'settings_page_html'],
            'dashicons-randomize'
        );
    }

    public function register_settings() {
        register_setting('fvr_settings_group', $this->option_name);

        add_settings_section(
            'fvr_main_section',
            'Pengaturan Redirect',
            null,
            'redirectme'
        );

        add_settings_field(
            'redirect_url',
            'URL Redirect',
            [$this, 'redirect_url_field_html'],
            'redirectme',
            'fvr_main_section'
        );

        add_settings_field(
            'timeout_value',
            'Timeout Redirect',
            [$this, 'timeout_value_field_html'],
            'redirectme',
            'fvr_main_section'
        );

        add_settings_field(
            'only_homepage',
            'Redirect Hanya di Homepage',
            [$this, 'only_homepage_field_html'],
            'redirectme',
            'fvr_main_section'
        );
    }

    public function redirect_url_field_html() {
        $settings = get_option($this->option_name);
        ?>
        <input type="text" name="fvr_settings[redirect_url]" value="<?php echo isset($settings['redirect_url']) ? esc_url($settings['redirect_url']) : ''; ?>" size="50" />
        <p class="description">Contoh: <?php echo home_url('/dashboard'); ?></p>
        <?php
    }

    public function timeout_value_field_html() {
        $settings = get_option($this->option_name);
        $timeout_value = isset($settings['timeout_value']) ? intval($settings['timeout_value']) : 60;
        $timeout_unit = isset($settings['timeout_unit']) ? $settings['timeout_unit'] : 'minutes';
        ?>
        <input type="number" name="fvr_settings[timeout_value]" value="<?php echo $timeout_value; ?>" min="1" />
        <select name="fvr_settings[timeout_unit]">
            <option value="minutes" <?php selected($timeout_unit, 'minutes'); ?>>Menit</option>
            <option value="hours" <?php selected($timeout_unit, 'hours'); ?>>Jam</option>
            <option value="days" <?php selected($timeout_unit, 'days'); ?>>Hari</option>
        </select>
        <p class="description">Tentukan durasi timeout agar user tidak redirect terus menerus</p>
        <?php
    }

    public function only_homepage_field_html() {
        $settings = get_option($this->option_name);
        $only_homepage = isset($settings['only_homepage']) ? boolval($settings['only_homepage']) : false;
        ?>
        <input type="checkbox" name="fvr_settings[only_homepage]" value="1" <?php checked($only_homepage, 1); ?> />
        <label>Redirect homepage saja</label>
        <?php
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Pengaturan redirectME</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fvr_settings_group');
                do_settings_sections('redirectme');
                submit_button('Simpan Pengaturan');
                ?>
            </form>
        </div>
        <?php
    }

    public function admin_notice_missing_url() {
        if (!current_user_can('manage_options')) return;

        $screen = get_current_screen();
        if ($screen->id !== 'settings_page_redirectme') return;

        $settings = get_option($this->option_name);
        if (empty($settings['redirect_url'])) {
            echo '<div class="notice notice-warning is-dismissible">
                    <p><strong>redirectME:</strong> Anda belum mengatur URL tujuan redirect. Silakan isi URL untuk mengaktifkan fitur redirect.</p>
                  </div>';
        }
    }
}

new redirectME();
?>