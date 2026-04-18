<?php

namespace Payway\Pages;

class ReferralPage extends AbstractAdminPage {

    protected function init_page_settings(): void {
        $this->page_title          = "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb0\xd0\xbb\xd1\x8b";
        $this->menu_title          = "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb0\xd0\xbb\xd1\x8b";
        $this->menu_slug           = 'payway-referrals';
        $this->columns          = [];
        $this->list_table_file     = 'class-referral.php';
        $this->list_table_class_name = 'Referral_List_Table';
        $this->db_table_name       = 'payway_referrals';
    }

    public function render_admin_page(): void {
        parent::render_admin_page();
    }

    protected function render_edit_form(int $id): void {}
    protected function handle_edit_form(): void {}
}
