<?php

namespace Payway\Pages;

if (!defined('ABSPATH')) {
    exit; // Выход, если напрямую обращаются к файлу
}

/**
 * Base abstract class for creating WordPress admin pages.
 * Provides common functionality for creating and managing admin pages
 * including registration, rendering, and asset (styles/scripts) loading.
 *
 * @author  Alex Kovalev
 */
abstract class AbstractAdminPage
{

    /**
     * @var string Page title displayed in the admin dashboard.
     */
    protected string $page_title;

    /**
     * @var string Menu title displayed in the admin menu.
     */
    protected string $menu_title;

    /**
     * @var string Unique menu slug used to identify the page.
     */
    protected string $menu_slug;

    /**
     * @var array Array of columns with custom widths for the admin table.
     */
    protected array $columns = [];

    /**
     * @var string Path to the file that contains the table class for the page.
     */
    protected string $list_table_file;

    /**
     * @var string The name of the custom class responsible for rendering the admin table.
     */
    protected string $list_table_class_name;

    /**
     * @var string The name of the database table being interacted with.
     */
    protected string $db_table_name;

    /**
     * Constructor for the admin page class.
     * Initializes page settings from the child class.
     */
    public function __construct()
    {
        // Initialize page settings from the child class
        $this->init_page_settings();

        if (isset($_GET['page']) && $_GET['page'] === $this->menu_slug) {
            $this->handle_edit_form();
            $this->handle_delete_action();
        }
    }

    /**
     * Abstract method to define page settings.
     * Must be implemented in the child class.
     */
    abstract protected function init_page_settings(): void;

    /**
     * Renders the edit form for a specific record.
     *
     * @param int $id The ID of the record to edit.
     *
     * @return void
     */
    abstract protected function render_edit_form(int $id);


    /**
     * Handles the display and processing of the edit form.
     * Typically used to manage form fields and submission handling
     * when editing an entity in the system.
     */
    abstract protected function handle_edit_form();

    /**
     * Static initializer to create an instance of the class and register hooks.
     */
    public static function init(): void
    {
        $page = new static();
        $page->register_page();

        // Hooks for styles and scripts
        add_action('admin_head', [$page, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$page, 'enqueue_scripts']);
    }

    /**
     * Registers the admin page in the WordPress admin menu.
     */
    public function register_page(): void
    {
        add_submenu_page(
            'payway-cabinet',
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->menu_slug,
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renders the admin page for the plugin.
     * Includes the list table and additional HTML specific to the admin interface.
     *
     * @return void
     */
    public function render_admin_page(): void
    {

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $this->render_edit_form(absint($_GET['id']));

            return;
        }

        require_once PAYWAY_PLUGIN_DIR . '/admin/pages/list-tables/' . $this->list_table_file;

        $list_table = new $this->list_table_class_name();
        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h2><?php echo esc_html($this->page_title); ?></h2>
            <div class="notice-container">
                <?php
                if (isset($_GET['deleted'])) {
                    if ($_GET['deleted'] === 'true') {
                        echo '<div class="notice notice-success"><p>Запись успешно удалена!</p></div>';
                    } elseif ($_GET['deleted'] === 'false') {
                        echo '<div class="notice notice-error"><p>Не удалось удалить запись!</p></div>';
                    }
                }
                if (isset($_GET['updated'])) {
                    if ($_GET['updated'] === 'true') {
                        echo '<div class="notice notice-success"><p>Запись успешно обновлена!</p></div>';
                    } elseif ($_GET['updated'] === 'false') {
                        echo '<div class="notice notice-error"><p>Не удалось обновить запись!</p></div>';
                    }
                }
                ?>
            </div>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($this->menu_slug); ?>"/>
                <?php $list_table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders a custom popup modal for the admin page.
     * This modal can be used to display additional UI elements such as
     * forms or alerts.
     */
    protected function render_popup_modal(): void
    {
        ?>
        <div id="payway-status-modal" style="display:none;">
            <p>
                <label for="payway-rejected-status-comment">Укажите причину отклонения:</label>
                <textarea id="payway-rejected-status-comment" rows="6" style="width: 100%"></textarea>
                <button class="button action" id="payway-rejected-status-button">Отклонить</button>
            </p>
        </div>
        <?php
    }

    /**
     * Enqueues inline styles for the admin page.
     * Specifically adjusts column widths in the admin table.
     */
    public function enqueue_styles(): void
    {
        if (empty($this->columns)) {
            return;
        }
        echo '<style type="text/css">';
        foreach ($this->columns as $column => $width) {
            echo ".wp-list-table .column-{$column} { width: {$width}px !important; overflow: hidden; }";
        }
        echo '</style>';
    }

    /**
     * Enqueues JavaScript for the admin page.
     * By default, it loads a script from the plugin's `assets/js/admin` directory.
     */
    public function enqueue_scripts(): void
    {
        $current_screen = get_current_screen();

        if ($current_screen->id !== "toplevel_page_" . $this->menu_slug) {
            return;
        }

        wp_enqueue_script(
            $this->menu_slug . '-js',
            PAYWAY_PLUGIN_URL . '/admin/assets/js/general.js',
            ['jquery'],
            PAYWAY_PLUGIN_VERSION,
            true
        );
        add_thickbox();
    }

    /**
     * Handles the deletion of a record.
     *
     * @return void
     */
    public function handle_delete_action(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('У вас недостаточно прав для доступа к этой странице.'));
        }

        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
            global $wpdb;

            $item_id = absint($_GET['id']);

            if (!$item_id) {
                return;
            }

            $table_name = $wpdb->prefix . $this->db_table_name;

            $deleted = $wpdb->delete($table_name, ['id' => $item_id], ['%d']);

            if ($deleted) {
                wp_redirect(add_query_arg(['deleted' => 'true'], remove_query_arg(['action', 'id'])));
                exit;
            } else {
                wp_redirect(add_query_arg(['deleted' => 'false'], remove_query_arg(['action', 'id'])));
                exit;
            }
        }
    }
}