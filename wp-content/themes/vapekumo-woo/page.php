<?php
/**
 * Standard page template.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<article class="section page-content">
		<div class="container">
			<header class="page-head">
				<div>
					<h1 class="page-head__title"><?php single_post_title(); ?></h1>
				</div>
			</header>
			<div class="editor-content">
				<?php
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
				?>
			</div>
		</div>
	</article>
</main>
<?php get_footer(); ?>
