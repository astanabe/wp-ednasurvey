<?php
/**
 * Plugin Name: eDNA Survey
 * Plugin URI:
 * Description: Environmental DNA Citizen Survey Reporting Site Plugin
 * Version: 1.8.3
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author:
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-ednasurvey
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'EDNASURVEY_VERSION', '1.8.3' );
define( 'EDNASURVEY_DB_VERSION', '1.5.0' );
define( 'EDNASURVEY_PLUGIN_FILE', __FILE__ );
define( 'EDNASURVEY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDNASURVEY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EDNASURVEY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader
$autoloader = EDNASURVEY_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
    require_once $autoloader;
}

// Plugin class files
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-activator.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-i18n.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-router.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-assets.php';

// Models
require_once EDNASURVEY_PLUGIN_DIR . 'includes/models/class-site-model.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/models/class-photo-model.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/models/class-chat-model.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/models/class-custom-field-model.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/models/class-custom-field-data-model.php';

// Controllers
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-dashboard-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-online-submission-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-offline-template-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-offline-submission-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-sites-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-map-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-chat-controller.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/controllers/class-site-detail-controller.php';

// Admin
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-download-data.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-add-users.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-all-sites.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-all-sites-table.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-sites-map.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-settings.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-messages.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/admin/class-admin-site-detail.php';

// Services
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-excel-service.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-photo-service.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-csv-service.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-user-import-service.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-validation-service.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/services/class-notification-service.php';

// AJAX
require_once EDNASURVEY_PLUGIN_DIR . 'includes/ajax/class-ajax-handler.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/ajax/class-ajax-submission.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/ajax/class-ajax-chat.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/ajax/class-ajax-sites.php';
require_once EDNASURVEY_PLUGIN_DIR . 'includes/ajax/class-ajax-admin.php';

// Main plugin class
require_once EDNASURVEY_PLUGIN_DIR . 'includes/class-plugin.php';

// Activation / Deactivation
register_activation_hook( __FILE__, array( 'EdnaSurvey_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'EdnaSurvey_Deactivator', 'deactivate' ) );

// Bootstrap
add_action( 'plugins_loaded', function () {
    EdnaSurvey_Plugin::get_instance();
} );
