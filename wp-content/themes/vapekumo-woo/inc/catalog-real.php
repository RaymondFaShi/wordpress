<?php
/**
 * Import the supplied product catalogue as WooCommerce variable products.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Create or update the real catalogue.
 *
 * @return array Import summary.
 */
function vk_seed_real_catalog() {
	$data_file = VK_THEME_DIR . '/inc/data/catalog.json';
	if ( ! class_exists( 'WC_Product_Variable' ) || ! file_exists( $data_file ) ) {
		return array( 'products' => 0, 'variations' => 0 );
	}
	$category_ids = function_exists( 'vk_ensure_catalog_categories' ) ? vk_ensure_catalog_categories() : array();

	$catalog = json_decode( (string) file_get_contents( $data_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $catalog ) ) {
		return array( 'products' => 0, 'variations' => 0 );
	}
	$brand_names = array_values( array_unique( array_column( $catalog, 'brand' ) ) );
	update_option( 'vk_catalog_brand_names', $brand_names, false );

	$attribute_specs = array(
		'pa_flavor'   => array( 'Flavor', true, true ),
		'pa_nicotine' => array( 'Nicotine', false, false ),
		'pa_puffs'    => array( 'Puff count', false, false ),
	);
	foreach ( $attribute_specs as $taxonomy => $spec ) {
		$attribute_name = str_replace( 'pa_', '', $taxonomy );
		if ( function_exists( 'wc_attribute_taxonomy_id_by_name' ) && ! wc_attribute_taxonomy_id_by_name( $attribute_name ) && function_exists( 'wc_create_attribute' ) ) {
			wc_create_attribute(
				array(
					'name'         => $spec[0],
					'slug'         => $attribute_name,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
		}
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy(
				$taxonomy,
				'product',
				array(
					'label'             => $spec[0],
					'public'            => false,
					'hierarchical'      => false,
					'show_ui'           => false,
					'show_in_nav_menus' => false,
					'query_var'         => true,
					'rewrite'           => false,
				)
			);
		}
	}

	$real_ids      = array();
	$variation_count = 0;
	foreach ( $catalog as $index => $item ) {
		$existing = get_page_by_path( $item['slug'], OBJECT, 'product' );
		$product  = $existing ? wc_get_product( $existing->ID ) : new WC_Product_Variable();
		if ( ! $product instanceof WC_Product_Variable ) {
			$product = new WC_Product_Variable( $existing ? $existing->ID : 0 );
		}

		$product->set_name( $item['name'] );
		$product->set_slug( $item['slug'] );
		$product->set_status( $item['status'] );
		$product->set_catalog_visibility( 'visible' );
		$product->set_short_description( $item['description'] );
		$content = str_replace(
			array( '/storage/product/detail/', '/storage/product/' ),
			array( VK_THEME_URI . '/assets/images/catalog/product/detail/', VK_THEME_URI . '/assets/images/catalog/product/' ),
			$item['content']
		);
		$product->set_description( $content );
		$product->set_menu_order( $index );
		$product->set_sku( $item['spu'] );
		$product->set_manage_stock( false );

		$image_id = function_exists( 'vk_seed_attachment' )
			? vk_seed_attachment( preg_replace( '#^assets/#', '', $item['image'] ), $item['name'] )
			: 0;
		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}
		$gallery_ids = array();
		foreach ( $item['gallery'] as $gallery_image ) {
			$gallery_id = function_exists( 'vk_seed_attachment' )
				? vk_seed_attachment( preg_replace( '#^assets/#', '', $gallery_image ), $item['name'] . ' detail' )
				: 0;
			if ( $gallery_id ) {
				$gallery_ids[] = $gallery_id;
			}
		}
		$product->set_gallery_image_ids( array_values( array_unique( $gallery_ids ) ) );

		$flavor_terms = array();
		foreach ( $item['variants'] as $variant ) {
			$slug = sanitize_title( $variant['name'] );
			if ( ! $slug ) {
				$slug = sanitize_title( $item['slug'] . '-' . $variant['sku'] );
			}
			$term = get_term_by( 'slug', $slug, 'pa_flavor' );
			if ( ! $term ) {
				$created = wp_insert_term( $variant['name'], 'pa_flavor', array( 'slug' => $slug ) );
				if ( ! is_wp_error( $created ) ) {
					$term = get_term( $created['term_id'], 'pa_flavor' );
				}
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$flavor_terms[] = $term;
			}
		}
		$nicotine_terms = array();
		foreach ( $item['nicotine'] as $value ) {
			$label = '0' === (string) $value ? '0%' : $value . '%';
			$term  = get_term_by( 'slug', (string) $value, 'pa_nicotine' );
			if ( ! $term ) {
				$created = wp_insert_term( $label, 'pa_nicotine', array( 'slug' => (string) $value ) );
				if ( ! is_wp_error( $created ) ) {
					$term = get_term( $created['term_id'], 'pa_nicotine' );
				}
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$nicotine_terms[] = $term;
			}
		}
		$puff_term = get_term_by( 'slug', $item['puffs'], 'pa_puffs' );
		if ( ! $puff_term ) {
			$puff_labels = array(
				'0-5000'      => '〜5,000回',
				'5001-10000'  => '5,001〜10,000回',
				'10001-20000' => '10,001〜20,000回',
				'20001-'      => '20,001回〜',
			);
			$created = wp_insert_term( $puff_labels[ $item['puffs'] ], 'pa_puffs', array( 'slug' => $item['puffs'] ) );
			if ( ! is_wp_error( $created ) ) {
				$puff_term = get_term( $created['term_id'], 'pa_puffs' );
			}
		}

		$attributes = array();
		$flavor_attribute = new WC_Product_Attribute();
		$flavor_attribute->set_id( wc_attribute_taxonomy_id_by_name( 'flavor' ) );
		$flavor_attribute->set_name( 'pa_flavor' );
		$flavor_attribute->set_options( wp_list_pluck( $flavor_terms, 'term_id' ) );
		$flavor_attribute->set_visible( true );
		$flavor_attribute->set_variation( true );
		$attributes[] = $flavor_attribute;

		foreach ( array( 'pa_nicotine' => $nicotine_terms, 'pa_puffs' => array_filter( array( $puff_term ) ) ) as $taxonomy => $terms ) {
			if ( ! $terms ) {
				continue;
			}
			$attribute = new WC_Product_Attribute();
			$attribute->set_id( wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $taxonomy ) ) );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( wp_list_pluck( $terms, 'term_id' ) );
			$attribute->set_visible( false );
			$attribute->set_variation( false );
			$attributes[] = $attribute;
		}
		$product->set_attributes( $attributes );
		$product_id = $product->save();
		if ( ! $product_id ) {
			continue;
		}

		foreach ( $product->get_children() as $old_variation_id ) {
			wp_delete_post( $old_variation_id, true );
		}

		$assigned_categories = array( 'disposable', 'recommend', 'disposable-vape', 'new-products' );
		$assigned_categories[] = '0-5000' === $item['puffs'] ? 'up-to-3000-puffs' : 'over-5000-puffs';
		if ( preg_match( '/mango|peach|grape|blueberry|watermelon|strawberry|cherry|apple|lemon|fruit|passion|kiwi|berry|raspberry|lime|pineapple|coconut|melon|lychee/i', $item['description'] . ' ' . wp_json_encode( $item['variants'] ) ) ) {
			$assigned_categories[] = 'fruit-flavors';
		}
		if ( preg_match( '/hookah|shisha|e-hose|h2o|hookalit/i', $item['name'] . ' ' . wp_strip_all_tags( $item['description'] ) ) ) {
			$assigned_categories[] = 'hookah';
			$assigned_categories[] = 'recommended-hookah';
		}
		$assigned_categories[] = 0 === $index % 2 ? 'new-devices' : 'weekly-arrivals';
		if ( 0 === $index % 5 ) {
			$assigned_categories[] = 'sale';
			$assigned_categories[] = 'clearance';
		}
		if ( 0 === $index % 6 || false !== stripos( $item['name'], 'MOQ' ) ) {
			$assigned_categories[] = 'wholesale';
			$assigned_categories[] = 'bulk-discount';
		}
		if ( $item['price'] && $item['price'] <= 2500 ) {
			$assigned_categories[] = 'cheap-vapes';
		}
		if ( preg_match( '/Pod|Device|Kit/i', $item['name'] ) ) {
			$assigned_categories[] = 'accessories';
			$assigned_categories[] = 'pods';
			$assigned_categories[] = 'starter-kit';
		}
		$term_ids = array();
		foreach ( array_values( array_unique( $assigned_categories ) ) as $category_slug ) {
			if ( isset( $category_ids[ $category_slug ] ) ) {
				$term_ids[] = (int) $category_ids[ $category_slug ];
			}
		}
		wp_set_object_terms( $product_id, array_values( array_unique( $term_ids ) ), 'product_cat' );

		$brand_term = term_exists( $item['brand'], 'product_brand' );
		if ( ! $brand_term ) {
			$brand_term = wp_insert_term( $item['brand'], 'product_brand', array( 'slug' => sanitize_title( $item['brand'] ) ) );
		}
		if ( ! is_wp_error( $brand_term ) && $brand_term ) {
			$brand_id = is_array( $brand_term ) ? (int) $brand_term['term_id'] : (int) $brand_term;
			wp_set_object_terms( $product_id, array( $brand_id ), 'product_brand' );
		}

		foreach ( $item['variants'] as $variant_index => $variant ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_status( 'publish' );
			$variation->set_sku( $variant['sku'] );
			$variation->set_regular_price( (string) $variant['regular_price'] );
			$variation->set_sale_price( $variant['regular_price'] > $variant['price'] ? (string) $variant['price'] : '' );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( (int) $variant['stock'] );
			$variation->set_stock_status( $variant['stock'] > 0 ? 'instock' : 'outofstock' );
			$variation->set_weight( $variant['weight'] ? (string) $variant['weight'] : '' );
			$variation->set_menu_order( $variant_index );
			$flavor_slug = sanitize_title( $variant['name'] ) ?: sanitize_title( $item['slug'] . '-' . $variant['sku'] );
			$variation->set_attributes( array( 'pa_flavor' => $flavor_slug ) );
			$variation_id = $variation->save();
			if ( $variation_id ) {
				$variation_count++;
				$nicotine = '';
				if ( preg_match( '/(\d+(?:\.\d+)?)\s*%/', $variant['name'], $match ) ) {
					$nicotine = (string) min( 5, max( 0, round( (float) $match[1] ) ) );
				}
				update_post_meta( $variation_id, '_vk_nicotine', $nicotine );
				update_post_meta( $variation_id, '_vk_puffs', $item['puffs'] );
			}
		}
		WC_Product_Variable::sync( $product_id );

		update_post_meta( $product_id, '_vk_brand', $item['brand'] );
		update_post_meta( $product_id, '_vk_spu', $item['spu'] );
		update_post_meta( $product_id, '_vk_puffs', $item['puffs'] );
		update_post_meta( $product_id, '_vk_nicotine', implode( ',', $item['nicotine'] ) );
		update_post_meta( $product_id, '_vk_real_catalog', '1' );
		update_post_meta( $product_id, 'total_sales', (string) ( 40 + $index * 7 ) );
		update_post_meta( $product_id, '_yoast_wpseo_title', $item['name'] . '｜正規品 7s7' );
		update_post_meta( $product_id, '_yoast_wpseo_metadesc', wp_html_excerpt( wp_strip_all_tags( $item['description'] ), 150, '…' ) );
		$real_ids[] = $product_id;
	}

	$all_products = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $all_products as $product_id ) {
		if ( ! in_array( (int) $product_id, $real_ids, true ) ) {
			wp_delete_post( (int) $product_id, true );
		}
	}
	if ( function_exists( 'vk_ensure_catalog_categories' ) ) {
		vk_ensure_catalog_categories();
	}
	if ( function_exists( 'vk_prune_product_brands' ) ) {
		vk_prune_product_brands();
	}

	$permalinks = (array) get_option( 'woocommerce_permalinks', array() );
	$permalinks['product_base'] = 'product';
	update_option( 'woocommerce_permalinks', $permalinks );
	update_option( 'woocommerce_review_rating_verification_required', 'yes' );
	if ( function_exists( 'wc_update_product_lookup_tables' ) ) {
		wc_update_product_lookup_tables();
	}
	delete_transient( 'vk_root_product_slugs' );
	delete_transient( 'vk_product_category_html_rules' );
	flush_rewrite_rules();
	delete_option( 'rewrite_rules' );

	return array(
		'products'   => count( $real_ids ),
		'variations' => $variation_count,
	);
}
