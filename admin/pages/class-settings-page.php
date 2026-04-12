<?php
/**
 * Sprint 6: Admin settings page — YouTube & OpenAI API keys.
 *
 * Stores keys in wp_options:
 *   payway_youtube_api_key
 *   payway_openai_api_key
 *
 * @package PaywayPersonal
 */

namespace Payway\Pages;

defined( 'ABSPATH' ) || exit;

class SettingsPage {

	const MENU_SLUG    = 'payway-settings';
	const OPTION_YT    = 'payway_youtube_api_key';
	const OPTION_AI    = 'payway_openai_api_key';
	const OPTION_GROUP = 'payway_api_settings';

	public static function init(): void {
		$instance = new self();
		add_action( 'admin_menu', [ $instance, 'register_menu' ] );
		add_action( 'admin_init', [ $instance, 'register_settings' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'payway-cabinet',
			'Настройки API — Payway',
			'Настройки API',
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( self::OPTION_GROUP, self::OPTION_YT, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );
		register_setting( self::OPTION_GROUP, self::OPTION_AI, [
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		] );

		add_settings_section(
			'payway_api_section',
			'API ключи для аудита каналов',
			[ $this, 'section_description' ],
			self::MENU_SLUG
		);

		add_settings_field( self::OPTION_YT, 'YouTube Data API v3', [ $this, 'field_youtube' ], self::MENU_SLUG, 'payway_api_section' );
		add_settings_field( self::OPTION_AI, 'OpenAI API Key', [ $this, 'field_openai' ], self::MENU_SLUG, 'payway_api_section' );
	}

	public function section_description(): void {
		echo '<p>Ключи хранятся в БД WordPress, доступны только администратору. Не попадают в исходный код.</p>';
	}

	public function field_youtube(): void {
		$val = esc_attr( (string) get_option( self::OPTION_YT, '' ) );
		printf( '<input type="password" id="%1$s" name="%1$s" value="%2$s" class="regular-text pw-api-key" autocomplete="new-password" /> <button type="button" class="button pw-toggle-key" data-target="%1$s">Показать</button><p class="description">Google Cloud Console → YouTube Data API v3 → Credentials.</p>', self::OPTION_YT, $val );
	}

	public function field_openai(): void {
		$val = esc_attr( (string) get_option( self::OPTION_AI, '' ) );
		printf( '<input type="password" id="%1$s" name="%1$s" value="%2$s" class="regular-text pw-api-key" autocomplete="new-password" /> <button type="button" class="button pw-toggle-key" data-target="%1$s">Показать</button><p class="description">OpenAI Platform → API keys → Create new secret key.</p>', self::OPTION_AI, $val );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Недостаточно прав.' ); }
		?>
		<div class="wrap">
		<h1>Настройки API — Payway</h1>
		<form method="post" action="options.php">
		<?php settings_fields( self::OPTION_GROUP ); do_settings_sections( self::MENU_SLUG ); submit_button( 'Сохранить ключи' ); ?>
		</form>
		</div>
		<script>
		document.querySelectorAll('.pw-toggle-key').forEach(function(b){
			b.addEventListener('click',function(){
				var i=document.getElementById(this.dataset.target);
				if(i.type==='password'){i.type='text';this.textContent='Скрыть';}
				else{i.type='password';this.textContent='Показать';}
			});
		});
		</script>
		<?php
	}
}