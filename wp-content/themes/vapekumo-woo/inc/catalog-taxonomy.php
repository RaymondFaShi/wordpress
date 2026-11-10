<?php
/**
 * Catalogue taxonomy hierarchy, links and archive metadata.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the category tree used by the storefront.
 *
 * @return array
 */
function vk_catalog_category_tree() {
	return array(
		'recommend' => array(
			'name'        => 'おすすめ',
			'description' => '<p>7s7が選ぶ人気商品を、用途別にわかりやすくまとめました。</p>',
			'image'       => 'images/banner/banner-01.jpg',
			'children'    => array(
				'disposable-vape' => array( '使い捨て電子タバコ', 'images/category/cat-disposable.jpg' ),
				'e-liquid'        => array( 'リキッド', 'images/category/cat-fruit.jpg' ),
				'starter-kit'     => array( 'スターターキット', 'images/category/cat-starter.jpg' ),
				'recommended-hookah' => array( 'シーシャ', 'images/category/cat-shisha.jpg' ),
			),
		),
		'new-products' => array(
			'name'        => '新商品',
			'description' => '<p>新しく入荷したデバイスやフレーバーをいち早くご紹介します。</p>',
			'image'       => 'images/banner/banner-02.jpg',
			'children'    => array(
				'weekly-arrivals' => array( '今週入荷', 'images/banner/banner-02.jpg' ),
				'new-e-liquid'    => array( '新作リキッド', 'images/category/cat-fruit.jpg' ),
				'new-devices'     => array( '新作デバイス', 'images/category/cat-disposable.jpg' ),
				'restocked'       => array( '再入荷商品', 'images/category/cat-nicotine.jpg' ),
			),
		),
		'disposable' => array(
			'name'        => '使い捨てベイプ',
			'description' => '<p>充電やコイル交換なしですぐ使える使い捨てベイプ。吸入口数、フレーバー、まとめ買いなど目的別に選べます。</p>',
			'image'       => 'images/category/cat-disposable.jpg',
			'children'    => array(
				'over-5000-puffs' => array( '5,000回以上', 'images/banner/banner-03.jpg' ),
				'up-to-3000-puffs'=> array( '3,000回以下', 'images/category/cat-starter.jpg' ),
				'fruit-flavors'   => array( 'フルーツ味', 'images/category/cat-fruit.jpg' ),
				'wholesale'       => array( 'まとめ買い', 'images/banner/banner-01.jpg' ),
			),
		),
		'sale' => array(
			'name'        => 'セール',
			'description' => '<p>期間限定価格や在庫限りの商品をまとめています。</p>',
			'image'       => 'images/banner/banner-04.jpg',
			'children'    => array(
				'clearance'       => array( 'クリアランス', 'images/banner/banner-04.jpg' ),
				'limited-quantity'=> array( '数量限定', 'images/category/cat-nicotine.jpg' ),
				'bulk-discount'   => array( 'まとめ買い割引', 'images/banner/banner-01.jpg' ),
			),
		),
		'starter-kits' => array(
			'name'        => 'スターターキット',
			'description' => '<p>初めての方にも扱いやすい本体セットです。</p>',
			'image'       => 'images/category/cat-starter.jpg',
			'children'    => array(),
		),
		'hookah' => array(
			'name'        => 'シーシャ',
			'description' => '<p>シーシャ本体、フレーバー、アクセサリーを取り扱っています。</p>',
			'image'       => 'images/category/cat-shisha.jpg',
			'children'    => array(),
		),
		'heated-tobacco-products' => array(
			'name'        => '加熱式タバコ',
			'description' => '<p>加熱式デバイスと関連商品のカテゴリーです。</p>',
			'image'       => 'images/banner/banner-02.jpg',
			'children'    => array(),
		),
		'liquids' => array(
			'name'        => 'リキッド',
			'description' => '<p>フレーバーの系統やニコチン有無からリキッドを選べます。</p>',
			'image'       => 'images/category/cat-fruit.jpg',
			'children'    => array(
				'fruit'         => array( 'フルーツ', 'images/category/cat-fruit.jpg' ),
				'menthol'       => array( 'メンソール', 'images/category/cat-nicotine.jpg' ),
				'dessert'       => array( 'スイーツ', 'images/banner/banner-01.jpg' ),
				'nicotine-free' => array( 'ノンニコチン', 'images/category/cat-disposable.jpg' ),
			),
		),
		'accessories' => array(
			'name'        => 'アクセサリー',
			'description' => '<p>ポッド、コイル、充電器、収納用品などの交換パーツです。</p>',
			'image'       => 'images/category/cat-atomizer.jpg',
			'children'    => array(
				'pods'          => array( 'ポッド', 'images/category/cat-disposable.jpg' ),
				'coils'         => array( 'コイル', 'images/category/cat-atomizer.jpg' ),
				'chargers'      => array( '充電器', 'images/banner/banner-02.jpg' ),
				'cases-storage' => array( 'ケース・収納', 'images/category/cat-starter.jpg' ),
			),
		),
		'cheap-vapes' => array(
			'name'        => '廉価ベイプ',
			'description' => '<p>手に取りやすい価格帯のベイプをまとめました。</p>',
			'image'       => 'images/category/cat-starter.jpg',
			'children'    => array(),
		),
	);
}

