<?php
/*
Plugin Name: Payway - личный кабинет
Plugin URI:  http://yourwebsite.com/my-custom-plugin
Description: Добавляет авторизацию и личный кабинет пользователя
Version:     8.0
Author:      Rus, Alex Kovalev
Author URI:  null
License:     GPL2
*/
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
define( 'PAYWAY_PLUGIN_VERSION',  '8.0' );
define( 'PAYWAY_PLUGIN_FILE',     __FILE__ );
define( 'PAYWAY_ABSPATH',         dirname( __FILE__ ) );
define( 'PAYWAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PAYWAY_PLUGIN_SLUG',     dirname( plugin_basename( __FILE__ ) ) );
define( 'PAYWAY_PLUGIN_URL',      plugins_url( '', __FILE__ ) );
define( 'PAYWAY_PLUGIN_DIR',      dirname( __FILE__ ) );
 
// ── Core ──────────────────────────────────────────────────────────────────────
require_once PAYWAY_PLUGIN_DIR . '/functions.php';
require_once PAYWAY_PLUGIN_DIR . '/activation.php';
 
// ── Assets ────────────────────────────────────────────────────────────────────
require_once PAYWAY_PLUGIN_DIR . '/includes/class-assets-manager.php';
 
// ── Ajax ──────────────────────────────────────────────────────────────────────
require_once PAYWAY_PLUGIN_DIR . '/admin/ajax-handlers.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/profile.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/projects.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/unlock.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/ajax-handlers/withdrawal.php';
 
// ── REST API ──────────────────────────────────────────────────────────────────
require_once PAYWAY_PLUGIN_DIR . '/includes/class-rest-api.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/controllers/class-referral-controller.php';
 
// ── Channel Audit: Sprint 1 (DB + PHP classes) ────────────────────────────────
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-db.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-rate-limiter.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-youtube-api.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-analyzer.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-repository.php';
//  Channel Audit: Sprint 2 (OpenAI + REST API + Cron) 
require_once PAYWAY_PLUGIN_DIR . '/includes/class-openai-client.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-credit.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-rest.php';
require_once PAYWAY_PLUGIN_DIR . '/includes/class-audit-cron.php';
// ── JWT Authentication (встроенная, замена внешнего плагина) ──────────────────
require_once PAYWAY_PLUGIN_DIR . '/includes/class-jwt-auth.php';
PW_JWT_Auth::init();
 
add_action( 'rest_api_init', function () { $c = new PW_Audit_REST(); $c->register_routes(); } );
PW_Audit_Cron::register_hooks();
// ── v4.9: Создание таблицы donations для существующих установок ──────────────
add_action( 'init', function () {
    if ( get_option( 'payway_donations_table_created' ) ) return;
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_donations (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     BIGINT(20) UNSIGNED NOT NULL,
        amount      DECIMAL(11,2) NOT NULL,
        message     TEXT DEFAULT '',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) {$charset_collate};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'payway_donations_table_created', 1 );
} );
// ── Admin settings page (API keys) ──────────────────────────────────────────
if ( is_admin() ) {
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-settings-page.php';
	Payway\Pages\SettingsPage::init();
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-audit-admin.php';
	Payway\Pages\AuditAdminPage::init();
}
 
 
// ── Admin bar ─────────────────────────────────────────────────────────────────
add_filter( 'show_admin_bar', fn( $show ) => current_user_can( 'administrator' ) );
 
// ── Admin pages ───────────────────────────────────────────────────────────────
// ── Admin parent menu (priority 9 — fires before submenu registrations) ────────
add_action( 'admin_menu', function () {
	add_menu_page(
		'PW Кабинет',
		'PW Кабинет',
		'manage_options',
		'payway-cabinet',
		'',
		'dashicons-admin-multisite',
		100
	);
}, 9 );
add_action( 'admin_menu', function () {
	remove_submenu_page( 'payway-cabinet', 'payway-cabinet' );
}, 11 );
 
 
add_action( 'admin_menu', function () {
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-admin-pages.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-projects-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-unlock-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-withdrawal-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-stats-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-income-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-users-columns.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-referral-page.php';
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-donations-page.php';
	Payway\Pages\DonationsPage::init();
	Payway\Pages\ProjectsPage::init();
	Payway\Pages\UnlockPage::init();
	Payway\Pages\WithdrawalPage::init();
	Payway\Pages\StatsPage::init();
	Payway\Pages\ReferralPage::init();
} );
 
