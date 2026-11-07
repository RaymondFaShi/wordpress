<?php
/**
 * Brand taxonomy routes and helpers.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical brand URL.
 *
 * @param WP_Term $term Brand term.
 * @return string
 */
function vk_brand_url( $term ) {
	return home_url( '/' . $term->slug . '.html' );
}

/**
 * Filter brand links to the requested .html format.
 *
 * @param string  $url  URL.
 * @param WP_Term $term Term.
 * @return string
 */
function vk_filter_brand_link( $url, $term ) {
	return $term instanceof WP_Term && 'product_brand' === $term->taxonomy ? vk_brand_url( $term ) : $url;
}
add_filter( 'term_link', 'vk_filter_brand_link', 10, 2 );

/**
 * Return the first available brand logo URL.
 *
 * @param string $slug Brand slug.
 * @return string
 */
function vk_get_brand_image_url( $slug ) {
	foreach ( array( 'png', 'jpg', 'jpeg', 'webp', 'svg' ) as $extension ) {
		$relative = 'images/brand/' . $slug . '.' . $extension;
		if ( file_exists( VK_THEME_DIR . '/assets/' . $relative ) ) {
			return vk_asset( $relative );
		}
	}
	return '';
}

/**
 * Return only brands with an available logo image.
 *
 * @param array $args Optional get_terms arguments.
 * @return array
 */
function vk_get_brand_terms_with_images( $args = array() ) {
	$terms = get_terms(
		wp_parse_args(
			$args,
			array(
				'taxonomy'   => 'product_brand',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	return array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				return $term instanceof WP_Term && '' !== vk_get_brand_image_url( $term->slug );
			}
		)
	);
}

/**
 * Register explicit brand routes.
 */
function vk_register_brand_rewrites() {
	$terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term ) {
		add_rewrite_rule( '^' . preg_quote( $term->slug, '/' ) . '\\.html$', 'index.php?product_brand=' . $term->slug, 'top' );
	}
}
add_action( 'init', 'vk_register_brand_rewrites', 22 );

/**
 * Use the brand product archive template.
 *
 * @param string $template Template path.
 * @return string
 */
function vk_brand_template( $template ) {
	if ( is_tax( 'product_brand' ) ) {
		$custom = get_theme_file_path( 'taxonomy-product_brand.php' );
		if ( file_exists( $custom ) ) {
			return $custom;
		}
	}
	return $template;
}
add_filter( 'template_include', 'vk_brand_template', 20 );

/**
 * Remove product brand terms not present in the supplied source catalogue.
 */
function vk_prune_product_brands() {
	$allowed = (array) get_option( 'vk_catalog_brand_names', array() );
	if ( ! $allowed ) {
		return;
	}
	$terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return;
	}
	foreach ( $terms as $term ) {
		if ( ! in_array( $term->name, $allowed, true ) && 0 === (int) $term->count ) {
			wp_delete_term( $term->term_id, 'product_brand' );
		}
	}
}
