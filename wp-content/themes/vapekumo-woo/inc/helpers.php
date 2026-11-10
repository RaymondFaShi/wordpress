<?php
/**
 * Template helpers.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme asset URL.
 *
 * @param string $path Relative path.
 * @return string
 */
function vk_asset( $path ) {
	return esc_url( VK_THEME_URI . '/assets/' . ltrim( $path, '/' ) );
}

/**
 * Logo mark and wordmark.
 *
 * @param bool $footer Footer variant.
 */
function vk_logo( $footer = false ) {
	$class = $footer ? 'site-logo site-logo--footer' : 'site-logo';
	?>
	<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="7s7 トップページへ">
		<!-- <span class="site-logo__mark" aria-hidden="true">
			<svg viewBox="0 0 32 32">
				<defs>
					<linearGradient id="vkGrad<?php echo $footer ? 'Footer' : ''; ?>" x1="0" y1="0" x2="1" y2="1">
						<stop offset="0" stop-color="#2e86e8"></stop>
						<stop offset="1" stop-color="#1e7bef"></stop>
					</linearGradient>
				</defs>
				<circle cx="16" cy="16" r="16" fill="url(#vkGrad<?php echo $footer ? 'Footer' : ''; ?>)"></circle>
				<path d="M8.4 10.2 15 22.2" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round"></path>
				<path d="M23.6 10.2 17 22.2" fill="none" stroke="#cbe4ff" stroke-width="3" stroke-linecap="round"></path>
				<path d="M12.4 8.6c2.6-2.6 5.6-1.4 7.2 0" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" opacity=".55"></path>
			</svg>
		</span>
		<span class="site-logo__word">7s7</span> -->
        <!-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 248 112" width="248" height="112" role="img" aria-label="7s7.cc">
            <g transform="translate(34,43) scale(0.78)">
                <path d="M 22 -22 C 22 -13 34 -13 34 -4" fill="none" stroke="#3A3D42" stroke-opacity="0.55" stroke-width="2" stroke-linecap="round"/>
                <rect x="17" y="0" width="10" height="9" rx="3" fill="#3A3D42" fill-opacity="0.9"/>
                <rect x="0" y="8" width="44" height="44" rx="13" fill="#3A3D42" fill-opacity="0.08" stroke="#3A3D42" stroke-opacity="0.6" stroke-width="2"/>
                <path d="M 11 40 L 11 21" fill="none" stroke="#3A3D42" stroke-opacity="0.4" stroke-width="2" stroke-linecap="round"/>
            </g>
            <text x="86" y="81" text-anchor="start" font-family="'Avenir Next','Nunito','Helvetica Neue',Helvetica,Arial,sans-serif" font-size="42" font-weight="500" letter-spacing="1.5">
                <tspan fill="#2C2F33">7s7</tspan><tspan fill="#6E8299">.cc</tspan>
            </text>
        </svg> -->
        <img src="/wp-content/themes/vapekumo-woo/assets/images/logo.png" width="248" height="112" />
	</a>
	<?php
}

/**
 * Cart item count.
 *
 * @return int
 */
function vk_get_cart_count() {
	return function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Valid published product IDs in a member's favorites.
 *
 * Stale IDs can remain after catalogue imports, so the header must count only
 * products that still exist and are publicly visible.
 *
 * @param int $user_id User ID. Defaults to the current user.
 * @return array
 */
function vk_get_user_favorite_ids( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$stored = array_values( array_unique( array_filter( array_map( 'intval', (array) get_user_meta( $user_id, '_vk_favorites', true ) ) ) ) );
	$valid  = array();
	foreach ( $stored as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && 'publish' === $product->get_status() ) {
			$valid[] = $product_id;
		}
	}
	if ( $valid !== $stored ) {
		update_user_meta( $user_id, '_vk_favorites', $valid );
	}
	return $valid;
}

/**
 * Favorites count for the current user.
 *
 * @return int
 */
function vk_get_user_favorite_count() {
	if ( ! is_user_logged_in() ) {
		return 0;
	}
	return count( vk_get_user_favorite_ids( get_current_user_id() ) );
}

/**
 * Products for the header favorites dropdown.
 *
 * @param int $limit Maximum number of products.
 * @return array
 */
function vk_get_user_favorite_products( $limit = 3 ) {
	$products = array();
	foreach ( array_slice( vk_get_user_favorite_ids(), 0, max( 1, (int) $limit ) ) as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			$products[] = $product;
		}
	}
	return $products;
}