// ── Template routing ──────────────────────────────────────────────────────────
add_filter( 'template_include', function ( $template ) {
	$template_map = [
		'login'             => 'account.php',
		'account'           => 'account.php',
		'profile'           => 'account.php',
		'projects'          => 'account.php',
		'unlock'            => 'account.php',
		'stats'             => 'account.php',
		'create-withdrawal' => 'account.php',
            'audit'             => 'account.php',
	];
 
	$dir = plugin_dir_path( __FILE__ ) . '/pages/';
 
	foreach ( $template_map as $page => $file ) {
		if ( is_page( $page ) ) {
			$path = $dir . $file;
			if ( file_exists( $path ) ) {
				return $path;
			}
		}
	}
 
	return $template;
} );
 
// ── Early cookie auth для обычных страниц (nonce создаётся для правильного юзера) ──
// Только для НЕ-REST запросов. Для REST — хук determine_current_user ниже.
add_action( 'init', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ( strpos( $uri, '/wp-json/' ) !== false ) return; // REST обрабатывается отдельно
    if ( is_user_logged_in() ) return;
    foreach ( $_COOKIE as $name => $val ) {
        if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
            $uid = wp_validate_auth_cookie( $val, 'logged_in' );
            if ( $uid ) { wp_set_current_user( $uid ); break; }
        }
    }
}, 1 );
 
// ── Cookie auth для REST API через determine_current_user ─────────────────────
add_filter( 'determine_current_user', function ( $user_id ) {
    if ( $user_id ) return $user_id;
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ( strpos( $uri, '/payway/v1/' ) === false ) return $user_id;
    foreach ( $_COOKIE as $name => $val ) {
        if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
            $uid = wp_validate_auth_cookie( $val, 'logged_in' );
            if ( $uid ) return $uid;
        }
    }
    return $user_id;
}, 20 );
 
// ── Auth redirect ─────────────────────────────────────────────────────────────
add_action( 'template_redirect', function () {
	if ( is_page( 'account' ) ) {
		if ( is_user_logged_in() ) {
			if ( ! current_user_can( 'read' ) ) {
				wp_die( 'У вас недостаточно прав для просмотра этой страницы!' );
			}
		} else {
			wp_redirect( site_url() . '/login' );
			exit;
		}
	}
} );
// ── Запасной фильтр: сбрасываем ошибки аутентификации для наших endpoints ─────
add_filter( 'rest_authentication_errors', function ( $result ) {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/payway/v1/' ) === false ) return $result;
    if ( ! get_current_user_id() ) {
        $token = $_SERVER['HTTP_X_PAYWAY_TOKEN'] ?? '';
        if ( $token ) {
            $uid = get_transient( 'payway_tok_' . md5( $token ) );
            if ( $uid ) {
                wp_set_current_user( (int) $uid );
            }
        }
    }
    if ( get_current_user_id() ) return null;
    return $result;
}, 200 );
 
 
add_action( 'wp_ajax_payway_fresh_nonce', function () {
    wp_send_json_success( [
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'is_admin' => current_user_can( 'manage_options' ),
    ] );
} );
 
add_action( 'send_headers', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ( strpos( $uri, '/audit' ) !== false || strpos( $uri, '/account' ) !== false ) {
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
    }
} );
  
// ── Audit UI v2: CSS + JS ──────────────────────────────────────
 
// Helper: все SPA-страницы (audit-inject.js должен быть загружен на всех них,
// чтобы работал при SPA-навигации от /account → /audit и т.д.)
function payway_is_spa_page() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return (bool) preg_match( '#/(audit|account|profile|projects|unlock|stats|create-withdrawal|login)(\?|/|$)#', $uri );
}
 
// -- Audit nonce + authToken injection via wp_head -------------------------
add_action( 'wp_head', function () {
    // Загружаем на всех SPA-страницах — скрипт нужен при навигации /account → /audit
    if ( ! payway_is_spa_page() ) return;
    $nonce    = wp_create_nonce( 'wp_rest' );
    $is_admin = current_user_can( 'manage_options' ) ? 'true' : 'false';
    $auth_token_js = 'null';
    $uid = get_current_user_id();
    if ( $uid ) {
        $token = wp_generate_password( 32, false );
        set_transient( 'payway_tok_' . md5( $token ), $uid, 2 * HOUR_IN_SECONDS );
        $auth_token_js = '"' . esc_js( $token ) . '"';
    }
echo '<script>window.paywayAuditCfg={nonce:"' . esc_js( $nonce ) . '",is_admin:' . $is_admin . ',authToken:' . $auth_token_js . '};' .
         'window.__paywayFetchPatched||(window.__paywayFetchPatched=1,(function(){' .
         'var oF=window.fetch;window.fetch=function(u,o){' .
         'if(typeof u==="string"&&u.indexOf("/payway/v1/")>-1){' .
         'o=Object.assign({},o||{});var h=o.headers||{};' .
         'if(h instanceof Headers){h=Object.fromEntries(h.entries());}' .
         'h["X-WP-Nonce"]=(window.paywayAuditCfg&&window.paywayAuditCfg.nonce)||"";' .
         'h["X-Payway-Token"]=(window.paywayAuditCfg&&window.paywayAuditCfg.authToken)||"";' .
         'o.headers=h;}return oF.call(this,u,o);}})());</script>';
 
    // Cloak Vue content on /audit pages to prevent flash before inject.js takes over
    if ( preg_match( '#/audit#', $uri ) ) {
        echo '<style>.pw-preload [data-v-app] .col:not(.col-fixed)>div>*:not(#pw-audit-inject):not(#pw-audit-landing){display:none!important}</style>';
        echo '<script>document.body.classList.add("pw-preload");</script>';
    }
} );
 
