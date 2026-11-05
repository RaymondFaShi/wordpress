<?php
/**
 * Search engine metadata and structured data.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether another SEO plugin is handling metadata.
 *
 * @return bool
 */
function vk_has_seo_plugin() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'SEOPress' );
}

/**
 * Keep the tax archive titles aligned with the Japanese static templates.
 *
 * @param string $title Current title.
 * @return string
 */
function vk_seo_archive_title( $title ) {
	if ( is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$page  = max( 1, (int) get_query_var( 'paged' ) );
			$title = $term->name . '一覧｜全' . (int) $GLOBALS['wp_query']->found_posts . '件から選べる電子タバコ通販 7s7';
			if ( $page > 1 ) {
				$title = $term->name . '一覧 ' . $page . 'ページ目｜7s7';
			}
			return $title;
		}
	}
	return $title;
}
add_filter( 'wpseo_title', 'vk_seo_archive_title', 99 );

/**
 * Supply a category-specific meta description when Yoast SEO is active.
 *
 * @param string $description Current description.
 * @return string
 */
function vk_seo_archive_description( $description ) {
	if ( is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'disposable' === $term->slug ) {
			return '使い捨てベイプの一覧ページです。吸入口数・ニコチン濃度・価格帯で絞り込み、売れ筋・新着・価格順に並び替えできます。20歳以上の方限定、正規品のみ、' . vk_free_shipping_minimum_label() . '円以上で送料無料。';
		}
	}
	return $description;
}
add_filter( 'wpseo_metadesc', 'vk_seo_archive_description', 99 );

/**
 * Return a description for the current view.
 *
 * @return string
 */
function vk_meta_description() {
	$fallback = '使い捨てベイプ・リキッド・スターターキット・シーシャを扱う20歳以上限定の電子タバコ専門店。正規品を' . vk_free_shipping_minimum_label() . '円以上送料無料でお届けします。';
	$text     = '';

	if ( is_front_page() ) {
		$text = get_bloginfo( 'description' ) ?: $fallback;
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$text = $product->get_short_description() ?: $product->get_description();
		}
	} elseif ( is_product_taxonomy() ) {
		$text = term_description();
	} elseif ( is_singular() || is_page() ) {
		$text = get_the_excerpt();
	}

	$text = wp_strip_all_tags( $text );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return wp_html_excerpt( $text ?: $fallback, 120, '…' );
}

/**
 * Canonical URL for current view.
 *
 * @return string
 */
function vk_canonical_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return wp_get_canonical_url();
	}
	if ( is_product_taxonomy() ) {
		$term_link = get_term_link( get_queried_object() );
		return is_wp_error( $term_link ) ? home_url( '/' ) : $term_link;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return vk_shop_url();
	}
	return home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Social share image.
 *
 * @return string
 */
function vk_share_image() {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$image_id  = $product->get_image_id();
			$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
			if ( $image_url ) {
				return $image_url;
			}
		}
	}
	if ( has_post_thumbnail() ) {
		return get_the_post_thumbnail_url( null, 'large' );
	}
	return vk_asset( 'images/banner/banner-01.jpg' );
}

/**
 * Meta tags, canonical and lightweight organization schema.
 */
