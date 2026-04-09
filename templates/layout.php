<?php
/**
 * Frontend layout wrapper.
 *
 * Variables expected: $page_title, $content_callback
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div id="ednasurvey-wrap" class="ednasurvey-container">
    <?php if ( ! empty( $page_title ) ) : ?>
        <h1 class="ednasurvey-page-title"><?php echo esc_html( $page_title ); ?></h1>
    <?php endif; ?>

    <div class="ednasurvey-content">
        <?php
        if ( is_callable( $content_callback ) ) {
            call_user_func( $content_callback );
        }
        ?>
    </div>
</div>

<?php
get_footer();
