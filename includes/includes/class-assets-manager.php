<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing the loading of scripts and styles.
 * Ensures proper styles and scripts are enqueued for specific pages.
 *
 * @author  Alex Kovalev <alex.kovalevv@gmail.com> <Telegram:@alex_kovalevv>
 * @copyright (c) 09.01.2025, CreativeMotion
 */
class PaywayAssetsManager
{

    /**
     * Base URL of the plugin.
     */
    private string $base_url;

    /**
     * Constructor. Sets up the hook for enqueuing scripts and styles.
     */
    public function __construct()
    {
        $this->base_url = PAYWAY_PLUGIN_URL; // Общая часть URL для всех файлов
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueues scripts and styles for the current page if it matches the target pages.
     */
    public function enqueue_assets()
    {
        if ($this->is_target_page()) {
            $this->enqueue_common_assets();
            $this->enqueue_page_specific_assets();
        }
    }

    /**
     * Checks if the current page is one of the predefined target pages.
     *
     * @return bool True if the current page is a target page, otherwise false.
     */
    private function is_target_page(): bool
    {
        return is_page(['login', 'account', 'projects', 'profile', 'unlocksum', 'stats']);
    }

    /**
     * Enqueues common styles and scripts that are needed across multiple pages.
     */
    private function enqueue_common_assets()
    {
        $this->enqueue_style('payway-style', 'style.css', []);
        $this->enqueue_style('payway-mobile-menu', 'assets/css/mobile-menu.css', []);

        $this->enqueue_script('payway-just-validate', 'assets/js/libs/just-validate.production.min.js', []);

        $this->enqueue_script('payway-general', 'assets/js/general.js', ['jquery']);

        wp_localize_script('payway-general', 'payway_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * Enqueues page-specific scripts for certain predefined pages.
     */
    private function enqueue_page_specific_assets()
    {
        if (is_page('profile')) {
            $this->enqueue_script('payway-profile', 'assets/js/profile.js', ['jquery']);
        }

        if (is_page('projects')) {
            $this->enqueue_script('payway-projects', 'assets/js/projects.js', ['jquery']);
        }

        if (is_page('stats')) {
            $this->enqueue_style('payway-stats', 'assets/css/stats.css', []);
        }
    }

    /**
     * Method for enqueuing styles.
     *
     * @param string $handle A unique identifier for the style.
     * @param string $relative_path The relative path to the style file.
     * @param array $deps Style dependencies.
     * @param string $media Media attribute (e.g., 'all').
     */
    private function enqueue_style(string $handle, string $relative_path, array $deps = [], string $media = 'all')
    {
        wp_enqueue_style($handle, $this->base_url . '/' . $relative_path, $deps, PAYWAY_PLUGIN_VERSION, $media);
    }

    /**
     * Method for enqueuing scripts.
     *
     * @param string $handle A unique identifier for the script.
     * @param string $relative_path The relative path to the script file.
     * @param array $deps Script dependencies.
     * @param bool $in_footer Whether the script should be loaded in the footer.
     */
    private function enqueue_script(string $handle, string $relative_path, array $deps = [], bool $in_footer = false)
    {
        wp_enqueue_script($handle, $this->base_url . '/' . $relative_path, $deps, PAYWAY_PLUGIN_VERSION, $in_footer);
    }
}

// Инициализация класса
new PaywayAssetsManager();