<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$page_title = EdnaSurvey_Router::get_page_titles()['dashboard'];
$content_callback = function () use ( $username, $settings ) {
    $base_url = home_url( '/' . $username );
    $current_user = wp_get_current_user();
    ?>
    <div class="ednasurvey-dashboard-header">
        <p class="ednasurvey-welcome">
            <?php
            /* translators: %s: user display name */
            printf( esc_html__( 'Welcome, %s', 'wp-ednasurvey' ), esc_html( $current_user->display_name ) );
            ?>
        </p>
    </div>

    <nav class="ednasurvey-dashboard-nav">
        <ul>
            <li>
                <a href="<?php echo esc_url( $base_url . '/onlinesubmission' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-edit-large"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'Submit Survey Data Online', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'Enter data for a single survey site', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( $base_url . '/offlinetemplate' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-download"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'Download Offline Template', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'Excel template for field surveys without connectivity', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( $base_url . '/offlinesubmission' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-upload"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'Upload Offline Data', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'Upload completed Excel template and photos', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( $base_url . '/sites' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-list-view"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'My Sites', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'View all your submitted survey sites', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( $base_url . '/map' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-location-alt"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'My Sites Map', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'View your survey sites on a map', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( $base_url . '/chat' ); ?>" class="ednasurvey-nav-card">
                    <span class="dashicons dashicons-format-chat"></span>
                    <span class="ednasurvey-nav-label"><?php esc_html_e( 'Messages', 'wp-ednasurvey' ); ?></span>
                    <span class="ednasurvey-nav-desc"><?php esc_html_e( 'Chat with administrator', 'wp-ednasurvey' ); ?></span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="ednasurvey-dashboard-footer-actions">
        <button type="button"
                id="ednasurvey-reset-online-defaults"
                class="button ednasurvey-reset-defaults-btn"
                data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'ednasurvey_nonce' ) ); ?>"
                data-confirm="<?php echo esc_attr__( 'Reset all defaults for the online submission page?', 'wp-ednasurvey' ); ?>"
                data-success="<?php echo esc_attr__( 'Online submission defaults have been reset.', 'wp-ednasurvey' ); ?>"
                data-error="<?php echo esc_attr__( 'Failed to reset defaults. Please try again.', 'wp-ednasurvey' ); ?>">
            <?php esc_html_e( 'Reset Online Submission Defaults', 'wp-ednasurvey' ); ?>
        </button>
        <a href="<?php echo esc_url( wp_logout_url( site_url( '/wp-login.php?loggedout=true' ) ) ); ?>" class="button ednasurvey-logout-btn">
            <?php esc_html_e( 'Log Out', 'wp-ednasurvey' ); ?>
        </a>
    </div>

    <script>
    (function() {
        var btn = document.getElementById('ednasurvey-reset-online-defaults');
        if (!btn) return;
        btn.addEventListener('click', function() {
            if (!window.confirm(btn.dataset.confirm)) return;
            btn.disabled = true;
            var body = new URLSearchParams();
            body.append('action', 'ednasurvey_reset_online_defaults');
            body.append('nonce', btn.dataset.nonce);
            fetch(btn.dataset.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: body.toString()
            }).then(function(r) { return r.json(); })
              .then(function(res) {
                  btn.disabled = false;
                  window.alert(res && res.success ? btn.dataset.success : btn.dataset.error);
              })
              .catch(function() {
                  btn.disabled = false;
                  window.alert(btn.dataset.error);
              });
        });
    })();
    </script>
    <?php
};

include EDNASURVEY_PLUGIN_DIR . 'templates/layout.php';