/**
 * Member-facing display name: full name, display name, then email.
 *
 * @param int $user_id User ID.
 * @return string
 */
function vk_member_display_name( $user_id = 0 ) {
	$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
	if ( ! $user instanceof WP_User ) {
		return '';
	}
	$full_name = trim( $user->last_name . ' ' . $user->first_name );
	if ( '' !== $full_name ) {
		return $full_name;
	}
	if ( $user->display_name && $user->display_name !== $user->user_login ) {
		return $user->display_name;
	}
	return $user->user_email ?: $user->user_login;
}

/**
 * Display name for the current member.
 *
 * @return string
 */
function vk_current_member_display_name() {
	return vk_member_display_name( get_current_user_id() );
}

/**
 * Shop URL.
 *
 * @return string
 */
function vk_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
}

/**
 * Product category URL from a slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function vk_product_category_url( $slug ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term && ! is_wp_error( $term ) ) {
		return get_term_link( $term );
	}
	return home_url( '/' . $slug . '.html' );
}

/**
 * Most frequently searched keywords, backed by real frontend searches.
 *
 * @param int $limit Number of keywords.
 * @return array
 */
function vk_popular_search_keywords( $limit = 6 ) {
	$search_counts = (array) get_option( 'vk_search_keyword_counts', array() );
	arsort( $search_counts );
	$keywords = array();
	foreach ( array_slice( $search_counts, 0, $limit, true ) as $keyword => $count ) {
		$term = get_term_by( 'name', $keyword, 'product_cat' );
		if ( ! $term ) {
			$term = get_term_by( 'slug', sanitize_title( $keyword ), 'product_cat' );
		}
		$keywords[] = array(
			'label' => $keyword,
			'url'   => $term ? get_term_link( $term ) : add_query_arg( array( 's' => $keyword, 'post_type' => 'product' ), home_url( '/' ) ),
		);
	}
	if ( count( $keywords ) < $limit ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => $limit,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		foreach ( $terms as $term ) {
			if ( wp_list_pluck( $keywords, 'label' ) && in_array( $term->name, wp_list_pluck( $keywords, 'label' ), true ) ) {
				continue;
			}
			$keywords[] = array( 'label' => $term->name, 'url' => get_term_link( $term ) );
			if ( count( $keywords ) >= $limit ) {
				break;
			}
		}
	}
	return array_slice( $keywords, 0, $limit );
}

/**
 * My account endpoint.
 *
 * @param string $endpoint Endpoint slug.
 * @return string
 */
function vk_account_url( $endpoint = '' ) {
	$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/member/' );
	return $endpoint ? trailingslashit( $url ) . $endpoint . '/' : $url;
}

/**
 * Render a 5-star score as accessible text plus CSS bar.
 *
 * @param float $score Score out of 5.
 * @param int   $count Review count.
 */
function vk_rating_html( $score = 0, $count = 0 ) {
	$score = min( 5, max( 0, (float) $score ) );
	$width = (int) round( $score * 20 );
	$label = $count ? sprintf( '%s（%d件）', number_format_i18n( $score, 1 ), $count ) : number_format_i18n( $score, 1 );
	?>
	<span class="pdp-rating" aria-label="評価 <?php echo esc_attr( $label ); ?>">
		<span class="rating rating--<?php echo esc_attr( (string) $width ); ?>" aria-hidden="true"></span>
		<b><?php echo esc_html( number_format_i18n( $score, 1 ) ); ?></b>
		<?php if ( $count ) : ?>
			<span class="pdp-rating__link">（<?php echo esc_html( number_format_i18n( $count ) ); ?>件）</span>
		<?php endif; ?>
	</span>
	<?php
}

/**
 * Get a product's display brand.
 *
 * @param WC_Product|int $product Product.
 * @return string
 */
function vk_product_brand( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return '';
	}
	$brand = get_post_meta( $product->get_id(), '_vk_brand', true );
	if ( $brand ) {
		return $brand;
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	return $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '7s7';
}

/**
 * Variation data used by the catalogue selection modal.
 *
 * @param WC_Product $product Product.
 * @return array
 */
