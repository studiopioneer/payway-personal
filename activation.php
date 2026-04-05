<?php
if (!defined('ABSPATH')) {
    exit; // Выход, если напрямую обращаются к файлу
}

/**
 * Класс-активатор для включения функционала плагина Payway.
 * Отвечает за создание таблиц, обновление типов колонок и начальную настройку.
 *
 * @package Payway
 */
class Payway_Activator
{
    /**
     * Получить текущую кодировку и параметры коллатинга для базы данных.
     *
     * @return string Кодировка и параметры коллатинга.
     */
    private static function get_charset_collate(): string
    {
        global $wpdb;
        return $wpdb->get_charset_collate();
    }

    /**
     * Активирует плагин, создавая необходимые таблицы,
     * обновляя типы колонок и регистрируя админ-страницы.
     */
    public static function activate(): void
    {
        self::create_tables();
        self::update_column_enum();
        self::create_admin_pages();
    }

    /**
     * Создает таблицы базы данных, необходимые для работы плагина.
     *
     * Использует dbDelta для управления схемой базы данных.
     *
     * @global wpdb $wpdb Глобальный объект базы данных WordPress.
     */
    private static function create_tables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = self::get_charset_collate();

        // Определения схемы таблиц
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_projects (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                url varchar(255) DEFAULT '' NOT NULL,
                amount varchar(55) DEFAULT 0 NOT NULL,
                count_users int(11) DEFAULT 0 NOT NULL,
                comments text DEFAULT '' NOT NULL,
                review_comments text DEFAULT '' NOT NULL,
                contacts varchar(255) DEFAULT '' NOT NULL,
                status enum('approved', 'review', 'rejected') DEFAULT 'review' NOT NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_withdrawal (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                payment_type enum('swift', 'cryptocurrency', 'cards') DEFAULT 'cards' NOT NULL,
                payment_details varchar(255) DEFAULT '' NOT NULL,
                amount decimal(11,2) NOT NULL,
                comments text DEFAULT '' NOT NULL,
                review_comments text DEFAULT '' NOT NULL,
                status enum('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_unlock (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id int(11) NOT NULL,
                amount decimal(11,2) NOT NULL,
                review_comments text DEFAULT '' NOT NULL,
                status enum('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;",
            "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_stats (
                id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
                project_id MEDIUMINT(9) NOT NULL,
                site VARCHAR(255) NOT NULL,
                estimated_earnings_usd DECIMAL(11, 2) NOT NULL,
                page_views INT(11) NOT NULL,
                page_rpm_usd DECIMAL(11, 2) NOT NULL,
                impressions INT(11) NOT NULL,
                impression_rpm_usd DECIMAL(11, 2) NOT NULL,
                active_view_viewable DECIMAL(5, 2) NOT NULL,
                clicks INT(11) NOT NULL,
                date_start DATE NOT NULL,
                date_end DATE NOT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (project_id) REFERENCES {$wpdb->prefix}payway_projects(id) ON DELETE CASCADE
            ) $charset_collate;"

    ];

        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_referrals (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id BIGINT(20) UNSIGNED NOT NULL,
            referral_email VARCHAR(255) NOT NULL,
            referral_code VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY referral_code (referral_code)
        ) $charset_collate;";

        // Выполняем SQL для каждой таблицы
        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    /**
     * Обновляет типы колонок enum в таблицах базы данных, добавляя недостающие значения.
     *
     * Добавляет значение 'paid' в колонку 'status', если его нет.
     *
     * @global wpdb $wpdb Глобальный объект базы данных WordPress.
     */
    private static function update_column_enum(): void
    {
        global $wpdb;

        // Новый ENUM-тип для колонки
        $new_enum_type = "ENUM('approved', 'review', 'rejected', 'paid') DEFAULT 'review' NOT NULL";

        // Список таблиц и колонок для обновления
        $tables = [
            'payway_unlock' => 'status',
            'payway_withdrawal' => 'status'

    ];

        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_referrals (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id BIGINT(20) UNSIGNED NOT NULL,
            referral_email VARCHAR(255) NOT NULL,
            referral_code VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY referral_code (referral_code)
        ) $charset_collate;";

        // Проверяем и обновляем каждую таблицу
        foreach ($tables as $table_suffix => $column_name) {
            $table_name = $wpdb->prefix . $table_suffix;

            // Проверяем существование колонки
            $query = $wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", $column_name);
            $column = $wpdb->get_row($query);

            // Если колонка существует и не содержит 'paid', выполняем обновление
            if ($column && isset($column->Type) && strpos($column->Type, "'paid'") === false) {
                $wpdb->query("ALTER TABLE {$table_name} MODIFY {$column_name} {$new_enum_type}");
            }
        }
    }


    /**
     * Создает административные страницы, проверяя, существуют ли они,
     * и добавляет их, если они еще не созданы.
     *
     * Проходит по заранее заданному списку страниц, проверяет существование
     * каждой страницы по ее slug, и добавляет страницу в базу данных,
     * если она еще не была создана.
     *
     * @return void
     */
    private static function create_admin_pages(): void
    {
        $pages = [
            ['slug' => 'account', 'title' => 'Payway личный кабинет'],
            ['slug' => 'unlock', 'title' => 'Разблокировка счета'],
            ['slug' => 'profile', 'title' => 'Профайл'],
            ['slug' => 'projects', 'title' => 'Мои проекты'],
            ['slug' => 'stats', 'title' => 'Статистика']

    ];

        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}payway_referrals (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_id BIGINT(20) UNSIGNED NOT NULL,
            referral_email VARCHAR(255) NOT NULL,
            referral_code VARCHAR(32) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY referrer_id (referrer_id),
            KEY referral_code (referral_code)
        ) $charset_collate;";

        foreach ($pages as $page) {
            $existing_page = get_page_by_path($page['slug']);
            if (!isset($existing_page->ID)) {
                wp_insert_post([
                    'post_title' => $page['title'],
                    'post_name' => $page['slug'],
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                ]);
            }
        }
    }
}

// Регистрация хука активации плагина
register_activation_hook(PAYWAY_PLUGIN_FILE, ['Payway_Activator', 'activate']);