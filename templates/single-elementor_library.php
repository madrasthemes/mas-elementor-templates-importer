<?php
/**
 * The template for displaying single post.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<div id="page" class="hfeed site">

	<main id="site-content" role="main">

		<?php

		if ( have_posts() ) {

			while ( have_posts() ) {
				the_post();

				?><article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
					<div class="entry-content">
						<?php the_content( __( 'Continue reading', 'mas-elementor-templates-importer' ) ); ?>
					</div>
				</article><?php
			}
		}

		?>

	</main>

</div>

<?php wp_footer(); ?>

</body>
</html>
