<?php
/**
 * Theme REST endpoints and small commerce integrations.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme endpoints.
 */
function vk_register_rest_routes() {
	register_rest_route(
		'vapekumo/v1',
		'/search-suggest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'vk_rest_search_suggest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'term' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/cart-add',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_cart_add',
			'permission_callback' => function ( WP_REST_Request $request ) {
				return (bool) wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
			},
			'args'                => array(
				'product_id'   => array(
					'type'              => 'integer',
					'required'          => true,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
				'variation_id' => array(
					'type'              => 'integer',
					'default'           => 0,
					'minimum'           => 0,
					'sanitize_callback' => 'absint',
				),
				'quantity'     => array(
					'type'              => 'integer',
					'default'           => 1,
					'minimum'           => 1,
					'maximum'           => 99,
					'sanitize_callback' => 'absint',
				),
				'buy_now'      => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/favorite',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_toggle_favorite',
			'permission_callback' => function () {
				return is_user_logged_in() && current_user_can( 'read' );
			},
			'args'                => array(
				'product_id' => array(
					'type'              => 'integer',
					'required'          => true,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/favorites',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'vk_rest_favorites',
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids' => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/newsletter',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_newsletter',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email' => array(
					'type'              => 'string',
					'format'            => 'email',
					'required'          => true,
					'sanitize_callback' => 'sanitize_email',
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/member-address',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_save_member_address',
			'permission_callback' => function () {
				return is_user_logged_in() && current_user_can( 'read' );
			},
			'args'                => array(
				'type'       => array(
					'type'              => 'string',
					'required'          => true,
					'enum'              => array( 'shipping', 'billing' ),
					'sanitize_callback' => 'sanitize_key',
				),
				'id'         => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				'last_name'  => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'first_name' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'postcode'   => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'state'      => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'city'       => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'address_1'  => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'address_2'  => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				'phone'      => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				'default'    => array( 'type' => 'boolean', 'default' => false ),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/contact',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_contact',
			'permission_callback' => '__return_true',
			'args'                => array(
				'last_name'  => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'first_name' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'email'      => array(
					'type'              => 'string',
					'format'            => 'email',
					'required'          => true,
					'sanitize_callback' => 'sanitize_email',
				),
				'message'    => array(
					'type'              => 'string',
					'required'          => true,
					'minLength'         => 10,
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		)
	);

	register_rest_route(
		'vapekumo/v1',
		'/newsletter-preferences',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'vk_rest_newsletter_preferences',
			'permission_callback' => function () {
				return is_user_logged_in() && current_user_can( 'read' );
			},
			'args'                => array(
				'offers'  => array( 'type' => 'boolean', 'default' => false ),
				'new'     => array( 'type' => 'boolean', 'default' => false ),
				'content' => array( 'type' => 'boolean', 'default' => false ),
			),
		)
	);
}
add_action( 'rest_api_init', 'vk_register_rest_routes' );

/**
 * Save a member address from the checkout modal.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function vk_rest_save_member_address( WP_REST_Request $request ) {
	$type = sanitize_key( $request->get_param( 'type' ) );
	$data = array(
		'id'         => $request->get_param( 'id' ),
		'last_name'  => $request->get_param( 'last_name' ),
		'first_name' => $request->get_param( 'first_name' ),
		'postcode'   => $request->get_param( 'postcode' ),
		'state'      => $request->get_param( 'state' ),
		'city'       => $request->get_param( 'city' ),
		'address_1'  => $request->get_param( 'address_1' ),
		'address_2'  => $request->get_param( 'address_2' ),
		'phone'      => $request->get_param( 'phone' ),
		'default'    => (bool) $request->get_param( 'default' ),
	);
	if ( '' === $data['last_name'] || '' === $data['first_name'] || '' === $data['postcode'] || '' === $data['state'] || '' === $data['city'] || '' === $data['address_1'] ) {
		return new WP_Error( 'vk_address_required', '必須項目を入力してください。', array( 'status' => 422 ) );
	}
	$result = vk_upsert_member_address( get_current_user_id(), $type, $data );
	return rest_ensure_response(
		array(
			'ok'        => true,
			'address'   => $result['address'],
			'listHtml'  => vk_checkout_address_list_html( $result['addresses'], $type ),
			'addresses' => $result['addresses'],
		)
	);
}

/**
 * Add a product or selected variation to the live WooCommerce cart.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function vk_rest_cart_add( WP_REST_Request $request ) {
	if ( ! function_exists( 'WC' ) ) {
		return new WP_Error( 'vk_woocommerce_missing', 'WooCommerceが有効ではありません。', array( 'status' => 500 ) );
	}
	if ( ! WC()->cart ) {
		wc_load_cart();
	}

	$product_id   = absint( $request->get_param( 'product_id' ) );
	$variation_id = absint( $request->get_param( 'variation_id' ) );
	$quantity     = absint( $request->get_param( 'quantity' ) );
	$buy_now      = (bool) $request->get_param( 'buy_now' );
	$product      = wc_get_product( $product_id );
	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return new WP_Error( 'vk_cart_unavailable', 'この商品は現在購入できません。', array( 'status' => 409 ) );
	}

	$attributes = array();
	if ( $product->is_type( 'variable' ) ) {
		$variation = $variation_id ? wc_get_product( $variation_id ) : false;
		if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== $product_id ) {
			return new WP_Error( 'vk_variation_required', '商品の規格を選択してください。', array( 'status' => 422 ) );
		}
		if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
			return new WP_Error( 'vk_variation_out_of_stock', '選択した規格は在庫切れです。', array( 'status' => 409 ) );
		}
		$attributes = $variation->get_variation_attributes();
	}

	$cart_item_key = $buy_now
		? ( vk_prepare_buy_now_cart( $product_id, $variation_id, $quantity, $attributes ) ? true : false )
		: WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
	if ( ! $cart_item_key ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();
		$message = $notices ? wp_strip_all_tags( $notices[0]['notice'] ) : 'カートに追加できませんでした。';
		return new WP_Error( 'vk_cart_add_failed', $message, array( 'status' => 409 ) );
	}

	return rest_ensure_response(
		array(
			'ok'            => true,
			'cart_count'    => (int) WC()->cart->get_cart_contents_count(),
			'cart_subtotal' => wp_strip_all_tags( WC()->cart->get_cart_subtotal() ),
			'mini_cart'     => vk_get_mini_cart_markup(),
			'cart_url'      => wc_get_cart_url(),
			'checkout_url'  => wc_get_checkout_url(),
			'buy_now'       => $buy_now,
		)
	);
}

/**
 * Mini-cart markup shared by catalogue and favorites interactions.
 *
 * @return string
 */
function vk_get_mini_cart_markup() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	ob_start();
	?>
	<ul class="cart-drawer__list">
		<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
			<?php
			$cart_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			if ( ! $cart_product || ! $cart_product->exists() ) {
				continue;
			}
			?>
			<li class="cart-drawer__item" data-cart-key="<?php echo esc_attr( $cart_item_key ); ?>">
				<?php echo $cart_product->get_image( 'woocommerce_thumbnail', array( 'class' => 'cart-drawer__thumb' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<div class="cart-drawer__info">
					<p class="cart-drawer__brand"><?php echo esc_html( vk_product_brand( $cart_product->get_parent_id() ?: $cart_product->get_id() ) ); ?></p>
					<p class="cart-drawer__name"><?php echo esc_html( $cart_product->get_name() ); ?></p>
					<?php $item_data = wc_get_formatted_cart_item_data( $cart_item ); ?>
					<?php if ( $item_data ) : ?><div class="cart-drawer__spec"><?php echo wp_kses_post( $item_data ); ?></div><?php endif; ?>
					<div class="cart-drawer__row">
						<span class="cart-drawer__qty">数量：<?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>
						<span class="cart-drawer__price"><?php echo wp_kses_post( WC()->cart->get_product_subtotal( $cart_product, $cart_item['quantity'] ) ); ?></span>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	return (string) ob_get_clean();
}

/**
 * Product and category search suggestions.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function vk_rest_search_suggest( WP_REST_Request $request ) {
	$term = $request->get_param( 'term' );
	$data = array( 'categories' => array(), 'products' => array() );
	if ( mb_strlen( $term ) < 1 || ! function_exists( 'wc_get_products' ) ) {
		return rest_ensure_response( $data );
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'name__like' => $term,
			'number'     => 4,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $category ) {
			$data['categories'][] = array(
				'label' => $category->name,
				'url'   => get_term_link( $category ),
			);
		}
	}

	$products = wc_get_products(
		array(
			'status' => 'publish',
			'limit'  => 6,
			's'      => $term,
		)
	);
	foreach ( $products as $product ) {
		$data['products'][] = array(
			'id'    => $product->get_id(),
			'label' => $product->get_name(),
			'url'   => get_permalink( $product->get_id() ),
			'price' => wp_strip_all_tags( $product->get_price_html() ),
			'image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
		);
	}
	return rest_ensure_response( $data );
}

/**
 * Toggle a product in the current user's favorites.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function vk_rest_toggle_favorite( WP_REST_Request $request ) {
	$product_id = absint( $request->get_param( 'product_id' ) );
	$product    = wc_get_product( $product_id );
	if ( ! $product ) {
		return new WP_Error( 'vk_invalid_product', '商品が見つかりません。', array( 'status' => 404 ) );
	}

	$user_id = get_current_user_id();
	$favorites = array_values( array_unique( array_map( 'intval', (array) get_user_meta( $user_id, '_vk_favorites', true ) ) ) );
	$key       = array_search( $product_id, $favorites, true );
	if ( false === $key ) {
		$favorites[] = $product_id;
		$active      = true;
	} else {
		unset( $favorites[ $key ] );
		$favorites = array_values( $favorites );
		$active    = false;
	}
	update_user_meta( $user_id, '_vk_favorites', $favorites );
	$favorites = vk_get_user_favorite_ids( $user_id );
	return rest_ensure_response(
		array(
			'active'    => $active,
			'favorites' => $favorites,
		)
	);
}

/**
 * Get products from supplied favorites IDs.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function vk_rest_favorites( WP_REST_Request $request ) {
	$ids = $request->get_param( 'ids' );
	if ( is_string( $ids ) ) {
		$ids = explode( ',', $ids );
	}
	$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
	if ( is_user_logged_in() ) {
		$ids = vk_get_user_favorite_ids();
	}
	$products = array();
	$valid_ids = array();
	foreach ( array_slice( $ids, 0, 30 ) as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			continue;
		}
		$valid_ids[] = $id;
		$products[] = array(
			'id'        => $id,
			'name'      => $product->get_name(),
			'url'       => get_permalink( $id ),
			'image'     => wp_get_attachment_image( $product->get_image_id(), 'vk-product-card', false, array( 'loading' => 'lazy' ) ),
			'miniImage' => $product->get_image( 'thumbnail', array( 'class' => 'fav-menu__thumb', 'loading' => 'lazy' ), false ),
			'priceHtml' => $product->get_price_html(),
			'brand'     => vk_product_brand( $product ),
		);
	}
	return rest_ensure_response(
		array(
			'products'  => $products,
			'favorites' => $valid_ids,
		)
	);
}

/**
 * Store newsletter emails as private subscribers.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function vk_rest_newsletter( WP_REST_Request $request ) {
	$email = sanitize_email( $request->get_param( 'email' ) );
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'vk_invalid_email', 'メールアドレスをご確認ください。', array( 'status' => 422 ) );
	}
	$existing = get_page_by_title( $email, OBJECT, 'vk_subscriber' );
	if ( ! $existing ) {
		wp_insert_post(
			array(
				'post_type'   => 'vk_subscriber',
				'post_status' => 'publish',
				'post_title'  => $email,
				'meta_input'  => array( '_vk_subscribed_at' => current_time( 'mysql' ) ),
			)
		);
	}
	return rest_ensure_response( array( 'message' => 'ご登録ありがとうございます。' ) );
}

/**
 * Register the private subscriber record type.
 */
function vk_register_subscriber_type() {
	register_post_type(
		'vk_subscriber',
		array(
			'labels'              => array( 'name' => 'メール登録者', 'singular_name' => 'メール登録者' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
		)
	);
}
add_action( 'init', 'vk_register_subscriber_type' );

/**
 * Store contact inquiries privately for follow-up.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function vk_rest_contact( WP_REST_Request $request ) {
	$last_name  = sanitize_text_field( (string) $request->get_param( 'last_name' ) );
	$first_name = sanitize_text_field( (string) $request->get_param( 'first_name' ) );
	$email      = sanitize_email( (string) $request->get_param( 'email' ) );
	$message    = sanitize_textarea_field( (string) $request->get_param( 'message' ) );
	if ( ! $last_name || ! $first_name || ! is_email( $email ) || mb_strlen( $message ) < 10 ) {
		return new WP_Error( 'vk_invalid_contact', '必須項目をご確認ください。', array( 'status' => 422 ) );
	}
	$inquiry_id = wp_insert_post(
		array(
			'post_type'   => 'vk_inquiry',
			'post_status' => 'private',
			'post_title'  => $last_name . ' ' . $first_name . ' 様',
			'post_content'=> $message,
			'meta_input'  => array(
				'_vk_email'      => $email,
				'_vk_received_at'=> current_time( 'mysql' ),
			),
		)
	);
	return rest_ensure_response(
		array(
			'ok'      => (bool) $inquiry_id,
			'message' => 'お問い合わせを受け付けました。担当者より順次ご連絡いたします。',
		)
	);
}

/**
 * Private inquiry records.
 */
function vk_register_inquiry_type() {
	register_post_type(
		'vk_inquiry',
		array(
			'labels'              => array( 'name' => 'お問い合わせ', 'singular_name' => 'お問い合わせ' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-format-chat',
			'exclude_from_search' => true,
			'supports'            => array( 'title', 'editor' ),
		)
	);
}
add_action( 'init', 'vk_register_inquiry_type' );

/**
 * Save newsletter interests for the current account.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function vk_rest_newsletter_preferences( WP_REST_Request $request ) {
	$preferences = array(
		'offers'  => (bool) $request->get_param( 'offers' ),
		'new'     => (bool) $request->get_param( 'new' ),
		'content' => (bool) $request->get_param( 'content' ),
	);
	update_user_meta( get_current_user_id(), '_vk_newsletter_preferences', $preferences );
	update_user_meta( get_current_user_id(), '_vk_newsletter_preferences_updated', current_time( 'mysql' ) );
	return rest_ensure_response( array( 'ok' => true, 'message' => 'メール配信設定を保存しました。' ) );
}
