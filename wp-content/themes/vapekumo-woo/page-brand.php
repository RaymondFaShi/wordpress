<?php
/**
 * Brand index.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$brands = get_terms(
	array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
$brands = is_wp_error( $brands ) ? array() : $brands;
$hot_brands = array_slice(
	array_values(
		array_filter(
			$brands,
			static function ( $term ) {
				return 0 < (int) $term->count;
			}
		)
	),
	0,
	8
);
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section brand" aria-labelledby="brandTitle">
		<div class="container">
			<div class="page-head">
				<div>
					<h1 class="page-head__title" id="brandTitle">ブランド一覧</h1>
					<p class="page-head__lead">取り扱いブランドを頭文字から探せます。キーワード検索にも対応しています。</p>
				</div>
			</div>

			<section class="brand-hot" aria-labelledby="brandHotTitle">
				<div class="section__head">
					<div><h2 class="section__title" id="brandHotTitle">人気ブランド</h2><p class="section__sub">よく選ばれているブランドをピックアップしました。</p></div>
				</div>
				<ul class="row g-page brand-hot__list">
					<?php foreach ( $hot_brands as $brand_term ) : ?>
						<li class="col-6 col-md-4 col-lg-3">
							<a class="brand-hot__item" href="<?php echo esc_url( get_term_link( $brand_term ) ); ?>">
								<?php if ( file_exists( VK_THEME_DIR . '/assets/images/brand/' . $brand_term->slug . '.png' ) ) : ?>
									<img class="brand-hot__logo" src="<?php echo esc_url( vk_asset( 'images/brand/' . $brand_term->slug . '.png' ) ); ?>" alt="<?php echo esc_attr( $brand_term->name ); ?>" width="216" height="82" loading="lazy">
								<?php else : ?>
									<span class="brand-item__name"><?php echo esc_html( $brand_term->name ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="brand-all" aria-labelledby="brandAllTitle">
				<div class="section__head">
					<div><h2 class="section__title" id="brandAllTitle">すべてのブランド</h2><p class="section__sub">頭文字を選ぶか、ブランド名を入力して絞り込めます。</p></div>
					<p class="brand-all__count" id="brandCount" aria-live="polite">該当 <b><?php echo esc_html( (string) count( $brands ) ); ?></b> ブランド</p>
				</div>
				<div class="brand-filter">
					<div class="brand-filter__search">
						<i class="bi bi-search brand-filter__icon" aria-hidden="true"></i>
						<label class="visually-hidden" for="brandSearch">ブランド名で検索</label>
						<input class="brand-filter__input" id="brandSearch" type="search" placeholder="ブランド名で検索（例：ELFBAR）" autocomplete="off">
						<button class="brand-filter__clear" id="brandClear" type="button" aria-label="検索条件をクリア" hidden>×</button>
					</div>
					<nav class="brand-az" aria-label="頭文字で絞り込み">
						<ul class="brand-az__list" id="brandAz">
							<li><button class="brand-az__btn is-active" type="button" data-initial="all" aria-pressed="true">すべて</button></li>
							<?php foreach ( range( 'A', 'Z' ) as $initial ) : ?>
								<li><button class="brand-az__btn" type="button" data-initial="<?php echo esc_attr( $initial ); ?>" aria-pressed="false"><?php echo esc_html( $initial ); ?></button></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				</div>
				<ul class="row g-page brand-list" id="brandList">
					<?php foreach ( $brands as $brand_term ) : ?>
						<li class="col-6 col-md-4 col-lg-3 brand-list__item" data-name="<?php echo esc_attr( strtolower( $brand_term->name ) ); ?>" data-initial="<?php echo esc_attr( strtoupper( $brand_term->name[0] ) ); ?>">
							<a class="brand-item" href="<?php echo esc_url( get_term_link( $brand_term ) ); ?>">
								<span class="brand-item__name"><?php echo esc_html( $brand_term->name ); ?></span>
								<span class="brand-item__count"><?php echo esc_html( (string) $brand_term->count ); ?>商品</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="brand-empty" id="brandEmpty" hidden>
					<p>該当するブランドがありません。</p>
					<button class="btn btn-line" type="button" id="brandReset">条件をクリア</button>
				</div>
			</section>
		</div>
	</section>
</main>
<?php get_footer(); ?>
