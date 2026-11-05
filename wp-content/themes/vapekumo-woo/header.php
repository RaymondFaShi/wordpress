<?php
/**
 * Site header.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

$navigation = vk_get_storefront_category_navigation();
$navigation[] = array(
	'type'     => 'brand',
	'slug'     => 'brand',
	'label'    => 'ブランド',
	'url'      => home_url( '/brand/' ),
	'children' => array(),
	'image'    => vk_asset( 'images/banner/banner-01.jpg' ),
);
$brand_terms  = vk_get_brand_terms_with_images();
$brand_logos  = array();
if ( $brand_terms && ! is_wp_error( $brand_terms ) ) {
	foreach ( $brand_terms as $brand_term ) {
		$brand_logos[ $brand_term->slug ] = array(
			'name' => $brand_term->name,
			'url'  => vk_get_brand_image_url( $brand_term->slug ),
		);
	}
}
$current_nav = '';
$resolve_root_category = static function ( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	while ( $term instanceof WP_Term && (int) $term->parent ) {
		$parent = get_term( (int) $term->parent, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		$term = $parent;
	}
	return $term instanceof WP_Term ? $term : null;
};
if ( is_front_page() || ( function_exists( 'is_shop' ) && is_shop() ) ) {
	$current_nav = isset( $_GET['orderby'] ) && 'date' === sanitize_key( wp_unslash( $_GET['orderby'] ) ) ? 'new-products' : 'recommend'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif ( function_exists( 'is_product' ) && is_product() ) {
	$terms = get_the_terms( get_queried_object_id(), 'product_cat' );
	$terms = $terms && ! is_wp_error( $terms ) ? $terms : array();
	foreach ( $terms as $product_term ) {
		$root_term = $resolve_root_category( $product_term );
		$current_nav = $root_term instanceof WP_Term ? $root_term->slug : '';
		if ( $current_nav ) {
			break;
		}
	}
} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
	$root_term   = $resolve_root_category( get_queried_object() );
	$current_nav = $root_term instanceof WP_Term ? $root_term->slug : '';
} elseif ( is_page( 'brand' ) ) {
	$current_nav = 'brand';
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">本文へスキップ</a>

<div class="age-notice">
	<div class="container age-notice__inner">
		<i class="bi bi-info-circle age-notice__icon" aria-hidden="true"></i>
		<p>20歳未満の方への販売はお断りしております。法律により、未成年者の喫煙は禁止されています。</p>
	</div>
</div>

<header class="site-header" id="siteHeader">
	<div class="container site-header__main">
		<?php vk_logo(); ?>
		<?php vk_search_form(); ?>

		<div class="site-header__actions">
			<div class="dropdown account-menu js-hover-dropdown">
				<button class="account-menu__toggle" type="button" id="accountToggle"
					data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true"
					aria-expanded="false" aria-haspopup="true">
					<i class="bi bi-person-fill account-menu__icon" aria-hidden="true"></i>
					<span><?php echo is_user_logged_in() ? 'マイアカウント' : 'ログイン'; ?></span>
				</button>
				<ul class="dropdown-menu account-menu__panel" aria-labelledby="accountToggle">
					<?php if ( is_user_logged_in() ) : ?>
						<?php $current_user = wp_get_current_user(); ?>
						<li class="account-menu__hello">ようこそ、<?php echo esc_html( vk_current_member_display_name() ); ?>様<span>いつもご利用ありがとうございます</span></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url() ); ?>">マイアカウント</a></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">注文履歴</a></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url( 'reviews' ) ); ?>">投稿したレビュー</a></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>">お気に入り</a></li>
						<li><a class="dropdown-item account-menu__logout" href="<?php echo esc_url( wc_logout_url() ); ?>">ログアウト</a></li>
					<?php else : ?>
						<li class="account-menu__hello">7s7 へようこそ<span>ログインして、お買い物をもっと便利に。</span></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url() ); ?>">ログイン・会員登録</a></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>">お気に入り</a></li>
						<li><a class="dropdown-item" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">ゲスト注文を確認</a></li>
					<?php endif; ?>
				</ul>
			</div>

			<div class="dropdown fav-menu js-hover-dropdown">
				<a class="fav-menu__toggle" href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>" id="favToggle"
					data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="お気に入り（<?php echo esc_attr( (string) vk_get_user_favorite_count() ); ?>点）">
					<i class="bi bi-heart fav-menu__icon" aria-hidden="true"></i>
					<span class="fav-menu__badge" data-fav-count><?php echo esc_html( (string) vk_get_user_favorite_count() ); ?></span>
				</a>
				<div class="dropdown-menu fav-menu__panel">
					<?php $header_favorites = vk_get_user_favorite_products( 3 ); ?>
					<div class="fav-menu__list" data-fav-header-list>
						<?php foreach ( $header_favorites as $favorite_product ) : ?>
							<a class="fav-menu__row" href="<?php echo esc_url( get_permalink( $favorite_product->get_id() ) ); ?>">
								<?php echo $favorite_product->get_image( 'thumbnail', array( 'class' => 'fav-menu__thumb', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<div>
									<p class="fav-menu__name"><?php echo esc_html( $favorite_product->get_name() ); ?></p>
									<p class="fav-menu__price"><?php echo wp_kses_post( $favorite_product->get_price_html() ); ?></p>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
					<p class="fav-menu__empty" data-fav-header-empty<?php echo $header_favorites ? ' hidden' : ''; ?>>お気に入りは商品カードのハートから追加できます。</p>
					<a class="btn btn-line fav-menu__btn" href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>">お気に入りを見る</a>
				</div>
			</div>

			<div class="dropdown cart-menu js-hover-dropdown">
				<a class="cart-menu__toggle" href="<?php echo esc_url( wc_get_cart_url() ); ?>" id="cartToggle"
					data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="カート">
					<i class="bi bi-cart cart-menu__icon" aria-hidden="true"></i>
					<span class="cart-menu__badge"><?php echo esc_html( (string) vk_get_cart_count() ); ?></span>
				</a>
				<div class="dropdown-menu cart-menu__panel">
					<?php vk_mini_cart(); ?>
					<a class="btn btn-cta cart-menu__btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>">カートを見る</a>
				</div>
			</div>

			<button class="site-header__burger" type="button" aria-label="メニューを開く"
				data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>

	<div class="site-keywords" id="siteKeywords">
		<div class="container site-keywords__inner">
			<span class="site-keywords__label">人気キーワード：</span>
			<?php foreach ( vk_popular_search_keywords( 6 ) as $keyword ) : ?>
				<a href="<?php echo esc_url( $keyword['url'] ); ?>"><?php echo esc_html( $keyword['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>

	<nav class="site-nav" aria-label="メインメニュー">
		<div class="container">
			<ul class="site-nav__list">
				<?php
				foreach ( $navigation as $nav_item ) :
					$is_brand    = 'brand' === $nav_item['type'];
					$has_children = ! empty( $nav_item['children'] );
					$has_mega     = $is_brand || $has_children;
					?>
					<li class="site-nav__item">
						<a class="site-nav__link" href="<?php echo esc_url( $nav_item['url'] ); ?>"<?php echo $current_nav === $nav_item['slug'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $nav_item['label'] ); ?></a>
						<?php if ( $has_mega ) : ?>
							<div class="mega<?php echo $is_brand ? ' mega--wide' : ''; ?>">
								<div class="mega__inner">
									<?php if ( $is_brand ) : ?>
										<div class="mega__brands">
											<?php foreach ( $brand_logos as $logo_slug => $logo ) : ?>
												<?php $brand_term = get_term_by( 'slug', $logo_slug, 'product_brand' ); ?>
												<a href="<?php echo esc_url( $brand_term ? get_term_link( $brand_term ) : home_url( '/' . $logo_slug . '.html' ) ); ?>"><img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['name'] ); ?>" width="216" height="82" loading="lazy"></a>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<ul class="mega__list">
											<?php foreach ( $nav_item['children'] as $child_term ) : ?>
												<?php $child_url = get_term_link( $child_term ); ?>
												<li><a href="<?php echo esc_url( is_wp_error( $child_url ) ? $nav_item['url'] : $child_url ); ?>"><?php echo esc_html( $child_term->name ); ?></a></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
									<a class="mega__promo" href="<?php echo esc_url( $nav_item['url'] ); ?>">
										<img src="<?php echo esc_url( $nav_item['image'] ); ?>" alt="<?php echo esc_attr( $nav_item['label'] . '特集' ); ?>" width="649" height="400" loading="lazy">
										<span class="mega__promo-label"><?php echo esc_html( $nav_item['label'] ); ?>を見る</span>
									</a>
								</div>
							</div>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
</header>
<div class="header-spacer" id="headerSpacer" aria-hidden="true"></div>

<div class="offcanvas offcanvas-end mobile-nav" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
	<div class="offcanvas-header">
		<p class="mobile-nav__title" id="mobileNavLabel">メニュー</p>
		<button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="閉じる"></button>
	</div>
	<div class="offcanvas-body">
		<p class="mobile-nav__heading">カテゴリー</p>
		<ul class="mobile-nav__list">
			<?php foreach ( $navigation as $nav_item ) : ?>
				<li><a href="<?php echo esc_url( $nav_item['url'] ); ?>"><?php echo esc_html( $nav_item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<p class="mobile-nav__heading">マイメニュー</p>
		<ul class="mobile-nav__list">
			<li><a href="<?php echo esc_url( vk_account_url() ); ?>">マイアカウント</a></li>
			<li><a href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">注文履歴</a></li>
			<li><a href="<?php echo esc_url( vk_account_url( 'reviews' ) ); ?>">投稿したレビュー</a></li>
			<li><a href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>">お気に入り</a></li>
			<li><a href="<?php echo esc_url( vk_account_url( 'edit-address' ) ); ?>">アドレス帳</a></li>
			<li><a href="<?php echo esc_url( vk_account_url( 'newsletter' ) ); ?>">メール配信設定</a></li>
			<?php if ( is_user_logged_in() ) : ?>
				<li><a href="<?php echo esc_url( wc_logout_url() ); ?>">ログアウト</a></li>
			<?php endif; ?>
		</ul>
		<a class="btn btn-cta mobile-nav__login" href="<?php echo esc_url( is_user_logged_in() ? vk_account_url() : vk_account_url() ); ?>">
			<?php echo is_user_logged_in() ? 'マイアカウントへ' : 'ログイン'; ?>
		</a>
		<p class="mobile-nav__notice">20歳未満の方への販売はお断りしております。法律により、未成年者の喫煙は禁止されています。</p>
	</div>
</div>
