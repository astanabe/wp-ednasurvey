<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$delete_data = get_option( 'ednasurvey_delete_data_on_uninstall', false );

if ( $delete_data ) {
    global $wpdb;
    $prefix = $wpdb->prefix;

    // Drop custom tables (order matters for foreign key safety)
    $tables = array(
        "{$prefix}ednasurvey_site_custom_data",
        "{$prefix}ednasurvey_custom_fields",
        "{$prefix}ednasurvey_photos",
        "{$prefix}ednasurvey_messages",
        "{$prefix}ednasurvey_sites",
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    // Delete uploaded files
    $upload_dir = wp_upload_dir();
    $edna_dir   = $upload_dir['basedir'] . '/ednasurvey';
    if ( is_dir( $edna_dir ) ) {
        $iterator = new RecursiveDirectoryIterator( $edna_dir, RecursiveDirectoryIterator::SKIP_DOTS );
        $files    = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::CHILD_FIRST );
        foreach ( $files as $file ) {
            if ( $file->isDir() ) {
                rmdir( $file->getRealPath() );
            } else {
                unlink( $file->getRealPath() );
            }
        }
        rmdir( $edna_dir );
    }

    delete_option( 'ednasurvey_db_version' );
    delete_option( 'ednasurvey_settings' );
}

// Always clean up the flag itself
delete_option( 'ednasurvey_delete_data_on_uninstall' );
delete_option( 'ednasurvey_flush_rewrite' );