add_action( 'wp_enqueue_scripts', function () {
    // Загружаем на всех SPA-страницах
    if ( ! payway_is_spa_page() ) {
        return;
    }
    wp_enqueue_style(
        'payway-audit-ui',
        plugin_dir_url( __FILE__ ) . 'assets/audit-ui-inject.css',
        [], '2.0'
    );
    wp_enqueue_script(
        'payway-audit-ui',
        plugin_dir_url( __FILE__ ) . 'assets/audit-ui-inject.js',
        [], '2.0', true
    );
    wp_localize_script( 'payway-audit-ui', 'paywayAuditCfg', [ 'nonce' => wp_create_nonce( 'wp_rest' ), 'is_admin' => current_user_can( 'manage_options' ) ] );
    wp_add_inline_script(
        'payway-audit-ui',
        'window.__paywayFetchPatched||(window.__paywayFetchPatched=1,(function(){var oF=window.fetch;window.fetch=function(u,o){if(typeof u==="string"&&u.indexOf("/payway/v1/")>-1){o=Object.assign({},o||{});var h=o.headers||{};if(h instanceof Headers){h=Object.fromEntries(h.entries());}h["X-WP-Nonce"]=(window.paywayAuditCfg&&window.paywayAuditCfg.nonce)||"";o.headers=h;}return oF.call(this,u,o);}})());',
        'before'
    );
});
 
// ── Audit history loader v2 (footer injection) ─────────────────
function payway_inject_audit_history_loader_v2() {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/audit' ) === false ) return;
    ?>
    <script>
    (function(){
        var id = new URLSearchParams(location.search).get('id');
        if (!id) return;
        function getStore() {
            try {
                var el = document.querySelector('[data-v-app]');
                if (!el || !el.__vue_app__) return null;
                var pinia = el.__vue_app__.config.globalProperties.$pinia;
                if (!pinia || !pinia._s) return null;
                return pinia._s.get('audit');
            } catch(e) { return null; }
        }
        function tryLoad(n) {
            if (n <= 0) return;
            var s = getStore();
            if (!s) { setTimeout(function(){ tryLoad(n-1); }, 400); return; }
            if (s.report && s.report.id === parseInt(id)) return;
            s.auditId = parseInt(id);
            if (typeof s.pollStatus === 'function') s.pollStatus();
        }
        setTimeout(function(){ tryLoad(25); }, 800);
    })();
    </script>
    <?php
}
 
// -- Audit UI v3: direct script src inject (на всех SPA-страницах) --
add_action( 'wp_footer', function () {
    // Загружаем на всех SPA-страницах — без этого скрипт не работает при SPA-навигации
    if ( ! payway_is_spa_page() ) return;
    $url = plugin_dir_url( __FILE__ ) . 'assets/audit-ui-inject.js?ver=9.5';
    echo '<script src="' . esc_url( $url ) . '"></script>' . "\n";
}, 5 );
 
add_action( 'wp_footer', 'payway_inject_audit_history_loader_v2' );
add_action( 'wp_footer', function () {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/create-withdrawal' ) === false ) return;
 
    $tariff = 11;
    if ( is_user_logged_in() ) {
        $user = get_userdata( get_current_user_id() );
        if ( $user ) {
            $cutoff     = strtotime( '2026-04-07 00:00:00' );
            $registered = strtotime( $user->user_registered );
            $tariff     = ( $registered < $cutoff ) ? 10 : 11;
        }
    }
 
    $url = plugin_dir_url( __FILE__ ) . 'assets/withdrawal-tariff-inject.js?ver=1.1';
    echo '<script>window.paywayWithdrawalCfg={cryptoTariff:' . intval( $tariff ) . '};</script>' . "\n";
    echo '<script src="' . esc_url( $url ) . '"></script>' . "\n";
}, 5 );
