<?php
/**
 * vapekumo WooCommerce theme bootstrap.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

define( 'VK_THEME_VERSION', '1.2.0' );
define( 'VK_THEME_DIR', get_template_directory() );
define( 'VK_THEME_URI', get_template_directory_uri() );

require_once VK_THEME_DIR . '/inc/helpers.php';
require_once VK_THEME_DIR . '/inc/seo.php';
require_once VK_THEME_DIR . '/inc/home-banners.php';
require_once VK_THEME_DIR . '/inc/theme-ajax.php';
require_once VK_THEME_DIR . '/inc/woocommerce.php';
require_once VK_THEME_DIR . '/inc/account.php';
require_once VK_THEME_DIR . '/inc/catalog-taxonomy.php';
require_once VK_THEME_DIR . '/inc/brand.php';
require_once VK_THEME_DIR . '/inc/member-demo.php';
require_once VK_THEME_DIR . '/inc/checkout.php';
require_once VK_THEME_DIR . '/inc/cart-selection.php';
require_once VK_THEME_DIR . '/inc/footer-pages.php';
require_once VK_THEME_DIR . '/inc/demo-content.php';
require_once VK_THEME_DIR . '/inc/catalog-real.php';

/**
 * Theme setup.
 */
function vk_theme_setup() {
	load_theme_textdomain( 'vapekumo-woo', VK_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 480,
			'single_image_width'    => 900,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary' => 'メインメニュー',
			'footer'  => 'フッターメニュー',
		)
	);

	add_image_size( 'vk-product-card', 480, 480, true );
	add_image_size( 'vk-post-card', 800, 520, true );
}
add_action( 'after_setup_theme', 'vk_theme_setup' );

/**
 * Retire the points page and refresh rewrite rules once.
 */
function vk_retire_points_feature() {
	$version = '2026-11-06-2';
	if ( get_option( 'vk_points_feature_retired' ) === $version ) {
		return;
	}
	$points_pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'name'           => 'points',
		)
	);
	foreach ( $points_pages as $points_page ) {
		if ( 'draft' !== $points_page->post_status ) {
			wp_update_post(
				array(
					'ID'          => $points_page->ID,
					'post_status' => 'draft',
				)
			);
		}
	}
	flush_rewrite_rules( false );
	update_option( 'vk_points_feature_retired', $version, false );
}
add_action( 'init', 'vk_retire_points_feature', 45 );

/**
 * Enqueue front-end assets.
 */