/**
 * Build the storefront navigation directly from WooCommerce product categories.
 *
 * @return array
 */
function vk_get_storefront_category_navigation() {
	$roots = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $roots ) || ! $roots ) {
		return array();
	}

	$default_order = array_flip(
		array(
			'recommend',
			'new-products',
			'disposable',
			'sale',
			'starter-kits',
			'hookah',
			'heated-tobacco-products',
			'liquids',
			'accessories',
			'cheap-vapes',
		)
	);
	$fallback_images = array(
		'recommend'               => 'images/banner/banner-01.jpg',
		'new-products'            => 'images/banner/banner-02.jpg',
		'disposable'              => 'images/banner/banner-03.jpg',
		'sale'                    => 'images/banner/banner-04.jpg',
		'starter-kits'            => 'images/category/cat-starter.jpg',
		'hookah'                  => 'images/category/cat-shisha.jpg',
		'heated-tobacco-products' => 'images/banner/banner-02.jpg',
		'liquids'                 => 'images/category/cat-fruit.jpg',
		'accessories'             => 'images/category/cat-nicotine.jpg',
		'cheap-vapes'             => 'images/category/cat-atomizer.jpg',
	);

	usort(
		$roots,
		static function ( $left, $right ) use ( $default_order ) {
			$left_order  = $default_order[ $left->slug ] ?? PHP_INT_MAX;
			$right_order = $default_order[ $right->slug ] ?? PHP_INT_MAX;
			return $left_order === $right_order ? strcasecmp( $left->name, $right->name ) : $left_order <=> $right_order;
		}
	);

	$navigation = array();
	foreach ( $roots as $term ) {
		$children = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $term->term_id,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		$url      = get_term_link( $term );
		$image    = vk_product_category_image_url( $term, 'large' );
		$image    = $image ?: vk_asset( $fallback_images[ $term->slug ] ?? 'images/banner/banner-01.jpg' );
		$navigation[] = array(
			'type'     => 'category',
			'slug'     => $term->slug,
			'term'     => $term,
			'label'    => $term->name,
			'url'      => is_wp_error( $url ) ? home_url( '/' ) : $url,
			'children' => is_wp_error( $children ) ? array() : $children,
			'image'    => $image,
		);
	}

	return $navigation;
}

/**
 * Ensure category names, slugs, hierarchy and thumbnails.
 *
 * @return array Map of slug to term ID.
 */