function vk_output_seo_meta() {
	if ( vk_has_seo_plugin() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = vk_meta_description();
	$canonical   = vk_canonical_url();
	$image       = vk_share_image();
	?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>">
	<meta property="og:locale" content="ja_JP">
	<meta property="og:type" content="<?php echo is_singular() && ! is_front_page() ? 'article' : 'website'; ?>">
	<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:url" content="<?php echo esc_url( $canonical ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $image ); ?>">
	<meta name="twitter:card" content="summary_large_image">
	<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>">
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>">
	<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>">
	<?php
}
add_action( 'wp_head', 'vk_output_seo_meta', 2 );

/**
 * Replace WordPress core robots directives instead of emitting a duplicate tag.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function vk_robots_directives( $robots ) {
	unset( $robots['noindex'], $robots['nofollow'] );
	$private_page = ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() )
		|| is_page( array( 'favorites', 'reviews', 'newsletter' ) );
	if ( is_search() || is_404() || $private_page ) {
		$robots['noindex'] = true;
	} else {
		$robots['index'] = true;
	}
	$robots['follow'] = true;
	return $robots;
}
add_filter( 'wp_robots', 'vk_robots_directives' );

/**
 * Structured data for the site entity and current listing.
 */
function vk_output_structured_data() {
	$graph = array(
		array(
			'@type'       => 'Organization',
			'@id'         => home_url( '/#organization' ),
			'name'        => '7s7',
			'url'         => home_url( '/' ),
			'description' => '正規品のみを取り扱う20歳以上限定の電子タバコ・ベイプ専門通販店',
			'areaServed'  => 'JP',
		),
		array(
			'@type'      => 'WebSite',
			'@id'        => home_url( '/#website' ),
			'url'        => home_url( '/' ),
			'name'       => '7s7',
			'inLanguage' => 'ja',
			'publisher'  => array( '@id' => home_url( '/#organization' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		),
	);

	if ( is_front_page() ) {
		$products = wc_get_products( array( 'status' => 'publish', 'limit' => 10, 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales', 'order' => 'DESC' ) );
		if ( $products ) {
			$items = array();
			foreach ( $products as $index => $product ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $index + 1,
					'name'     => $product->get_name(),
					'url'      => get_permalink( $product->get_id() ),
				);
			}
			$graph[] = array(
				'@type'           => 'ItemList',
				'@id'             => home_url( '/#ranking' ),
				'name'            => '売れ筋ランキング',
				'itemListOrder'   => 'https://schema.org/ItemListOrderDescending',
				'numberOfItems'   => count( $items ),
				'itemListElement' => $items,
			);
		}
	}

	if ( is_singular( 'post' ) ) {
		$graph[] = array(
			'@type'         => 'Article',
			'headline'      => get_the_title(),
			'datePublished' => get_the_date( DATE_W3C ),
			'dateModified'  => get_the_modified_date( DATE_W3C ),
			'mainEntityOfPage' => get_permalink(),
			'author'        => array( '@type' => 'Organization', 'name' => '7s7' ),
			'publisher'     => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'vk_output_structured_data', 30 );

/**
 * Resource hints for local critical assets.
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Relation type.
 * @return array
 */
function vk_resource_hints( $urls, $relation_type ) {
	if ( is_front_page() && 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => VK_THEME_URI . '/assets/images/banner/banner-01.jpg',
			'as'   => 'image',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'vk_resource_hints', 10, 2 );

/**
 * Virtual robots.txt with sitemap.
 */
function vk_robots_txt( $output ) {
	if ( '0' === (string) get_option( 'blog_public' ) ) {
		return $output;
	}
	$output  = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nDisallow: /cart/\nDisallow: /checkout/\nDisallow: /member/\nDisallow: /my-account/\nDisallow: /favorites/\nDisallow: /?s=\n\n";
	$output .= 'Sitemap: ' . esc_url_raw( home_url( '/wp-sitemap.xml' ) ) . "\n";
	return $output;
}
add_filter( 'robots_txt', 'vk_robots_txt' );

// The theme emits one canonical after resolving the actual request.
remove_action( 'wp_head', 'rel_canonical' );

/**
 * Exclude private commerce/account pages from the public sitemap.
 *
 * @param array  $args      Sitemap query arguments.
 * @param string $post_type Post type.
 * @return array
 */
function vk_sitemap_exclude_private_pages( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}
	$meta_query   = isset( $args['meta_query'] ) ? (array) $args['meta_query'] : array();
	$meta_query[] = array(
		'key'     => '_vk_no_sitemap',
		'compare' => 'NOT EXISTS',
	);
	$args['meta_query'] = $meta_query;
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'vk_sitemap_exclude_private_pages', 10, 2 );