function vk_enqueue_assets() {
	$css_uri = VK_THEME_URI . '/assets/css';
	$js_uri  = VK_THEME_URI . '/assets/js';
	$vendor  = VK_THEME_URI . '/assets/vendor';
	$common_version = (string) ( filemtime( VK_THEME_DIR . '/assets/css/common.css' ) ?: VK_THEME_VERSION );
	$woo_version    = (string) ( filemtime( VK_THEME_DIR . '/assets/css/woocommerce.css' ) ?: VK_THEME_VERSION );
	$script_version = (string) ( filemtime( VK_THEME_DIR . '/assets/js/theme.js' ) ?: VK_THEME_VERSION );

	wp_enqueue_style( 'vk-normalize', $vendor . '/normalize.css', array(), VK_THEME_VERSION );
	wp_enqueue_style( 'vk-bootstrap', $vendor . '/bootstrap.min.css', array( 'vk-normalize' ), VK_THEME_VERSION );
	wp_enqueue_style( 'vk-bootstrap-icons', $vendor . '/bootstrap-icons/bootstrap-icons.css', array( 'vk-bootstrap' ), VK_THEME_VERSION );
	wp_enqueue_style( 'vk-slick', $vendor . '/slick.css', array( 'vk-bootstrap' ), VK_THEME_VERSION );
	wp_enqueue_style( 'vk-common', $css_uri . '/common.css', array( 'vk-bootstrap', 'vk-bootstrap-icons' ), $common_version );

	$page_css = vk_get_page_css();
	if ( $page_css && 'checkout.css' !== $page_css ) {
		$page_version = (string) ( filemtime( VK_THEME_DIR . '/assets/css/' . $page_css ) ?: VK_THEME_VERSION );
		wp_enqueue_style( 'vk-page', $css_uri . '/' . $page_css, array( 'vk-common' ), $page_version );
	}
	if ( ( function_exists( 'is_account_page' ) && is_account_page() ) || is_page( 'favorites' ) ) {
		$category_version = (string) ( filemtime( VK_THEME_DIR . '/assets/css/category.css' ) ?: VK_THEME_VERSION );
		wp_enqueue_style( 'vk-catalog-ui', $css_uri . '/category.css', array( 'vk-page' ), $category_version );
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		wp_enqueue_style( 'vk-auth', $css_uri . '/auth.css', array( 'vk-common' ), VK_THEME_VERSION );
	}
	wp_enqueue_style( 'vk-woocommerce', $css_uri . '/woocommerce.css', array( 'vk-common' ), $woo_version );
	if ( 'checkout.css' === $page_css ) {
		$checkout_version = (string) ( filemtime( VK_THEME_DIR . '/assets/css/checkout.css' ) ?: VK_THEME_VERSION );
		wp_enqueue_style( 'vk-checkout-page', $css_uri . '/checkout.css', array( 'vk-woocommerce' ), $checkout_version );
	}

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'vk-bootstrap', $vendor . '/bootstrap.bundle.min.js', array( 'jquery' ), VK_THEME_VERSION, true );
	wp_enqueue_script( 'vk-slick', $vendor . '/slick.min.js', array( 'jquery' ), VK_THEME_VERSION, true );
	wp_enqueue_script( 'vk-theme', $js_uri . '/theme.js', array( 'jquery', 'vk-bootstrap', 'vk-slick' ), $script_version, true );

	wp_localize_script(
		'vk-theme',
		'vapekumoSettings',
		array(
			'restUrl'        => esc_url_raw( rest_url( 'vapekumo/v1/' ) ),
			'homeUrl'        => home_url( '/' ),
			'shopUrl'        => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'cartUrl'        => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
			'checkoutUrl'    => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
			'accountUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/member/' ),
			'favoritesUrl'   => vk_account_url( 'favorites' ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'isLoggedIn'     => is_user_logged_in(),
			'cartCount'      => vk_get_cart_count(),
			'newsletterText' => 'ご登録ありがとうございます。次回の更新をお待ちください。',
		)
	);

	if ( function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'vk_enqueue_assets', 20 );

/**
 * Generate root-level product permalinks.
 *
 * @param string  $permalink Current permalink.
 * @param WP_Post $post      Post object.
 * @return string
 */
function vk_root_product_permalink( $permalink, $post ) {
	if ( $post instanceof WP_Post && 'product' === $post->post_type && 'publish' === $post->post_status ) {
		return home_url( '/' . $post->post_name . '.html' );
	}
	return $permalink;
}
add_filter( 'post_type_link', 'vk_root_product_permalink', 10, 2 );

/**
 * Resolve root-level product URLs at request time so newly published products
 * do not depend on a stale cached rewrite-rule set.
 *
 * @param WP $wp Current request.
 */
function vk_resolve_root_product_request( $wp ) {
	if ( ! $wp instanceof WP || ! isset( $wp->request ) ) {
		return;
	}
	$path = trim( (string) $wp->request, '/' );
	if ( ! preg_match( '#^([^/]+)\.html$#u', $path, $matches ) ) {
		return;
	}
	$slug    = sanitize_title( rawurldecode( $matches[1] ) );
	$product = $slug ? get_page_by_path( $slug, OBJECT, 'product' ) : null;
	if ( ! $product instanceof WP_Post || 'publish' !== $product->post_status ) {
		return;
	}
	$wp->query_vars['post_type'] = 'product';
	$wp->query_vars['product']   = $slug;
	$wp->did_permalink           = true;
}
add_action( 'parse_request', 'vk_resolve_root_product_request', 5 );

/**
 * Refresh cached product slugs and rewrite rules when a product changes.
 *
 * @param int $post_id Product post ID.
 */
