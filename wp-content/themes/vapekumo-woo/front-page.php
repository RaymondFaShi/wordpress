<?php
/**
 * Storefront home page.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$ranking = wc_get_products(
	array(
		'status'  => 'publish',
		'limit'   => 10,
		'orderby' => 'meta_value_num',
		'meta_key' => 'total_sales',
		'order'   => 'DESC',
	)
);
$latest = wc_get_products( array( 'status' => 'publish', 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
$sale   = wc_get_products( array( 'status' => 'publish', 'limit' => 8, 'category' => array( 'sale' ) ) );
$recommended = wc_get_products( array( 'status' => 'publish', 'limit' => 8, 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales', 'order' => 'DESC' ) );
$categories = array_filter(
	array_map(
		static function ( $slug ) {
			return get_term_by( 'slug', $slug, 'product_cat' );
		},
		array( 'recommend', 'disposable', 'liquids', 'accessories' )
	)
);
$hero_banners  = vk_get_home_banners( 'hero' );
$promo_banners = vk_get_home_banners( 'promo' );
$posts     = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 5, 'post_status' => 'publish' ) );
?>
<h1 class="visually-hidden">7s7｜20歳以上限定 電子タバコ・ベイプ通販</h1>
<main id="main">
	<section class="hero" aria-label="特集バナー">
		<div class="container">
			<div class="row g-page">
				<div class="col-12 col-lg-6">
					<div class="hero__slider slider" id="heroSlider">
						<?php foreach ( $hero_banners as $index => $banner ) : ?>
							<div>
								<?php if ( $banner['link'] ) : ?>
									<a href="<?php echo esc_url( $banner['link'] ); ?>"><img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['alt'] ); ?>" width="649" height="400"<?php echo 0 === $index ? ' fetchpriority="high"' : ' loading="lazy"'; ?>></a>
								<?php else : ?>
									<img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['alt'] ); ?>" width="649" height="400"<?php echo 0 === $index ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="col-12 col-lg-6">
					<div class="hero__themes">
						<?php foreach ( $promo_banners as $index => $banner ) : ?>
							<?php if ( $banner['link'] ) : ?>
								<a class="hero__theme" href="<?php echo esc_url( $banner['link'] ); ?>"><img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['alt'] ); ?>" width="649" height="400"<?php echo $index > 1 ? ' loading="lazy"' : ''; ?>></a>
							<?php else : ?>
								<div class="hero__theme"><img src="<?php echo esc_url( $banner['image_url'] ); ?>" alt="<?php echo esc_attr( $banner['alt'] ); ?>" width="649" height="400"<?php echo $index > 1 ? ' loading="lazy"' : ''; ?>></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="services" aria-label="サービス情報">
		<div class="container">
			<div class="services__card">
				<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-0">
					<div class="col"><div class="service"><i class="bi bi-truck service__icon" aria-hidden="true"></i><div><p class="service__title"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</p><p class="service__text">対象地域への通常配送が無料です。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-shield-check service__icon" aria-hidden="true"></i><div><p class="service__title">正規品を取扱い</p><p class="service__text">商品情報を確認してご案内します。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-headset service__icon" aria-hidden="true"></i><div><p class="service__title">日本語サポート</p><p class="service__text">ご不明点はお問い合わせください。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-journal-text service__icon" aria-hidden="true"></i><div><p class="service__title">スムーズな配送</p><p class="service__text">ご注文状況はマイページで確認できます。</p></div></div></div>
				</div>
			</div>
		</div>
	</section>

	<section class="section" aria-labelledby="catTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="catTitle">カテゴリーで探す</h2><p class="section__sub">目的やスタイルから、ぴったりの一本を選べます。</p></div>
				<a class="section__more" href="<?php echo esc_url( vk_shop_url() ); ?>">すべて見る<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-page">
				<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $category ) : ?>
						<?php
						$image_id  = (int) get_term_meta( $category->term_id, 'thumbnail_id', true );
						$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : vk_asset( 'images/category/cat-disposable.jpg' );
						?>
						<div class="col">
							<a class="category-card" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
								<span class="category-card__media"><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $category->name ); ?>" width="427" height="264" loading="lazy"></span>
								<span class="category-card__body">
									<span class="category-card__name"><?php echo esc_html( $category->name ); ?></span>
									<span class="category-card__text"><?php echo esc_html( wp_trim_words( $category->description, 18 ) ); ?></span>
								</span>
							</a>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="col-12"><p>商品カテゴリーを準備しています。</p></div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="section section--tint" aria-labelledby="rankTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="rankTitle">売れ筋ランキング</h2><p class="section__sub">今月の購入ランキング</p></div>
				<a class="section__more" href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', vk_shop_url() ) ); ?>">ランキング一覧<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-page" id="rankingList">
				<?php foreach ( $ranking as $index => $product ) : ?>
					<div class="col"><?php vk_product_card( $product, $index + 1, '', false, 'rank' ); ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section rail" aria-labelledby="newTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="newTitle">新着商品</h2><p class="section__sub">今週入荷した新商品をいち早くチェック。</p></div>
				<a class="section__more" href="<?php echo esc_url( add_query_arg( 'orderby', 'date', vk_shop_url() ) ); ?>">もっと見る<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<?php if ( $latest ) : ?>
				<div class="rail__wrap">
					<button class="rail__nav rail__nav--prev" type="button" aria-label="前へ"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
					<div class="rail__viewport" data-rail-viewport>
						<?php foreach ( $latest as $product ) : ?>
							<?php vk_product_card( $product, 0, '', false, 'new' ); ?>
						<?php endforeach; ?>
					</div>
					<button class="rail__nav rail__nav--next" type="button" aria-label="次へ"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section--tint rail" aria-labelledby="recTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="recTitle">おすすめベイプ</h2><p class="section__sub">編集部が選んだ、自信を持っておすすめできる一台。</p></div>
				<a class="section__more" href="<?php echo esc_url( vk_shop_url() ); ?>">もっと見る<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<?php if ( $recommended ) : ?>
				<div class="rail__wrap">
					<button class="rail__nav rail__nav--prev" type="button" aria-label="前へ"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
					<div class="rail__viewport" data-rail-viewport>
						<?php foreach ( $recommended as $product ) : ?>
							<?php vk_product_card( $product ); ?>
						<?php endforeach; ?>
					</div>
					<button class="rail__nav rail__nav--next" type="button" aria-label="次へ"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section--tint rail" aria-labelledby="saleTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="saleTitle">クリアランスセール</h2><p class="section__sub">数量限定・在庫限り。お早めにお買い求めください。</p></div>
				<a class="section__more" href="<?php echo esc_url( vk_product_category_url( 'sale' ) ); ?>">もっと見る<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<?php if ( $sale ) : ?>
				<div class="rail__wrap">
					<button class="rail__nav rail__nav--prev" type="button" aria-label="前へ"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
					<div class="rail__viewport" data-rail-viewport>
						<?php foreach ( $sale as $product ) : ?>
							<?php vk_product_card( $product, 0, '', false, 'sale' ); ?>
						<?php endforeach; ?>
					</div>
					<button class="rail__nav rail__nav--next" type="button" aria-label="次へ"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section--tint" aria-labelledby="brandTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="brandTitle">ブランドで探す</h2><p class="section__sub">取り扱いブランドから、お目当ての製品を探せます。</p></div>
				<a class="section__more" href="<?php echo esc_url( home_url( '/brand/' ) ); ?>">すべてのブランド<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="row row-cols-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-7 g-page">
				<?php
				$brand_terms = vk_get_brand_terms_with_images();
				foreach ( $brand_terms as $brand_term ) :
					$image_url = vk_get_brand_image_url( $brand_term->slug );
					?>
					<div class="col"><a class="brand-card" href="<?php echo esc_url( get_term_link( $brand_term ) ); ?>"><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $brand_term->name ); ?>" width="216" height="82" loading="lazy"></a></div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section rail" aria-labelledby="blogTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="blogTitle">ブログ</h2><p class="section__sub">選び方やお手入れ方法など、役立つ情報をお届けします。</p></div>
				<a class="section__more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">記事一覧<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="rail__wrap">
				<button class="rail__nav rail__nav--prev" type="button" aria-label="前へ"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
				<div class="rail__viewport rail__viewport--blog" data-rail-viewport>
					<?php foreach ( $posts as $post ) : ?>
						<a class="post-card" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
							<span class="post-card__media"><?php echo get_the_post_thumbnail( $post, 'vk-post-card', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="post-card__body">
								<span class="post-card__meta"><span class="post-card__tag">お知らせ</span><?php echo esc_html( get_the_date( 'Y.m.d', $post ) ); ?></span>
								<span class="post-card__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
				<button class="rail__nav rail__nav--next" type="button" aria-label="次へ"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
			</div>
		</div>
	</section>

	<section class="section section--tint" aria-labelledby="reasonTitle">
		<div class="container">
			<div class="section__head"><div><h2 class="section__title" id="reasonTitle">選ばれる理由</h2><p class="section__sub">安心してご購入いただくための、7s7の約束です。</p></div></div>
			<div class="services__card services__card--light">
				<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-0">
					<div class="col"><div class="service"><i class="bi bi-truck service__icon" aria-hidden="true"></i><div><p class="service__title">送料無料</p><p class="service__text"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上のご注文が対象です。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-shield-check service__icon" aria-hidden="true"></i><div><p class="service__title">正規品保証</p><p class="service__text">仕入元と商品情報を確認しています。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-headset service__icon" aria-hidden="true"></i><div><p class="service__title">安心サポート</p><p class="service__text">日本語でお問い合わせいただけます。</p></div></div></div>
					<div class="col"><div class="service"><i class="bi bi-credit-card service__icon" aria-hidden="true"></i><div><p class="service__title">安全な決済</p><p class="service__text">WooCommerceの決済フローで処理します。</p></div></div></div>
				</div>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
