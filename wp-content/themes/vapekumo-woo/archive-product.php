<?php
/**
 * Product archive.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$term       = is_product_taxonomy() ? get_queried_object() : null;
$term_slug  = $term instanceof WP_Term ? $term->slug : '';
$title      = $term ? $term->name : 'オンラインストア';
$lead       = $term && $term->description ? $term->description : '使い捨てベイプからスターターキット、リキッド、シーシャまで。20歳以上の方に向けた商品を揃えています。';
$archive_url = $term instanceof WP_Term ? get_term_link( $term ) : vk_shop_url();
$child_categories = $term instanceof WP_Term && 'product_cat' === $term->taxonomy && 0 === (int) $term->parent
	? get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => false,
		)
	)
	: array();
if ( $child_categories && ! is_wp_error( $child_categories ) ) {
	$child_order = array(
		'disposable-vape'    => 0,
		'e-liquid'           => 1,
		'starter-kit'        => 2,
		'recommended-hookah' => 3,
		'weekly-arrivals'    => 0,
		'new-e-liquid'       => 1,
		'new-devices'        => 2,
		'restocked'          => 3,
	);
	usort(
		$child_categories,
		static function ( $left, $right ) use ( $child_order ) {
			return ( $child_order[ $left->slug ] ?? 99 ) <=> ( $child_order[ $right->slug ] ?? 99 );
		}
	);
}
$min_price  = isset( $_GET['vk_min_price'] ) ? wc_clean( wp_unslash( $_GET['vk_min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$max_price  = isset( $_GET['vk_max_price'] ) ? wc_clean( wp_unslash( $_GET['vk_max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$nicotine   = isset( $_GET['nicotine'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['nicotine'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$puffs      = isset( $_GET['puffs'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['puffs'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page   = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 20; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$orderby    = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : 'menu_order'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page   = in_array( $per_page, array( 20, 40, 60 ), true ) ? $per_page : 20;
$nicotine   = array_values( array_intersect( $nicotine, array( '0', '1', '2', '3', '4', '5' ) ) );
$puffs      = array_values( array_intersect( $puffs, array( '0-5000', '5001-10000', '10001-20000', '20001-' ) ) );
$paged      = max( 1, (int) get_query_var( 'paged' ) );
$found      = (int) $GLOBALS['wp_query']->found_posts;
$total_pages = max( 1, (int) $GLOBALS['wp_query']->max_num_pages );
$range_from = $found ? ( ( $paged - 1 ) * $per_page ) + 1 : 0;
$range_to   = min( $found, $paged * $per_page );

$filter_args = array();
if ( '' !== $min_price ) {
	$filter_args['vk_min_price'] = $min_price;
}
if ( '' !== $max_price ) {
	$filter_args['vk_max_price'] = $max_price;
}
if ( $nicotine ) {
	$filter_args['nicotine'] = $nicotine;
}
if ( $puffs ) {
	$filter_args['puffs'] = $puffs;
}
if ( 20 !== $per_page ) {
	$filter_args['per_page'] = $per_page;
}

$archive_link = static function ( $changes = array(), $remove = array() ) use ( $archive_url, $filter_args, $orderby ) {
	$args = $filter_args;
	if ( 'menu_order' !== $orderby ) {
		$args['orderby'] = $orderby;
	}
	foreach ( $remove as $key ) {
		unset( $args[ $key ] );
	}
	foreach ( $changes as $key => $value ) {
		if ( null === $value || '' === $value || array() === $value ) {
			unset( $args[ $key ] );
		} else {
			$args[ $key ] = $value;
		}
	}
	return $args ? add_query_arg( $args, $archive_url ) : $archive_url;
};

$page_link = static function ( $page ) use ( $archive_link ) {
	return (int) $page > 1 ? add_query_arg( 'paged', (int) $page, $archive_link() ) : $archive_link();
};

$nicotine_labels = array(
	'0' => '0mg（ノンニコチン）',
	'1' => '1%',
	'2' => '2%',
	'3' => '3%',
	'4' => '4%',
	'5' => '5%',
);
$puff_labels = array(
	'0-5000'      => '〜5,000回',
	'5001-10000'  => '5,001〜10,000回',
	'10001-20000' => '10,001〜20,000回',
	'20001-'      => '20,001回〜',
);
$sort_labels = array(
	'popularity' => '売れ筋',
	'date'       => '新着',
	'price'      => '価格',
	'menu_order' => 'おすすめ',
);
$price_sort      = 'price-desc' === $orderby ? 'price' : 'price-desc';
$sort_icon_dir   = 'price-desc' === $orderby ? 'desc' : 'asc';
$active_price    = in_array( $orderby, array( 'price', 'price-desc' ), true );
$active_sort     = $active_price ? 'price' : ( isset( $sort_labels[ $orderby ] ) ? $orderby : 'menu_order' );
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="cat-intro" aria-labelledby="catTitle">
		<div class="container">
			<div class="cat-intro__box">
				<h1 class="cat-intro__title" id="catTitle"><?php echo esc_html( $title ); ?></h1>
				<div class="editor-content" id="catIntroEditor">
					<?php if ( $term instanceof WP_Term && trim( $term->description ) ) : ?>
						<?php echo wp_kses_post( term_description( $term ) ); ?>
					<?php else : ?>
						<p><?php echo esc_html( wp_strip_all_tags( $lead ) ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $child_categories && ! is_wp_error( $child_categories ) ) : ?>
				<nav class="cat-children" aria-label="<?php echo esc_attr( $title ); ?>の子カテゴリー">
					<?php foreach ( $child_categories as $child ) : ?>
						<?php
						$image_url = (string) get_term_meta( $child->term_id, '_vk_category_image', true );
						$image_url = $image_url ?: vk_asset( 'images/category/cat-disposable.jpg' );
						?>
						<a class="cat-children__item" href="<?php echo esc_url( get_term_link( $child ) ); ?>">
							<span class="cat-children__media"><img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $child->name ); ?>" width="649" height="400" loading="lazy" decoding="async"></span>
							<span class="cat-children__body"><span class="cat-children__name"><?php echo esc_html( $child->name ); ?></span><span class="cat-children__count"><?php echo esc_html( (string) $child->count ); ?>件</span></span>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</section>

	<section class="cat-main" aria-labelledby="listTitle">
		<div class="container">
			<h2 class="visually-hidden" id="listTitle"><?php echo esc_html( $title ); ?>の商品一覧</h2>
			<div class="row g-4 cat-layout">
				<div class="col-12 col-lg-3">
					<aside class="filter-panel offcanvas-lg offcanvas-start" tabindex="-1" id="filterPanel" aria-labelledby="filterTitle">
						<div class="offcanvas-header filter-panel__head">
							<p class="filter-panel__title" id="filterTitle">絞り込み</p>
							<button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#filterPanel" aria-label="閉じる"></button>
						</div>
						<div class="offcanvas-body filter-panel__body">
							<form class="filter-form" id="filterForm" action="<?php echo esc_url( $archive_url ); ?>" method="get">
								<p class="filter-form__heading">絞り込み</p>
								<?php if ( 'menu_order' !== $orderby ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
								<?php if ( 20 !== $per_page ) : ?><input type="hidden" name="per_page" value="<?php echo esc_attr( (string) $per_page ); ?>"><?php endif; ?>
								<section class="filter-group">
									<h3 class="filter-group__title">価格帯</h3>
									<div class="price-range">
										<span class="price-range__box"><span class="price-range__yen" aria-hidden="true">¥</span><input class="price-range__input" id="filterPriceMin" name="vk_min_price" type="number" min="0" step="100" inputmode="numeric" placeholder="下限なし" aria-label="価格の下限" value="<?php echo esc_attr( $min_price ); ?>"></span>
										<span class="price-range__sep" aria-hidden="true">〜</span>
										<span class="price-range__box"><span class="price-range__yen" aria-hidden="true">¥</span><input class="price-range__input" id="filterPriceMax" name="vk_max_price" type="number" min="0" step="100" inputmode="numeric" placeholder="上限なし" aria-label="価格の上限" value="<?php echo esc_attr( $max_price ); ?>"></span>
									</div>
									<button class="btn btn-cta filter-form__submit" type="submit">金額を確認</button>
								</section>
								<section class="filter-group">
									<h3 class="filter-group__title">ニコチン濃度</h3>
									<ul class="filter-checks" role="group" aria-label="ニコチン濃度で絞り込む">
										<?php foreach ( $nicotine_labels as $value => $label ) : ?>
											<?php $value = (string) $value; ?>
											<li><div class="form-check"><input class="form-check-input" type="checkbox" name="nicotine[]" value="<?php echo esc_attr( $value ); ?>" id="filter-nicotine-<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $nicotine, true ) ); ?>><label class="form-check-label" for="filter-nicotine-<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></label></div></li>
										<?php endforeach; ?>
									</ul>
								</section>
								<section class="filter-group">
									<h3 class="filter-group__title">吸入口数</h3>
									<ul class="filter-checks" role="group" aria-label="吸入口数で絞り込む">
										<?php foreach ( $puff_labels as $value => $label ) : ?>
											<li><div class="form-check"><input class="form-check-input" type="checkbox" name="puffs[]" value="<?php echo esc_attr( $value ); ?>" id="filter-puffs-<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $puffs, true ) ); ?>><label class="form-check-label" for="filter-puffs-<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></label></div></li>
										<?php endforeach; ?>
									</ul>
								</section>
								<?php if ( $min_price || $max_price || $nicotine || $puffs ) : ?>
									<a class="filter-form__reset" href="<?php echo esc_url( $archive_link( array(), array( 'vk_min_price', 'vk_max_price', 'nicotine', 'puffs' ) ) ); ?>">条件をクリア</a>
								<?php endif; ?>
							</form>
						</div>
					</aside>
				</div>

				<div class="col-12 col-lg-9">
					<div class="toolbar">
						<div class="toolbar__lead">
							<button class="filter-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterPanel" aria-controls="filterPanel"><i class="bi bi-funnel" aria-hidden="true"></i>絞り込み</button>
							<p class="toolbar__count">全<b id="resultCount"><?php echo esc_html( number_format_i18n( $found ) ); ?></b>件<span class="toolbar__range">（<span id="resultRange"><?php echo esc_html( number_format_i18n( $range_from ) ); ?>〜<?php echo esc_html( number_format_i18n( $range_to ) ); ?></span>件を表示）</span></p>
							<form class="toolbar__size" action="<?php echo esc_url( $archive_url ); ?>" method="get">
								<label class="toolbar__size-label" for="filterPageSize">表示件数</label>
								<select class="form-select form-select-sm" id="filterPageSize" name="per_page" aria-label="1ページの表示件数" onchange="this.form.submit()">
									<?php foreach ( array( 20, 40, 60 ) as $size ) : ?>
										<option value="<?php echo esc_attr( (string) $size ); ?>" <?php selected( $per_page, $size ); ?>><?php echo esc_html( (string) $size ); ?>件</option>
									<?php endforeach; ?>
								</select>
								<?php foreach ( $filter_args as $key => $value ) : ?>
									<?php if ( 'per_page' === $key ) : ?>
										<?php continue; ?>
									<?php endif; ?>
									<?php if ( is_array( $value ) ) : ?>
										<?php foreach ( $value as $item ) : ?>
											<input type="hidden" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $item ); ?>">
										<?php endforeach; ?>
									<?php else : ?>
										<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if ( 'menu_order' !== $orderby ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
							</form>
						</div>
						<div class="toolbar__side">
							<div class="sort-tabs" id="sortTabs" role="group" aria-label="並び替え">
								<a class="sort-tabs__item<?php echo 'popularity' === $active_sort ? ' is-active' : ''; ?>" href="<?php echo esc_url( $archive_link( array( 'orderby' => 'popularity' ) ) ); ?>" data-sort="popularity">売れ筋</a>
								<a class="sort-tabs__item<?php echo 'date' === $active_sort ? ' is-active' : ''; ?>" href="<?php echo esc_url( $archive_link( array( 'orderby' => 'date' ) ) ); ?>" data-sort="date">新着</a>
								<a class="sort-tabs__item<?php echo $active_price ? ' is-active' : ''; ?>" href="<?php echo esc_url( $archive_link( array( 'orderby' => $price_sort ) ) ); ?>" data-sort="price" data-dir="<?php echo esc_attr( $sort_icon_dir ); ?>">価格<span class="sort-tabs__caret" aria-hidden="true"></span></a>
								<a class="sort-tabs__item<?php echo 'menu_order' === $active_sort ? ' is-active' : ''; ?>" href="<?php echo esc_url( $archive_link( array( 'orderby' => 'menu_order' ) ) ); ?>" data-sort="recommend"<?php echo 'menu_order' === $active_sort ? ' aria-current="true"' : ''; ?>>おすすめ</a>
							</div>
						</div>
					</div>

					<?php if ( $min_price || $max_price || $nicotine || $puffs ) : ?>
						<div class="active-filters" id="activeFilters">
							<p class="active-filters__label">絞り込み中：</p>
							<ul class="active-filters__list">
								<?php if ( $min_price || $max_price ) : ?>
									<li class="active-filters__item"><a class="active-filters__chip" href="<?php echo esc_url( $archive_link( array(), array( 'vk_min_price', 'vk_max_price' ) ) ); ?>"><?php echo esc_html( ( $min_price ? '¥' . number_format_i18n( (float) $min_price ) : '¥0' ) . '〜' . ( $max_price ? '¥' . number_format_i18n( (float) $max_price ) : '上限なし' ) ); ?><span class="active-filters__x" aria-hidden="true"></span><span class="visually-hidden">の絞り込みを解除</span></a></li>
								<?php endif; ?>
								<?php foreach ( $nicotine as $value ) : ?>
									<?php $rest = array_values( array_diff( $nicotine, array( $value ) ) ); ?>
									<li class="active-filters__item"><a class="active-filters__chip" href="<?php echo esc_url( $archive_link( array( 'nicotine' => $rest ) ) ); ?>">ニコチン <?php echo esc_html( $nicotine_labels[ $value ] ); ?><span class="active-filters__x" aria-hidden="true"></span><span class="visually-hidden">の絞り込みを解除</span></a></li>
								<?php endforeach; ?>
								<?php foreach ( $puffs as $value ) : ?>
									<?php $rest = array_values( array_diff( $puffs, array( $value ) ) ); ?>
									<li class="active-filters__item"><a class="active-filters__chip" href="<?php echo esc_url( $archive_link( array( 'puffs' => $rest ) ) ); ?>">吸入 <?php echo esc_html( $puff_labels[ $value ] ); ?><span class="active-filters__x" aria-hidden="true"></span><span class="visually-hidden">の絞り込みを解除</span></a></li>
								<?php endforeach; ?>
							</ul>
							<a class="active-filters__clear" href="<?php echo esc_url( $archive_link( array(), array( 'vk_min_price', 'vk_max_price', 'nicotine', 'puffs' ) ) ); ?>">すべて解除</a>
						</div>
					<?php endif; ?>
					<p class="visually-hidden" id="resultStatus" aria-live="polite"></p>

					<?php if ( woocommerce_product_loop() ) : ?>
						<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-page product-grid" id="productGrid">
							<?php while ( have_posts() ) : ?>
								<?php the_post(); ?>
								<div class="col"><?php vk_product_card( get_the_ID(), 0, '', true, 'catalog' ); ?></div>
							<?php endwhile; ?>
						</div>
						<nav class="pager" id="pager" aria-label="ページ送り">
							<?php if ( $paged > 1 ) : ?>
								<a class="pager__link" href="<?php echo esc_url( $page_link( $paged - 1 ) ); ?>" rel="prev" aria-label="前のページ">前へ</a>
							<?php else : ?>
								<span class="pager__link is-disabled" aria-disabled="true" aria-label="前のページ">前へ</span>
							<?php endif; ?>
							<ul class="pager__list">
								<?php
								$page_entries = array();
								if ( $total_pages <= 7 ) {
									$page_entries = range( 1, $total_pages );
								} else {
									$page_entries[] = 1;
									$start = max( 2, $paged - 1 );
									$end   = min( $total_pages - 1, $paged + 1 );
									if ( $start > 2 ) {
										$page_entries[] = '...';
									}
									for ( $page = $start; $page <= $end; $page++ ) {
										$page_entries[] = $page;
									}
									if ( $end < $total_pages - 1 ) {
										$page_entries[] = '...';
									}
									$page_entries[] = $total_pages;
								}
								foreach ( $page_entries as $entry ) :
									?>
									<li>
										<?php if ( '...' === $entry ) : ?>
											<span class="pager__gap" aria-hidden="true">…</span>
										<?php elseif ( (int) $entry === $paged ) : ?>
											<span class="pager__link is-active" aria-current="page"><?php echo esc_html( (string) $entry ); ?></span>
										<?php else : ?>
											<a class="pager__link" href="<?php echo esc_url( $page_link( $entry ) ); ?>"><?php echo esc_html( (string) $entry ); ?></a>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php if ( $paged < $total_pages ) : ?>
								<a class="pager__link" href="<?php echo esc_url( $page_link( $paged + 1 ) ); ?>" rel="next" aria-label="次のページ">次へ</a>
							<?php else : ?>
								<span class="pager__link is-disabled" aria-disabled="true" aria-label="次のページ">次へ</span>
							<?php endif; ?>
							<form class="pager__jump" id="pagerJump" action="<?php echo esc_url( $archive_url ); ?>" method="get">
								<label class="pager__jump-label" for="pagerInput">ページへ移動</label>
								<input class="pager__input" id="pagerInput" name="paged" type="number" min="1" max="<?php echo esc_attr( (string) $total_pages ); ?>" inputmode="numeric" placeholder="<?php echo esc_attr( (string) $paged ); ?>">
								<?php foreach ( $filter_args as $key => $value ) : ?>
									<?php if ( is_array( $value ) ) : ?>
										<?php foreach ( $value as $item ) : ?>
											<input type="hidden" name="<?php echo esc_attr( $key ); ?>[]" value="<?php echo esc_attr( $item ); ?>">
										<?php endforeach; ?>
									<?php else : ?>
										<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if ( 'menu_order' !== $orderby ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
								<button class="btn pager__btn" type="submit">移動</button>
							</form>
						</nav>
					<?php else : ?>
						<div class="woocommerce-info">条件に一致する商品がありませんでした。<a href="<?php echo esc_url( $archive_url ); ?>">すべての商品を見る</a></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