function vk_refresh_root_product_rewrites( $post_id ) {
	static $refreshed = false;
	if ( $refreshed || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( 'product' !== get_post_type( $post_id ) || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ) {
		return;
	}
	$refreshed = true;
	delete_transient( 'vk_root_product_slugs' );
	flush_rewrite_rules( false );
}
add_action( 'save_post_product', 'vk_refresh_root_product_rewrites', 20 );
add_action( 'trashed_post', 'vk_refresh_root_product_rewrites', 20 );
add_action( 'untrashed_post', 'vk_refresh_root_product_rewrites', 20 );
add_action( 'deleted_post', 'vk_refresh_root_product_rewrites', 20 );

/**
 * Add explicit root rules only for real product slugs so normal pages keep
 * their existing one-segment permalinks.
 */
function vk_register_root_product_rewrites() {
	$rewrite_version = '2026-11-12-1';
	$needs_sync      = get_option( 'vk_root_product_rewrite_version' ) !== $rewrite_version;
	if ( $needs_sync ) {
		delete_transient( 'vk_root_product_slugs' );
	}
	$product_slugs = get_transient( 'vk_root_product_slugs' );
	if ( ! is_array( $product_slugs ) ) {
		$product_slugs = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		$product_slugs = array_values(
			array_filter(
				array_map(
					static function ( $product_id ) {
						return get_post_field( 'post_name', $product_id );
					},
					$product_slugs
				)
			)
		);
		set_transient( 'vk_root_product_slugs', $product_slugs, DAY_IN_SECONDS );
	}
	foreach ( $product_slugs as $slug ) {
		add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '\\.html$', 'index.php?product=' . $slug, 'top' );
		add_rewrite_rule( '^' . preg_quote( $slug, '/' ) . '/?$', 'index.php?product=' . $slug, 'top' );
	}

	add_rewrite_rule( '^member/?$', 'index.php?pagename=member', 'top' );
	add_rewrite_rule( '^member/logout/?$', 'index.php?pagename=member&vk_logout=1', 'top' );
	foreach ( array( 'orders', 'edit-account', 'edit-address', 'favorites', 'reviews', 'newsletter', 'view-order', 'lost-password', 'customer-logout' ) as $endpoint ) {
		add_rewrite_rule(
			'^member/' . preg_quote( $endpoint, '/' ) . '(?:/([^/]+))?/?$',
			'index.php?pagename=member&' . $endpoint . '=$matches[1]',
			'top'
		);
	}
	if ( $needs_sync ) {
		flush_rewrite_rules( false );
		update_option( 'vk_root_product_rewrite_version', $rewrite_version, false );
	}
}
add_action( 'init', 'vk_register_root_product_rewrites', 20 );

/**
 * Register the custom member logout query var.
 *
 * @param array $vars Public query vars.
 * @return array
 */
function vk_member_logout_query_var( $vars ) {
	$vars[] = 'vk_logout';
	return $vars;
}
add_filter( 'query_vars', 'vk_member_logout_query_var' );

/**
 * Build the nonce-protected member logout URL.
 *
 * @param string $redirect Optional redirect destination.
 * @return string
 */
function vk_member_logout_url( $redirect = '' ) {
	$target = $redirect ?: vk_account_url();
	return wp_nonce_url(
		add_query_arg( 'redirect_to', $target, vk_account_url( 'logout' ) ),
		'vk_member_logout'
	);
}

/**
 * Replace core logout links with the front-end member logout URL.
 *
 * @param string $logout_url Core logout URL.
 * @param string $redirect   Redirect destination.
 * @return string
 */
function vk_filter_member_logout_url( $logout_url, $redirect ) {
	if ( is_admin() ) {
		return $logout_url;
	}
	return vk_member_logout_url( $redirect );
}
add_filter( 'logout_url', 'vk_filter_member_logout_url', 10, 2 );

/**
 * Handle logout through /member/logout/.
 */
function vk_handle_member_logout() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( 'member/customer-logout' === $path ) {
		wp_safe_redirect( vk_member_logout_url(), 302 );
		exit;
	}
	if ( 'member/logout' !== $path || ! is_user_logged_in() ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'vk_member_logout' ) ) {
		wp_die( 'ログアウトリンクが無効です。', 'ログアウトできません', array( 'response' => 403 ) );
	}

	wp_logout();
	$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : vk_account_url();
	wp_safe_redirect( $redirect ?: vk_account_url() );
	exit;
}
add_action( 'template_redirect', 'vk_handle_member_logout', 1 );

/**
 * Track product searches for the popular-keyword strip.
 *
 * @param WP_Query $query Query.
 */
