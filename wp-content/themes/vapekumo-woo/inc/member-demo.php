<?php
/**
 * Demo orders and purchased-product reviews.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seed demonstration orders and purchased reviews for the test member.
 *
 * @return array
 */
function vk_seed_member_demo_data() {
	$user = get_user_by( 'login', 'wpshop_admin' );
	if ( ! $user || ! function_exists( 'wc_create_order' ) ) {
		return array( 'orders' => 0, 'reviews' => 0 );
	}
	if ( ! get_user_meta( $user->ID, '_vk_newsletter_preferences_updated', true ) ) {
		update_user_meta(
			$user->ID,
			'_vk_newsletter_preferences',
			array(
				'offers'  => true,
				'new'     => true,
				'content' => true,
			)
		);
	}

	$orders = wc_get_orders(
		array(
			'customer_id' => $user->ID,
			'limit'       => -1,
			'meta_key'    => '_vk_demo_order',
			'meta_value'  => '1',
		)
	);
	$statuses = array( 'pending', 'on-hold', 'processing', 'completed', 'cancelled', 'refunded', 'failed' );
	$products = wc_get_products( array( 'status' => 'publish', 'limit' => 7, 'orderby' => 'date', 'order' => 'DESC' ) );
	$existing_statuses = array_map(
		static function ( $order ) {
			return $order->get_status();
		},
		$orders
	);
	$created_orders    = 0;

	foreach ( $statuses as $index => $status ) {
		if ( in_array( $status, $existing_statuses, true ) || empty( $products[ $index ] ) ) {
			continue;
		}
		$product = $products[ $index ];
		$order   = wc_create_order(
			array(
				'customer_id' => $user->ID,
				'status'      => 'pending',
				'created_via' => 'checkout',
			)
		);
		if ( is_wp_error( $order ) ) {
			continue;
		}

		$purchase_product = $product;
		if ( $product->is_type( 'variable' ) ) {
			$children = $product->get_children();
			if ( $children ) {
				$purchase_product = wc_get_product( $children[0] );
			}
		}
		$order->add_product( $purchase_product, 1 + ( $index % 2 ) );
		$billing = array(
			'first_name' => $user->first_name ?: '太郎',
			'last_name'  => $user->last_name ?: '山田',
			'company'    => '',
			'address_1'  => get_user_meta( $user->ID, 'shipping_address_1', true ) ?: '神宮前1-2-3',
			'address_2'  => get_user_meta( $user->ID, 'shipping_address_2', true ) ?: 'サンプルマンション101',
			'city'       => get_user_meta( $user->ID, 'shipping_city', true ) ?: '渋谷区',
			'state'      => get_user_meta( $user->ID, 'shipping_state', true ) ?: '東京都',
			'postcode'   => get_user_meta( $user->ID, 'shipping_postcode', true ) ?: '150-0001',
			'country'    => 'JP',
			'email'      => $user->user_email,
			'phone'      => get_user_meta( $user->ID, 'billing_phone', true ) ?: '09012345678',
		);
		$order->set_address( $billing, 'billing' );
		$order->set_address( $billing, 'shipping' );
		$order->set_payment_method( 'cooder' );
		$order->set_payment_method_title( 'クレジットカード（Cooder）' );
		$order->set_date_created( new WC_DateTime( gmdate( 'Y-m-d H:i:s', time() - ( ( $index + 1 ) * 4 * DAY_IN_SECONDS ) ) ) );
		$order->calculate_totals();
		$order->set_status( $status );
		$order->update_meta_data( '_vk_demo_order', '1' );
		$order->add_order_note( 'テスト用の' . wc_get_order_status_name( $status ) . '注文です。' );
		$order->save();
		$created_orders++;
	}

	$review_products = array_slice( $products, 0, 3 );
	$review_texts    = array(
		'吸い心地が安定していて、フレーバーも最後までしっかり続きました。',
		'思ったより軽くて持ち歩きやすいです。再購入したい商品でした。',
		'味のバランスがよく、日常使いにちょうど良い製品です。',
	);
	$created_reviews = 0;
	foreach ( $review_products as $index => $product ) {
		$existing = get_comments(
			array(
				'post_id'    => $product->get_id(),
				'user_id'    => $user->ID,
				'status'     => 'approve',
				'type'       => 'review',
				'meta_key'   => '_vk_demo_review',
				'meta_value' => '1',
			)
		);
		if ( $existing ) {
			continue;
		}
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $product->get_id(),
				'comment_author'       => vk_member_display_name( $user->ID ),
				'comment_author_email' => $user->user_email,
				'comment_author_url'   => '',
				'comment_content'      => $review_texts[ $index ],
				'comment_type'         => 'review',
				'comment_parent'       => 0,
				'user_id'              => $user->ID,
				'comment_approved'     => 1,
				'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - ( ( $index + 1 ) * 7 * DAY_IN_SECONDS ) ),
			)
		);
		if ( $comment_id ) {
			add_comment_meta( $comment_id, 'rating', 5 - $index );
			add_comment_meta( $comment_id, 'verified', 1 );
			add_comment_meta( $comment_id, '_vk_demo_review', 1 );
			$created_reviews++;
		}
	}
	return array( 'orders' => $created_orders, 'reviews' => $created_reviews );
}