function vk_product_variation_payload( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$variation_data = array();
	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}
		$attribute_labels = array();
		foreach ( $variation->get_attributes() as $attribute_key => $attribute_value ) {
			$taxonomy = str_replace( 'attribute_', '', $attribute_key );
			$term     = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $attribute_value, $taxonomy ) : false;
			if ( $term && ! is_wp_error( $term ) ) {
				$attribute_labels[] = $term->name;
			} elseif ( $attribute_value ) {
				$attribute_labels[] = $attribute_value;
			}
		}
		$variation_data[] = array(
			'id'        => $variation->get_id(),
			'label'     => implode( ' / ', $attribute_labels ) ?: $variation->get_name(),
			'sku'       => $variation->get_sku(),
			'price'     => wp_strip_all_tags( wc_price( (float) $variation->get_price() ) ),
			'image'     => wp_get_attachment_image_url( $variation->get_image_id() ?: $product->get_image_id(), 'vk-product-card' ),
			'in_stock'  => $variation->is_in_stock() && $variation->is_purchasable(),
			'stock_qty' => null === $variation->get_stock_quantity() ? null : (int) $variation->get_stock_quantity(),
		);
	}
	return $variation_data;
}

/**
 * Resolve a catalogue filter to its registered WooCommerce attribute taxonomy.
 *
 * @param string $filter nicotine|puffs.
 * @return string
 */
function vk_catalog_filter_taxonomy( $filter ) {
	$candidates = array(
		'nicotine' => array( 'pa_nicotine' ),
		'puffs'    => array( 'pa_puff_range', 'pa_puffs' ),
	);
	foreach ( $candidates[ $filter ] ?? array() as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return $taxonomy;
		}
	}
	return '';
}

/**
 * Read the configured catalogue filter terms from WooCommerce.
 *
 * @param string $filter nicotine|puffs.
 * @return WP_Term[]
 */
function vk_catalog_filter_terms( $filter ) {
	$taxonomy = vk_catalog_filter_taxonomy( $filter );
	if ( '' === $taxonomy ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Human-readable variation attributes for cart and checkout summaries.
 *
 * @param WC_Product $product Product object.
 * @return array
 */
function vk_get_variation_attribute_lines( $product ) {
	if ( ! $product instanceof WC_Product_Variation ) {
		return array();
	}
	$labels = array(
		'pa_flavor'     => 'フレーバー',
		'pa_nicotine'   => 'ニコチン',
		'pa_puff_range' => '吸引回数',
		'pa_puffs'      => '吸引回数',
	);
	$lines = array();
	foreach ( $product->get_variation_attributes() as $attribute => $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			continue;
		}
		$taxonomy = str_replace( 'attribute_', '', $attribute );
		$term     = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $value, $taxonomy ) : false;
		$lines[]  = array(
			'label' => $labels[ $taxonomy ] ?? wc_attribute_label( $taxonomy, $product ),
			'value' => $term && ! is_wp_error( $term ) ? $term->name : wc_clean( $value ),
		);
	}
	return $lines;
}

/**
 * Total stock across a simple product or its variants.
 *
 * @param WC_Product $product Product.
 * @return int|null
 */
function vk_product_stock_total( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return null;
	}
	if ( ! $product->is_type( 'variable' ) ) {
		return null === $product->get_stock_quantity() ? null : (int) $product->get_stock_quantity();
	}
	$total = 0;
	$known = false;
	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( $variation && null !== $variation->get_stock_quantity() ) {
			$known = true;
			$total += max( 0, (int) $variation->get_stock_quantity() );
		}
	}
	return $known ? $total : null;
}

/**
 * Reusable product card.
 *
 * @param WC_Product|int $product Product.
 * @param int            $rank Ranking number.
 * @param string         $badge Optional badge text.
 * @param bool           $show_button Whether to show the add-to-cart button.
 * @param string         $context     Optional render context.
 */
