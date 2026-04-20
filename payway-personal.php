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

add_action( 'rest_api_init', function () { $c = new PW_Audit_REST(); $c->register_routes(); } );
PW_Audit_Cron::register_hooks();
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

// ── Early cookie auth (priority 1 — до формирования nonce в wp_head) ─────────
add_action( 'init', function () {
    if ( is_user_logged_in() ) return;
    foreach ( $_COOKIE as $name => $val ) {
        if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
            $uid = wp_validate_auth_cookie( $val, 'logged_in' );
            if ( $uid ) {
                wp_set_current_user( $uid );
                break;
            }
        }
    }
}, 1 );

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
// Cookie-auth для всех payway/v1 endpoints (обходит ограничения REST API для обычных пользователей)
add_filter( 'rest_authentication_errors', function ( $result ) {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/payway/v1/' ) === false ) return $result;
    if ( get_current_user_id() ) return null; // уже аутентифицирован
    foreach ( $_COOKIE as $name => $val ) {
        if ( strpos( $name, 'wordpress_logged_in_' ) === 0 ) {
            $uid = wp_validate_auth_cookie( $val, 'logged_in' );
            if ( $uid ) { wp_set_current_user( $uid ); return null; }
        }
    }
    return $result; // не блокируем, возвращаем исходный результат
}, 150 );

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

// -- Audit nonce injection via wp_head (fetch interceptor) ----------------
add_action( 'wp_head', function () {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/audit' ) === false ) return;
    $nonce = wp_create_nonce( 'wp_rest' );
    $is_admin = current_user_can( 'manage_options' ) ? 'true' : 'false';
echo '<script>window.paywayAuditCfg={nonce:"' . esc_js( $nonce ) . '",is_admin:' . $is_admin . '};' .
         'window.__paywayFetchPatched||(window.__paywayFetchPatched=1,(function(){' .
         'var oF=window.fetch;window.fetch=function(u,o){' .
         'if(typeof u==="string"&&u.indexOf("/payway/v1/")>-1){' .
         'o=Object.assign({},o||{});var h=o.headers||{};' .
         'if(h instanceof Headers){h=Object.fromEntries(h.entries());}' .
         'h["X-WP-Nonce"]=(window.paywayAuditCfg&&window.paywayAuditCfg.nonce)||"";' .
         'o.headers=h;}return oF.call(this,u,o);}})());</script>';
} );

add_action( 'wp_enqueue_scripts', function () {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/audit' ) === false ) {
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
    // fetch_interceptor: auto-inject WP REST nonce into payway API calls
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

// -- Audit UI v3: direct script src inject (bypasses wp_enqueue handle) --
add_action( 'wp_footer', function () {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/audit' ) === false ) return;
    $url = plugin_dir_url( __FILE__ ) . 'assets/audit-ui-inject.js?ver=8.8';
    echo '<script src="' . esc_url( $url ) . '"></script>' . "\n";
}, 5 );

add_action( 'wp_footer', 'payway_inject_audit_history_loader_v2' );
add_action( 'wp_footer', function () {
    if ( strpos( $_SERVER['REQUEST_URI'] ?? '', '/create-withdrawal' ) === false ) return;
 
    // Тариф: регистрация ДО 07.04.2026 → 10%, с 07.04.2026 → 11%
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