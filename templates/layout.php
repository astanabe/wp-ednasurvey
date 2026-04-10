<?php
/**
 * Frontend layout wrapper.
 *
 * Renders plugin pages using the same HTML structure as GeneratePress page.php
 * so that theme styles (content width, typography, spacing) are applied naturally.
 *
 * Variables expected: $page_title, $content_callback
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div id="primary" <?php generate_do_element_classes( 'content' ); ?>>
    <main id="main" <?php generate_do_element_classes( 'main' ); ?>>
        <article id="ednasurvey-wrap" class="page type-page status-publish">
            <div class="inside-article">
                <?php if ( ! empty( $page_title ) ) : ?>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php echo esc_html( $page_title ); ?></h1>
                    </header>
                <?php endif; ?>

                <div class="entry-content ednasurvey-content">
                    <?php
                    if ( is_callable( $content_callback ) ) {
                        call_user_func( $content_callback );
                    }
                    ?>
                </div>
            </div>
        </article>
    </main>
</div>

<?php
generate_construct_sidebars();
get_footer();