function vk_product_card( $product, $rank = 0, $badge = '', $show_button = false, $context = '' ) {
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$id         = $product->get_id();
	$link       = get_permalink( $id );
	$is_sale    = $product->is_on_sale();
	$rating     = (float) $product->get_average_rating();
	$rating_cnt = (int) $product->get_review_count();
	$is_new     = strtotime( $product->get_date_created() ? $product->get_date_created()->date( 'c' ) : '') > strtotime( '-21 days' );
	$user_favs  = is_user_logged_in() ? (array) get_user_meta( get_current_user_id(), '_vk_favorites', true ) : array();
	$is_fav     = in_array( $id, array_map( 'intval', $user_favs ), true );
	$flavors    = wc_get_product_terms( $id, 'pa_flavor', array( 'fields' => 'names' ) );
	$nicotine   = get_post_meta( $id, '_vk_nicotine', true );
	if ( false !== strpos( (string) $nicotine, ',' ) ) {
		$nicotine = strtok( (string) $nicotine, ',' );
	}
	$card_meta  = $flavors && ! is_wp_error( $flavors ) ? 'フレーバー：' . $flavors[0] : wp_html_excerpt( wp_strip_all_tags( $product->get_short_description() ), 36, '…' );
	if ( '' !== $nicotine ) {
		$card_meta .= '／ニコチン' . ( '0' === (string) $nicotine ? '0mg（ノンニコチン）' : wc_format_localized_decimal( (float) $nicotine ) . '%' );
	}
	$card_name  = $product->get_name();
	$brand_name = vk_product_brand( $product );
	if ( $brand_name && 0 === stripos( $card_name, $brand_name . ' ' ) ) {
		$card_name = substr( $card_name, strlen( $brand_name ) + 1 );
	}
	$stock_quantity = vk_product_stock_total( $product );
	$is_low_stock   = $product->is_in_stock() && null !== $stock_quantity && $stock_quantity > 0 && $stock_quantity < 10;
	$stock_text     = $product->is_in_stock()
		? ( $is_low_stock ? '在庫僅少・お早めに' : '在庫あり' )
		: '在庫切れ・入荷待ち';
	$product_type   = $product->get_type();
	$variation_data = vk_product_variation_payload( $product );
	$badge_items = array();
	if ( 'rank' === $context ) {
		$badge_items = array();
	} elseif ( ! $product->is_in_stock() ) {
		$badge_items[] = array( '在庫切れ', '' );
	} elseif ( 'new' === $context ) {
		$badge_items[] = array( 'NEW', 'product-card__badge--new' );
	} elseif ( 'sale' === $context ) {
		$badge_items[] = array( 'SALE', '' );
	} elseif ( 'catalog' === $context ) {
		if ( $is_new ) {
			$badge_items[] = array( 'NEW', 'product-card__badge--new' );
		} elseif ( $is_low_stock ) {
			$badge_items[] = array( '残り' . $stock_quantity . '点', 'product-card__badge--low' );
		}
	} elseif ( $is_sale ) {
		$badge_items[] = array( 'SALE', '' );
	} elseif ( $badge ) {
		$badge_items[] = array( $badge, '' );
	} elseif ( $is_new ) {
		$badge_items[] = array( 'NEW', 'product-card__badge--new' );
	}
	if ( 'sale' === $context && $is_low_stock ) {
		$badge_items[] = array( '残り' . $stock_quantity . '点', 'product-card__badge--low' );
	}
	?>
	<article class="product-card" data-product-id="<?php echo esc_attr( (string) $id ); ?>" data-product-type="<?php echo esc_attr( $product_type ); ?>" data-product-name="<?php echo esc_attr( $product->get_name() ); ?>" data-product-image="<?php echo esc_url( wp_get_attachment_image_url( $product->get_image_id(), 'vk-product-card' ) ); ?>" data-variations="<?php echo esc_attr( wp_json_encode( $variation_data ) ); ?>">
		<a class="product-card__media" href="<?php echo esc_url( $link ); ?>">
			<?php if ( $rank ) : ?>
				<span class="product-card__rank<?php echo $rank <= 3 ? ' product-card__rank--' . esc_attr( (string) $rank ) : ''; ?>"><?php echo esc_html( (string) $rank ); ?></span>
			<?php endif; ?>
			<?php if ( $badge_items ) : ?>
				<span class="product-card__badges">
					<?php foreach ( $badge_items as $badge_item ) : ?>
						<span class="product-card__badge <?php echo esc_attr( $badge_item[1] ); ?>"><?php echo esc_html( $badge_item[0] ); ?></span>
					<?php endforeach; ?>
				</span>
			<?php endif; ?>
			<button class="product-card__fav js-fav<?php echo $is_fav ? ' is-on' : ''; ?>" type="button"
				data-product-id="<?php echo esc_attr( (string) $id ); ?>"
				aria-label="<?php echo esc_attr( $is_fav ? 'お気に入りから削除' : 'お気に入りに追加' ); ?>"
				aria-pressed="<?php echo esc_attr( $is_fav ? 'true' : 'false' ); ?>">
				<i class="bi <?php echo esc_attr( $is_fav ? 'bi-heart-fill' : 'bi-heart' ); ?>" aria-hidden="true"></i>
			</button>
			<?php echo $product->get_image( 'vk-product-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<div class="product-card__body">
			<p class="product-card__brand"><?php echo esc_html( vk_product_brand( $product ) ); ?></p>
			<h3 class="product-card__name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $card_name ); ?></a></h3>
			<p class="product-card__meta"><?php echo esc_html( $card_meta ); ?></p>
			<p class="product-card__rating">
				<?php vk_rating_html( $rating, $rating_cnt ); ?>
			</p>
			<p class="product-card__stock<?php echo $product->is_in_stock() ? '' : ' product-card__stock--out'; ?>"><?php echo esc_html( $stock_text ); ?></p>
			<div class="product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<?php if ( $show_button ) : ?>
				<?php if ( 'catalog' === $context ) : ?>
					<button class="btn btn-cta product-card__btn js-add-cart" type="button" data-product-id="<?php echo esc_attr( (string) $id ); ?>"><?php echo $product->is_in_stock() ? 'カートに入れる' : '在庫切れ'; ?></button>
				<?php else : ?>
					<?php woocommerce_template_loop_add_to_cart( array( 'class' => 'btn btn-cta product-card__btn' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Breadcrumb output.
 *
 * @param array $items Additional final items as label/url pairs.
 */
function vk_breadcrumbs( $items = array() ) {
	$crumbs = array( array( 'label' => 'ホーム', 'url' => home_url( '/' ) ) );

	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		if ( is_product_taxonomy() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$crumbs[] = array( 'label' => $term->name, 'url' => get_term_link( $term ) );
			}
		} else {
			$crumbs[] = array( 'label' => 'オンラインストア', 'url' => vk_shop_url() );
		}
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		$crumbs[] = array( 'label' => 'オンラインストア', 'url' => vk_shop_url() );
		$terms    = get_the_terms( get_queried_object_id(), 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$crumbs[] = array( 'label' => $terms[0]->name, 'url' => get_term_link( $terms[0] ) );
		}
		$crumbs[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_singular() ) {
		$crumbs[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_home() ) {
		$crumbs[] = array( 'label' => 'ブログ', 'url' => '' );
	} elseif ( is_archive() ) {
		$crumbs[] = array( 'label' => get_the_archive_title(), 'url' => '' );
	} elseif ( is_search() ) {
		$crumbs[] = array( 'label' => '検索結果', 'url' => '' );
	} elseif ( is_404() ) {
		$crumbs[] = array( 'label' => 'ページが見つかりません', 'url' => '' );
	} else {
		$crumbs[] = array( 'label' => get_the_title(), 'url' => get_permalink() );
	}

	foreach ( $items as $item ) {
		$crumbs[] = $item;
	}
	?>
	<nav class="crumbs" aria-label="パンくずリスト">
		<div class="container">
			<ol class="crumbs__list">
				<?php foreach ( $crumbs as $index => $crumb ) : ?>
					<li class="crumbs__item">
						<?php if ( ! empty( $crumb['url'] ) && $index < count( $crumbs ) - 1 ) : ?>
							<a class="crumbs__link" href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['label'] ); ?></a>
						<?php else : ?>
							<span class="crumbs__current"<?php echo $index === count( $crumbs ) - 1 ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $crumb['label'] ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</nav>
	<?php
}

/**
 * Render the mini cart used by the header.
 */
function vk_mini_cart() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$items = WC()->cart->get_cart();
	?>
	<div class="cart-menu__inner">
		<?php if ( ! $items ) : ?>
			<p class="cart-menu__empty">カートに商品がありません。</p>
		<?php else : ?>
			<?php foreach ( array_slice( $items, 0, 3 ) as $item ) : ?>
				<?php $product = $item['data']; ?>
				<div class="cart-menu__row">
					<?php echo $product->get_image( 'thumbnail', array( 'class' => 'cart-menu__thumb', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div>
						<p class="cart-menu__name"><?php echo esc_html( $product->get_name() ); ?></p>
						<p class="cart-menu__qty">数量：<?php echo esc_html( (string) $item['quantity'] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
			<p class="cart-menu__total"><span>小計</span><b><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></b></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Output the shared card search form.
 */
function vk_search_form() {
	?>
	<div class="site-search" id="siteSearchArea">
		<form class="site-search__form" role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" autocomplete="off">
			<i class="bi bi-search site-search__icon" aria-hidden="true"></i>
			<label class="visually-hidden" for="searchInput">商品を検索</label>
			<input class="site-search__input" id="searchInput" name="s" type="search" value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="商品名・ブランド名で検索" role="combobox" aria-expanded="false" aria-controls="searchSuggest" aria-autocomplete="list">
			<input type="hidden" name="post_type" value="product">
			<button class="btn btn-cta site-search__submit" type="submit">検索</button>
		</form>
		<div class="site-search__suggest" id="searchSuggest" role="listbox" aria-label="検索候補"></div>
	</div>
	<?php
}

/**
 * Shortcode: local favorites list.
 *
 * @return string
 */
function vk_favorites_shortcode() {
	if ( is_user_logged_in() ) {
		$favorite_ids = vk_get_user_favorite_ids();
		ob_start();
		?>
		<div class="u-card">
			<div class="u-card__body">
				<div class="u-fav-list" id="vkFavorites">
					<?php if ( $favorite_ids ) : ?>
						<?php foreach ( $favorite_ids as $favorite_id ) : ?>
							<?php
							$favorite_product = wc_get_product( $favorite_id );
							if ( ! $favorite_product || 'publish' !== $favorite_product->get_status() ) {
								continue;
							}
							$favorite_brand = vk_product_brand( $favorite_product );
							$favorite_name  = $favorite_product->get_name();
							if ( $favorite_brand && 0 === stripos( $favorite_name, $favorite_brand . ' ' ) ) {
								$favorite_name = substr( $favorite_name, strlen( $favorite_brand ) + 1 );
							}
							$favorite_link = get_permalink( $favorite_product->get_id() );
							?>
							<article class="u-fav" data-product-id="<?php echo esc_attr( (string) $favorite_product->get_id() ); ?>" data-product-type="<?php echo esc_attr( $favorite_product->get_type() ); ?>" data-product-name="<?php echo esc_attr( $favorite_product->get_name() ); ?>" data-product-image="<?php echo esc_url( wp_get_attachment_image_url( $favorite_product->get_image_id(), 'vk-product-card' ) ); ?>" data-variations="<?php echo esc_attr( wp_json_encode( vk_product_variation_payload( $favorite_product ) ) ); ?>">
								<button class="u-fav__remove js-fav is-on" type="button" data-product-id="<?php echo esc_attr( (string) $favorite_product->get_id() ); ?>" aria-label="お気に入りから削除"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
								<a class="u-fav__media" href="<?php echo esc_url( $favorite_link ); ?>"><?php echo $favorite_product->get_image( 'vk-product-card', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
								<div class="u-fav__body">
									<p class="u-fav__brand"><?php echo esc_html( $favorite_brand ); ?></p>
									<p class="u-fav__name"><a href="<?php echo esc_url( $favorite_link ); ?>"><?php echo esc_html( $favorite_name ); ?></a></p>
									<p class="u-fav__stock<?php echo $favorite_product->is_in_stock() ? '' : ' u-fav__stock--out'; ?>"><?php echo esc_html( $favorite_product->is_in_stock() ? '在庫あり' : '在庫切れ・入荷待ち' ); ?></p>
									<p class="u-fav__price"><?php echo wp_kses_post( $favorite_product->get_price_html() ); ?><span class="product-card__tax">（税込）</span></p>
									<div class="u-fav__actions">
										<button class="btn btn-cta js-add-cart" type="button" data-product-id="<?php echo esc_attr( (string) $favorite_product->get_id() ); ?>"<?php disabled( ! $favorite_product->is_in_stock() ); ?>><?php echo $favorite_product->is_in_stock() ? 'カートに入れる' : '在庫切れ'; ?></button>
										<a class="btn btn-line" href="<?php echo esc_url( $favorite_link ); ?>">詳細</a>
									</div>
								</div>
							</article>
						<?php endforeach; ?>
					<?php endif; ?>
					<div class="u-empty" id="favEmpty"<?php echo $favorite_ids ? ' hidden' : ''; ?>>
						<span class="u-empty__icon"><i class="bi bi-heart" aria-hidden="true"></i></span>
						<p class="u-empty__title">お気に入りはまだありません</p>
						<p class="u-empty__text">気になる商品のハートをタップすると、ここに保存されます。</p>
						<a class="btn btn-cta" href="<?php echo esc_url( vk_shop_url() ); ?>">今すぐ購入する</a>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
	<div class="vk-favorites" id="vkFavorites">
		<div class="vk-favorites__loading">お気に入りを読み込んでいます…</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'vapekumo_favorites', 'vk_favorites_shortcode' );
