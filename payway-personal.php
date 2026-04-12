<?php
/*
Plugin Name: Payway - личный кабинет
Plugin URI:  http://yourwebsite.com/my-custom-plugin
Description: Добавляет авторизацию и личный кабинет пользователя
Version:     7.0
Author:      Rus, Alex Kovalev
Author URI:  null
License:     GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAYWAY_PLUGIN_VERSION',  '7.0' );
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

add_action( 'rest_api_init', [ 'PW_Audit_REST', 'register_routes' ] );
PW_Audit_Cron::register_hooks();
// ── Admin settings page (API keys) ──────────────────────────────────────────
if ( is_admin() ) {
	require_once PAYWAY_PLUGIN_DIR . '/admin/pages/class-settings-page.php';
	Payway\\Pages\\SettingsPage::init();
}


// ── Admin bar ─────────────────────────────────────────────────────────────────
add_filter( 'show_admin_bar', fn( $show ) => current_user_can( 'administrator' ) );

// ── Admin pages ───────────────────────────────────────────────────────────────
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