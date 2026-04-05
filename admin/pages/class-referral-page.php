<?php

namespace Payway\Pages;

class ReferralPage extends AbstractAdminPage {

    public function init_page_settings() {
        $this->page_title          = "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb0\xd0\xbb\xd1\x8b";
        $this->menu_title          = "\xd0\xa0\xd0\xb5\xd1\x84\xd0\xb5\xd1\x80\xd0\xb0\xd0\xbb\xd1\x8b";
        $this->menu_slug           = 'payway-referrals';
        $this->columns             = 5;
        $this->list_table_file     = 'class-referral.php';
        $this->list_table_class_name = 'Referral_List_Table';
        $this->db_table_name       = 'payway_referrals';
    }

    public function render_admin_page() {
        parent::render_admin_page();
    }
}
