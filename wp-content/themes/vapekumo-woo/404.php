<?php
/**
 * Not found.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
$error_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 6 ) );
$error_products   = wc_get_products( array( 'status' => 'publish', 'limit' => 4, 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales', 'order' => 'DESC' ) );
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="error">
		<div class="container">
			<div class="error__panel">
				<p class="error__code" aria-hidden="true">404</p>
				<h1 class="error__title">お探しのページが見つかりませんでした</h1>
				<p class="error__lead">ご覧になろうとしたページは、URLが変更されたか削除された可能性があります。下記の検索やカテゴリーからお探しの商品を探せます。</p>
				<div class="error__search"><form class="site-search__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get"><i class="bi bi-search site-search__icon" aria-hidden="true"></i><label class="visually-hidden" for="errorSearchInput">商品を検索</label><input class="site-search__input" id="errorSearchInput" name="s" type="search" placeholder="商品名・ブランド名で検索"><input type="hidden" name="post_type" value="product"><button class="btn btn-cta site-search__submit" type="submit">検索する</button></form></div>
				<div class="error__actions"><a class="btn btn-cta error__action" href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="bi bi-house-door" aria-hidden="true"></i>トップページへ戻る</a><a class="btn btn-line error__action" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><i class="bi bi-envelope" aria-hidden="true"></i>お問い合わせ</a></div>
			</div>
		</div>
	</section>
	<section class="section" aria-labelledby="errorCatTitle">
		<div class="container">
			<div class="section__head"><div><h2 class="section__title" id="errorCatTitle">カテゴリーから探す</h2><p class="section__sub">取り扱いカテゴリーの一覧です。目的の商品を絞り込めます。</p></div><a class="section__more" href="<?php echo esc_url( home_url( '/sitemap/' ) ); ?>">サイトマップ<i class="bi bi-chevron-right" aria-hidden="true"></i></a></div>
			<div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-page">
				<?php if ( $error_categories && ! is_wp_error( $error_categories ) ) : ?>
					<?php foreach ( $error_categories as $category ) : ?><div class="col"><a class="error-cat" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><i class="bi bi-box-seam error-cat__icon" aria-hidden="true"></i><span class="error-cat__name"><?php echo esc_html( $category->name ); ?></span></a></div><?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<section class="section section--tint" aria-labelledby="errorPopularTitle">
		<div class="container">
			<div class="section__head"><div><h2 class="section__title" id="errorPopularTitle">よく見られている商品</h2><p class="section__sub">いま人気の商品をピックアップしました。</p></div><a class="section__more" href="<?php echo esc_url( vk_shop_url() ); ?>">商品一覧へ<i class="bi bi-chevron-right" aria-hidden="true"></i></a></div>
			<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-page"><?php foreach ( $error_products as $error_product ) : ?><div class="col"><?php vk_product_card( $error_product ); ?></div><?php endforeach; ?></div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
