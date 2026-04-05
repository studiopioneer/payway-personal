<?php
/*
Plugin Name: Payway - личный кабинет
Plugin URI:  http://yourwebsite.com/my-custom-plugin
Description: Добавляет авторизацию и личный кабинет пользователя
Version:     4.4
Author:      Rus, Alex Kovalev
Author URI:  null
License:     GPL2
*/

// Безопасность - предотвратить прямой доступ к файлу
if (!defined('ABSPATH')) {
    exit; // Выход, если напрямую обращаются к файлу
}

define('PAYWAY_PLUGIN_VERSION', '4.3');
define('PAYWAY_PLUGIN_FILE', __FILE__);
define('PAYWAY_ABSPATH', dirname(__FILE__));
define('PAYWAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PAYWAY_PLUGIN_SLUG', dirname(plugin_basename(__FILE__)));
define('PAYWAY_PLUGIN_URL', plugins_url('', __FILE__));
define('PAYWAY_PLUGIN_DIR', dirname(__FILE__));

// Предварительная настройка плагина, при активации
require_once(PAYWAY_PLUGIN_DIR . '/functions.php');
require_once(PAYWAY_PLUGIN_DIR . '/activation.php');

// Управление ресурсами плагина
require_once(PAYWAY_PLUGIN_DIR . '/includes/class-assets-manager.php');

// Ajax
require_once(PAYWAY_PLUGIN_DIR . '/admin/ajax-handlers.php');
require_once(PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/profile.php');
require_once(PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/projects.php');
require_once(PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/unlock.php');
require_once(PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/withdrawal.php');

// Rest
require_once(PAYWAY_PLUGIN_DIR . '/includes/class-rest-api.php');

add_filter('show_admin_bar', function ($show) {
    return current_user_can('administrator');
});


add_action('admin_menu', function () {
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-admin-pages.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-projects-page.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-unlock-page.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-withdrawal-page.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-stats-page.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-income-page.php');
    require_once(PAYWAY_PLUGIN_DIR . '/admin/pages/class-users-columns.php');

    Payway\Pages\ProjectsPage::init();
    Payway\Pages\UnlockPage::init();
    Payway\Pages\WithdrawalPage::init();
    Payway\Pages\StatsPage::init();
    Payway\Pages\ReferralPage::init();
});

add_filter('template_include', function ($template) {
    $template_map = [
        'login' => 'account.php',
        'account' => 'account.php',
        'profile' => 'account.php',
        'projects' => 'account.php',
        'unlock' => 'account.php',
        'stats' => 'account.php',
    ];

    $dir = plugin_dir_path(__FILE__) . '/pages/';

    foreach ($template_map as $page => $file) {
        if (is_page($page)) {
            $template_path = $dir . $file;
            if (file_exists($template_path)) {
                return $template_path;
            }
        }
    }

    return $template;
});

add_action('template_redirect', function () {
    if (is_page('account')) {
        if (is_user_logged_in()) {
            if (!current_user_can('read')) {
                wp_die('У вас недостаточно прав для просмотра этой страницы!');
            }
        } else {
            wp_redirect(site_url() . '/login');
        }
    }
});


