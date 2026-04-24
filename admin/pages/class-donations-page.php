<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
 
namespace Payway\Pages;
 
/**
 * DonationsPage — вкладка «Донаты» в WP Admin → PW Кабинет
 *
 * @package Payway
 * @version 4.9
 */
class DonationsPage {
 
    public static function init(): void {
        $page = new static();
        add_action( 'admin_menu', [ $page, 'register_page' ] );
    }
 
    public function register_page(): void {
        add_submenu_page(
            'payway-cabinet',
            'Донаты — PayWay',
            'Донаты',
            'manage_options',
            'payway-donations',
            [ $this, 'render' ]
        );
    }
 
    public function render(): void {
        require_once PAYWAY_PLUGIN_DIR . '/admin/pages/list-tables/class-donations-list-table.php';
 
        $table = new \DonationsListTable();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1>Донаты</h1>
            <p style="color:#666;margin-bottom:16px">
                История донатов от пользователей. Суммы списываются с баланса PayWay.
            </p>
            <form method="get">
                <input type="hidden" name="page" value="payway-donations">
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }
}