function vk_track_product_search_keywords( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	if ( $post_type && 'product' !== $post_type ) {
		return;
	}
	$keyword = trim( sanitize_text_field( (string) $query->get( 's' ) ) );
	if ( '' === $keyword || mb_strlen( $keyword ) > 60 ) {
		return;
	}
	$counts = (array) get_option( 'vk_search_keyword_counts', array() );
	$counts[ $keyword ] = (int) ( $counts[ $keyword ] ?? 0 ) + 1;
	arsort( $counts );
	update_option( 'vk_search_keyword_counts', array_slice( $counts, 0, 30, true ), false );
}
add_action( 'pre_get_posts', 'vk_track_product_search_keywords', 4 );

/**
 * Redirect legacy /product/{slug}/ links to root-level product URLs.
 */
function vk_redirect_legacy_product_url() {
	if ( is_admin() || ! is_singular( 'product' ) ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$legacy_slug = preg_match( '#^product/([^/]+)/?$#', $path, $matches ) ? $matches[1] : ( preg_match( '#^([^/]+)/?$#', $path, $matches ) ? $matches[1] : '' );
	if ( $legacy_slug && ! str_ends_with( $legacy_slug, '.html' ) ) {
		$product_post = get_page_by_path( $legacy_slug, OBJECT, 'product' );
		if ( $product_post ) {
			wp_safe_redirect( home_url( '/' . $product_post->post_name . '.html' ), 301 );
			exit;
		}
	}
	if ( 0 === strpos( $path, 'product/' ) && is_singular( 'product' ) ) {
		wp_safe_redirect( get_permalink( get_queried_object_id() ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'vk_redirect_legacy_product_url', 2 );

/**
 * Preserve old account and favorites URLs after moving account pages to /member.
 */
function vk_legacy_member_redirects() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( 'favorites' === $path ) {
		wp_safe_redirect( trailingslashit( vk_account_url( 'favorites' ) ), 301 );
		exit;
	}
	if ( 'my-account' === $path || 0 === strpos( $path, 'my-account/' ) ) {
		$suffix = ltrim( substr( $path, strlen( 'my-account' ) ), '/' );
		$target = vk_account_url( $suffix );
		wp_safe_redirect( $target, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'vk_legacy_member_redirects', 1 );

/**
 * .html permalinks are canonical; do not add a trailing slash.
 *
 * @param string|false $redirect  Redirect URL.
 * @param string       $requested Requested URL.
 * @return string|false
 */
function vk_prevent_html_trailing_slash( $redirect, $requested ) {
	return str_ends_with( (string) wp_parse_url( $requested, PHP_URL_PATH ), '.html' ) ? false : $redirect;
}
add_filter( 'redirect_canonical', 'vk_prevent_html_trailing_slash', 10, 2 );

/**
 * Choose page-specific stylesheet.
 *
 * @return string
 */
function vk_get_page_css() {
	if ( is_front_page() ) {
		return 'home.css';
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return 'product.css';
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return 'category.css';
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return 'cart.css';
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return 'checkout.css';
	}
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return 'user.css';
	}
	if ( is_404() ) {
		return 'error.css';
	}
	if ( is_singular( 'post' ) || is_home() ) {
		return 'blog.css';
	}
	if ( is_page( 'brand' ) ) {
		return 'brand.css';
	}
	if ( is_page( 'contact' ) ) {
		return 'contact.css';
	}
	if ( is_page( 'sitemap' ) ) {
		return 'sitemap.css';
	}
	if ( is_page( array( 'reviews', 'newsletter' ) ) ) {
		return 'user.css';
	}
	if ( is_page( 'favorites' ) && is_user_logged_in() ) {
		return 'user.css';
	}
	if ( is_page() ) {
		return 'guide.css';
	}
	return 'category.css';
}

/**
 * Body classes.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function vk_body_classes( $classes ) {
	$classes[] = 'vk-theme';
	if ( is_front_page() ) {
		$classes[] = 'vk-front-page';
	}
	return $classes;
}
add_filter( 'body_class', 'vk_body_classes' );

/**
 * The local demo host has no SMTP service. Short-circuit mail so checkout
 * never waits on a non-existent local mail transfer agent.
 *
 * @param mixed $return Short-circuit value.
 * @param array $atts   wp_mail arguments.
 * @return mixed
 */
function vk_local_mail_short_circuit( $return, $atts ) {
	$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( in_array( $host, array( 'wpshop.com', '192.168.88.2' ), true ) ) {
		return true;
	}
	return $return;
}
add_filter( 'pre_wp_mail', 'vk_local_mail_short_circuit', 10, 2 );

/**
 * Main query tuning.
 *
 * @param WP_Query $query Query.
 */
function vk_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( function_exists( 'is_shop' ) && ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) ) {
		$per_page = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 20; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $per_page, array( 20, 40, 60 ), true ) ) {
			$per_page = 20;
		}
		$query->set( 'posts_per_page', $per_page );
		$meta_query = (array) $query->get( 'meta_query' );

		$min_price = isset( $_GET['vk_min_price'] ) ? wc_clean( wp_unslash( $_GET['vk_min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max_price = isset( $_GET['vk_max_price'] ) ? wc_clean( wp_unslash( $_GET['vk_max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nicotine   = isset( $_GET['nicotine'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['nicotine'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$puffs      = isset( $_GET['puffs'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['puffs'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $min_price ) {
			$meta_query[] = array( 'key' => '_price', 'value' => (float) $min_price, 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( '' !== $max_price ) {
			$meta_query[] = array( 'key' => '_price', 'value' => (float) $max_price, 'compare' => '<=', 'type' => 'NUMERIC' );
		}

		$nicotine_taxonomy = vk_catalog_filter_taxonomy( 'nicotine' );
		$puff_taxonomy     = vk_catalog_filter_taxonomy( 'puffs' );
		$nicotine_slugs    = wp_list_pluck( vk_catalog_filter_terms( 'nicotine' ), 'slug' );
		$puff_slugs        = wp_list_pluck( vk_catalog_filter_terms( 'puffs' ), 'slug' );
		$nicotine          = array_values( array_intersect( $nicotine, $nicotine_slugs ) );
		$puffs             = array_values( array_intersect( $puffs, $puff_slugs ) );
		if ( count( $meta_query ) > 1 ) {
			$meta_query['relation'] = 'AND';
		}
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}
		if ( $nicotine || $puffs ) {
			$tax_query = (array) $query->get( 'tax_query' );
			if ( $nicotine && $nicotine_taxonomy ) {
				$tax_query[] = array(
					'taxonomy' => $nicotine_taxonomy,
					'field'    => 'slug',
					'terms'    => $nicotine,
					'operator' => 'IN',
				);
			}
			if ( $puffs && $puff_taxonomy ) {
				$tax_query[] = array(
					'taxonomy' => $puff_taxonomy,
					'field'    => 'slug',
					'terms'    => $puffs,
					'operator' => 'IN',
				);
			}
			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
			$query->set( 'tax_query', $tax_query );
		}

		$orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'popularity' === $orderby ) {
			$query->set( 'meta_key', 'total_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
		} elseif ( 'date' === $orderby ) {
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
		} elseif ( 'price' === $orderby ) {
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
		} elseif ( 'price-desc' === $orderby ) {
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
		}
	}
}
add_action( 'pre_get_posts', 'vk_pre_get_posts' );

/**
 * Match the static catalogue card wording while WooCommerce still opens the
 * product page when a shopper needs to choose a variation.
 *
 * @param string     $text Button text.
 * @param WC_Product $product Product.
 * @return string
 */
function vk_loop_add_to_cart_text( $text, $product ) {
	if ( $product instanceof WC_Product && $product->is_in_stock() ) {
		return 'カートに入れる';
	}
	return $text;
}
add_filter( 'woocommerce_product_add_to_cart_text', 'vk_loop_add_to_cart_text', 10, 2 );

/**
 * Remove WordPress emoji payload.
 */
function vk_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'vk_disable_emojis' );

/**
 * Keep the theme URLs correct after moving between local hosts.
 *
 * @param mixed $url URL or Customizer value.
 * @return mixed
 */
function vk_filter_asset_url( $url ) {
	if ( ! is_string( $url ) ) {
		return $url;
	}
	return str_replace( array( 'http://vapekumo.jp', 'https://vapekumo.jp' ), home_url( '' ), $url );
}
add_filter( 'theme_mod_custom_logo', 'vk_filter_asset_url' );