function vk_ensure_catalog_categories() {
	$definitions = vk_catalog_category_tree();
	$ids         = array();
	foreach ( $definitions as $slug => $parent ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			$created = wp_insert_term(
				$parent['name'],
				'product_cat',
				array(
					'slug'        => $slug,
					'description' => $parent['description'],
					'parent'      => 0,
				)
			);
			$term = is_wp_error( $created ) ? null : get_term( $created['term_id'], 'product_cat' );
		} else {
			wp_update_term(
				$term->term_id,
				'product_cat',
				array(
					'name'        => $parent['name'],
					'slug'        => $slug,
					'description' => $parent['description'],
					'parent'      => 0,
				)
			);
			$term = get_term( $term->term_id, 'product_cat' );
		}
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$ids[ $slug ] = (int) $term->term_id;
		update_term_meta( $term->term_id, '_vk_category_image', vk_asset( $parent['image'] ) );

		foreach ( $parent['children'] as $child_slug => $child ) {
			$child_term = get_term_by( 'slug', $child_slug, 'product_cat' );
			if ( ! $child_term ) {
				$created = wp_insert_term(
					$child[0],
					'product_cat',
					array(
						'slug'   => $child_slug,
						'parent' => $term->term_id,
					)
				);
				$child_term = is_wp_error( $created ) ? null : get_term( $created['term_id'], 'product_cat' );
			} else {
				wp_update_term(
					$child_term->term_id,
					'product_cat',
					array(
						'name'   => $child[0],
						'slug'   => $child_slug,
						'parent' => $term->term_id,
					)
				);
				$child_term = get_term( $child_term->term_id, 'product_cat' );
			}
			if ( $child_term && ! is_wp_error( $child_term ) ) {
				$ids[ $child_slug ] = (int) $child_term->term_id;
				update_term_meta( $child_term->term_id, '_vk_category_image', vk_asset( $child[1] ) );
			}
		}
	}

	$allowed = array_keys( $ids );
	$all     = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	foreach ( $all as $term ) {
		if ( ! in_array( $term->slug, $allowed, true ) && 0 === (int) $term->count ) {
			wp_delete_term( $term->term_id, 'product_cat' );
		}
	}
	return $ids;
}

/**
 * Build an SEO category URL ending in .html.
 *
 * @param WP_Term $term Term.
 * @return string
 */
function vk_category_url( $term ) {
	$parts = array( $term->slug );
	$parent_id = (int) $term->parent;
	while ( $parent_id ) {
		$parent = get_term( $parent_id, 'product_cat' );
		if ( ! $parent || is_wp_error( $parent ) ) {
			break;
		}
		array_unshift( $parts, $parent->slug );
		$parent_id = (int) $parent->parent;
	}
	return home_url( '/' . implode( '/', $parts ) . '.html' );
}

/**
 * Replace product category links with .html links.
 *
 * @param string  $url  URL.
 * @param WP_Term $term Term.
 * @return string
 */
function vk_filter_product_category_link( $url, $term ) {
	return $term instanceof WP_Term && 'product_cat' === $term->taxonomy ? vk_category_url( $term ) : $url;
}
add_filter( 'term_link', 'vk_filter_product_category_link', 10, 2 );

/**
 * Resolve .html category paths even when WordPress' canonical page matcher
 * would otherwise claim the request first.
 *
 * @param WP $wp Current request.
 */
function vk_resolve_html_category_request( $wp ) {
	$request_path = trim( (string) $wp->request, '/' );
	if ( ! $request_path || ! str_ends_with( $request_path, '.html' ) ) {
		return;
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term ) {
		$term_path = trim( (string) wp_parse_url( vk_category_url( $term ), PHP_URL_PATH ), '/' );
		if ( $request_path === $term_path ) {
			$wp->query_vars['product_cat'] = $term->slug;
			$wp->did_permalink = true;
			return;
		}
	}
}
add_action( 'parse_request', 'vk_resolve_html_category_request', 5 );

/**
 * Register category .html rewrite rules.
 */
function vk_register_category_html_rewrites() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term ) {
		$url  = vk_category_url( $term );
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		if ( $path ) {
			add_rewrite_rule( '^' . preg_quote( $path, '/' ) . '$', 'index.php?product_cat=' . $term->slug, 'top' );
		}
	}
}
add_action( 'init', 'vk_register_category_html_rewrites', 21 );

/**
 * Redirect old /product-category/{slug}/ URLs to the SEO .html path.
 */
function vk_redirect_legacy_category_url() {
	if ( is_admin() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( preg_match( '#^product-category/([^/]+)/?$#', $path, $matches ) ) {
		$term = get_term_by( 'slug', $matches[1], 'product_cat' );
		if ( $term ) {
			wp_safe_redirect( vk_category_url( $term ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'vk_redirect_legacy_category_url', 1 );
