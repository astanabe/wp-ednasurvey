<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EdnaSurvey_Admin_All_Sites {

    public function render(): void {
        $table = new EdnaSurvey_All_Sites_Table();
        $table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'All Sites', 'wp-ednasurvey' ) . '</h1>';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="edna-survey-all-sites">';
        $table->display();
        echo '</form>';
        echo '</div>';
    }
}
