<?php
/**
 * HTML sitemap.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$product_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0 ) );
$brands = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'name' ) );
$brands = is_wp_error( $brands ) ? array() : $brands;
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section sitemap" aria-labelledby="sitemapTitle">
		<div class="container">
			<div class="page-head">
				<div><h1 class="page-head__title" id="sitemapTitle">サイトマップ</h1><p class="page-head__lead">各ページへのリンクをまとめました。目的のページをすばやく探せます。</p></div>
				<p class="page-head__side sitemap__xml"><a href="<?php echo esc_url( home_url( '/wp-sitemap.xml' ) ); ?>">XMLサイトマップ</a></p>
			</div>
			<div class="sitemap__grid">
				<section class="sitemap-group" aria-labelledby="smCategory"><h2 class="sitemap-group__title" id="smCategory">カテゴリー</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( vk_shop_url() ); ?>">すべての商品</a></li><?php foreach ( $product_categories as $category ) : ?><li><a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a></li><?php endforeach; ?></ul></section>
				<section class="sitemap-group" aria-labelledby="smFeature"><h2 class="sitemap-group__title" id="smFeature">特集・おすすめ</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', vk_shop_url() ) ); ?>">人気ランキング</a></li><li><a href="<?php echo esc_url( vk_product_category_url( 'new-products' ) ); ?>">新商品</a></li><li><a href="<?php echo esc_url( vk_product_category_url( 'sale' ) ); ?>">セール</a></li><li><a href="<?php echo esc_url( vk_product_category_url( 'disposable' ) ); ?>">使い捨てベイプ</a></li></ul></section>
				<section class="sitemap-group" aria-labelledby="smBrand"><h2 class="sitemap-group__title" id="smBrand">ブランド</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">ブランド一覧</a></li><?php foreach ( $brands as $brand ) : ?><li><a href="<?php echo esc_url( get_term_link( $brand ) ); ?>"><?php echo esc_html( $brand->name ); ?></a></li><?php endforeach; ?></ul></section>
				<section class="sitemap-group" aria-labelledby="smGuide"><h2 class="sitemap-group__title" id="smGuide">ご利用ガイド</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">ご利用ガイド</a></li><li><a href="<?php echo esc_url( home_url( '/payment/' ) ); ?>">お支払い方法</a></li><li><a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">配送について</a></li><li><a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">返品・交換について</a></li><li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくあるご質問</a></li></ul></section>
				<section class="sitemap-group" aria-labelledby="smBlog"><h2 class="sitemap-group__title" id="smBlog">ブログ</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">ブログ一覧</a></li><?php foreach ( get_posts( array( 'posts_per_page' => 5, 'post_status' => 'publish' ) ) as $post_item ) : ?><li><a href="<?php echo esc_url( get_permalink( $post_item ) ); ?>"><?php echo esc_html( get_the_title( $post_item ) ); ?></a></li><?php endforeach; ?></ul></section>
				<section class="sitemap-group" aria-labelledby="smAccount"><h2 class="sitemap-group__title" id="smAccount">マイページ</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( vk_account_url() ); ?>">ログイン・マイアカウント</a></li><li><a href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">注文履歴</a></li><li><a href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>">お気に入り</a></li><li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">ショッピングカート</a></li></ul></section>
				<section class="sitemap-group" aria-labelledby="smCompany"><h2 class="sitemap-group__title" id="smCompany">会社情報・サポート</h2><ul class="sitemap-group__list"><li><a href="<?php echo esc_url( home_url( '/company/' ) ); ?>">会社概要</a></li><li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせ</a></li><li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a></li><li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">利用規約</a></li><li><a href="<?php echo esc_url( home_url( '/sitemap/' ) ); ?>">サイトマップ</a></li></ul></section>
			</div>
			<p class="sitemap__note">サイトマップに掲載されていないページは、各カテゴリーや検索からご覧いただけます。商品情報は日々更新されています。</p>
		</div>
	</section>
</main>
<?php get_footer(); ?>
