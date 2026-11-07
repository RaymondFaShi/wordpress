<?php
/**
 * Search results.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section">
		<div class="container">
			<header class="page-head">
				<div>
					<h1 class="page-head__title">検索結果「<?php echo esc_html( get_search_query() ); ?>」</h1>
					<p class="page-head__lead"><?php echo esc_html( (string) $GLOBALS['wp_query']->found_posts ); ?>件見つかりました。</p>
				</div>
			</header>
			<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-page product-grid">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<div class="col">
							<?php
							if ( 'product' === get_post_type() ) {
								vk_product_card( get_the_ID() );
							} else {
								?>
								<article class="post-card post-card--grid"><div class="post-card__body"><p class="post-card__meta"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></p><h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p></div></article>
								<?php
							}
							?>
						</div>
					<?php endwhile; ?>
				<?php else : ?>
					<div class="col-12"><div class="woocommerce-info">該当する商品や記事がありません。<a href="<?php echo esc_url( vk_shop_url() ); ?>">商品一覧へ</a></div></div>
				<?php endif; ?>
			</div>
			<?php the_posts_pagination( array( 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
