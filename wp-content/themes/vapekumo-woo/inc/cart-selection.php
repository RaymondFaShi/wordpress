<?php
/**
 * Partial checkout selection for the cart.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Snapshot cart contents so they can be restored after an exclusive checkout.
 *
 * @return array
 */
function vk_snapshot_current_cart() {
	$items = array();
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $items;
	}
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$items[] = array(
			'product_id'     => (int) $cart_item['product_id'],
			'variation_id'   => (int) $cart_item['variation_id'],
			'quantity'       => (int) $cart_item['quantity'],
			'variation'      => (array) ( $cart_item['variation'] ?? array() ),
			'cart_item_data' => (array) ( $cart_item['cart_item_data'] ?? array() ),
		);
	}
	return $items;
}

/**
 * Replace the cart with one product for an immediate checkout.
 *
 * Existing cart contents are reserved in the session and restored when the
 * customer returns to the cart without completing the order.
 *
 * @param int   $product_id Product ID.
 * @param int   $variation_id Variation ID.
 * @param int   $quantity Quantity.
 * @param array $attributes Variation attributes.
 * @return bool
 */
function vk_prepare_buy_now_cart( $product_id, $variation_id, $quantity, $attributes ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session ) {
		return false;
	}

	$buy_now_active = (bool) WC()->session->get( 'vk_buy_now_checkout', false );
	if ( ! $buy_now_active ) {
		$reserved = array_merge(
			(array) WC()->session->get( 'vk_reserved_cart_items', array() ),
			vk_snapshot_current_cart()
		);
		WC()->session->set( 'vk_reserved_cart_items', $reserved );
	}

	WC()->cart->empty_cart();
	WC()->session->set( 'vk_buy_now_checkout', true );
	$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
	if ( ! $cart_item_key ) {
		WC()->session->set( 'vk_buy_now_checkout', false );
		vk_restore_reserved_cart_items();
		return false;
	}
	return true;
}

/**
 * Move unselected cart items into a temporary session bucket.
 */
function vk_apply_cart_selection() {
	if ( ! isset( $_POST['vk_checkout_selected'] ) || ! isset( $_POST['vk_cart_selection_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vk_cart_selection_nonce'] ) ), 'vk_cart_selection' ) || ! WC()->cart ) {
		return;
	}
	$selected = isset( $_POST['vk_selected'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['vk_selected'] ) ) : array();
	if ( ! $selected ) {
		wc_add_notice( '購入する商品を1点以上選択してください。', 'error' );
		return;
	}
	$reserved = array();
	foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
		if ( in_array( $cart_key, $selected, true ) ) {
			continue;
		}
		$reserved[] = array(
			'product_id'   => (int) $cart_item['product_id'],
			'variation_id' => (int) $cart_item['variation_id'],
			'quantity'     => (int) $cart_item['quantity'],
			'variation'    => (array) ( $cart_item['variation'] ?? array() ),
			'cart_item_data' => (array) ( $cart_item['cart_item_data'] ?? array() ),
		);
		WC()->cart->remove_cart_item( $cart_key );
	}
	if ( $reserved && WC()->session ) {
		WC()->session->set( 'vk_reserved_cart_items', $reserved );
	}
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}
add_action( 'wp_loaded', 'vk_apply_cart_selection', 25 );

/**
 * Restore cart items that were deliberately left out of checkout.
 */
function vk_restore_reserved_cart_items() {
	if ( ! WC()->session ) {
		return;
	}
	$reserved = (array) WC()->session->get( 'vk_reserved_cart_items', array() );
	if ( ! $reserved ) {
		return;
	}
	foreach ( $reserved as $item ) {
		WC()->cart->add_to_cart(
			(int) $item['product_id'],
			(int) $item['quantity'],
			(int) $item['variation_id'],
			(array) $item['variation'],
			(array) $item['cart_item_data']
		);
	}
	WC()->session->set( 'vk_reserved_cart_items', array() );
}
/**
 * Restore unselected products if checkout was abandoned and the cart is reopened.
 */
function vk_restore_cart_on_cart_view() {
	$show_restored = ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_order_received_page' ) && is_order_received_page() );
	if ( $show_restored && ! isset( $_POST['vk_checkout_selected'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( WC()->session && WC()->session->get( 'vk_buy_now_checkout', false ) ) {
			WC()->cart->empty_cart();
			WC()->session->set( 'vk_buy_now_checkout', false );
		}
		vk_restore_reserved_cart_items();
	}
}
add_action( 'template_redirect', 'vk_restore_cart_on_cart_view', 5 );

/**
 * Remove unavailable lines requested from the cart page.
 */
function vk_remove_out_of_stock_cart_items() {
	if ( empty( $_POST['vk_remove_out_of_stock'] ) || empty( $_POST['woocommerce-cart-nonce'] ) || ! WC()->cart ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ), 'woocommerce-cart' ) ) {
		return;
	}
	foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
		$product = $cart_item['data'] ?? null;
		if ( $product && ! $product->is_in_stock() ) {
			WC()->cart->remove_cart_item( $cart_key );
		}
	}
}
add_action( 'wp_loaded', 'vk_remove_out_of_stock_cart_items', 24 );
