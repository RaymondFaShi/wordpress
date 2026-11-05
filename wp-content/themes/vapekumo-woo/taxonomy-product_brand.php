<?php
/**
 * Brand product archive.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
$term = get_queried_object();
$paged = max( 1, (int) get_query_var( 'paged' ) );
?>
<main id="main">
	<nav class="crumbs" aria-label="パンくずリスト">
		<div class="container"><ol class="crumbs__list"><li class="crumbs__item"><a class="crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li><li class="crumbs__item"><a class="crumbs__link" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">ブランド一覧</a></li><li class="crumbs__item"><span class="crumbs__current"><?php echo esc_html( $term->name ); ?></span></li></ol></div>
	</nav>
	<section class="cat-intro">
		<div class="container">
			<div class="cat-intro__box">
				<h1 class="cat-intro__title"><?php echo esc_html( $term->name ); ?></h1>
				<div class="editor-content">
					<?php if ( $term->description ) : ?>
						<?php echo wp_kses_post( term_description( $term ) ); ?>
					<?php else : ?>
						<p><?php echo esc_html( $term->name ); ?>の商品一覧です。商品名を選択すると、規格・価格・在庫を確認できます。</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<section class="cat-main">
		<div class="container">
			<div class="toolbar"><div class="toolbar__lead"><p class="toolbar__count">全<b><?php echo esc_html( number_format_i18n( $GLOBALS['wp_query']->found_posts ) ); ?></b>件</p></div><div class="toolbar__side"><a class="btn btn-line" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">ブランド一覧へ</a></div></div>
			<?php if ( have_posts() ) : ?>
				<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-page product-grid">
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<div class="col"><?php vk_product_card( get_the_ID(), 0, '', true, 'catalog' ); ?></div>
					<?php endwhile; ?>
				</div>
				<?php if ( $GLOBALS['wp_query']->max_num_pages > 1 ) : ?>
					<nav class="pager" aria-label="ページ送り">
						<?php if ( $paged > 1 ) : ?><a class="pager__link" href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1, vk_brand_url( $term ) ) ); ?>">前へ</a><?php endif; ?>
						<span class="pager__link is-active"><?php echo esc_html( (string) $paged ); ?> / <?php echo esc_html( (string) $GLOBALS['wp_query']->max_num_pages ); ?></span>
						<?php if ( $paged < $GLOBALS['wp_query']->max_num_pages ) : ?><a class="pager__link" href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1, vk_brand_url( $term ) ) ); ?>">次へ</a><?php endif; ?>
					</nav>
				<?php endif; ?>
			<?php else : ?>
				<div class="cart-empty"><p class="cart-empty__title">該当する商品がありません</p><a class="btn btn-cta" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">ブランド一覧を見る</a></div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
