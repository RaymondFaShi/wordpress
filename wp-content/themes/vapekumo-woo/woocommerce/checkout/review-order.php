<?php
/**
 * Checkout order review matching the static checkout summary.
 *
 * @package Vapekumo_Woo
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table">
	<thead><tr><th class="product-name">商品</th><th class="product-total">小計</th></tr></thead>
	<tbody>
		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
		<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
			<?php
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$visible  = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );
			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}
			$parent_product = $_product instanceof WC_Product_Variation ? wc_get_product( $_product->get_parent_id() ) : null;
			$display_name   = $parent_product ? $parent_product->get_name() : WC()->cart->get_item_product_name( $cart_item, $_product );
			$product_name   = apply_filters( 'woocommerce_cart_item_name', $display_name, $cart_item, $cart_item_key );
			$variation_lines = vk_get_variation_attribute_lines( $_product );
			$meta            = $variation_lines ? '' : wc_get_formatted_cart_item_data( $cart_item, false, $product_name );
			?>
			<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
				<td class="product-name">
					<span class="co-summary-product">
						<span class="co-summary-product__thumb"><?php echo $_product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="co-summary-product__body">
							<span class="co-summary-product__name"><?php echo wp_kses_post( $product_name ); ?></span>
							<?php if ( $variation_lines ) : ?>
								<span class="co-summary-product__meta"><?php foreach ( $variation_lines as $variation_line ) : ?><span><?php echo esc_html( $variation_line['label'] . '：' . $variation_line['value'] ); ?></span><?php endforeach; ?></span>
							<?php endif; ?>
							<?php if ( $meta ) : ?><span class="co-summary-product__meta"><?php echo wp_kses_post( $meta ); ?></span><?php endif; ?>
							<span class="co-summary-product__qty">数量：<?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>
						</span>
					</span>
				</td>
				<td class="product-total"><?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			</tr>
		<?php endforeach; ?>
		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	</tbody>
	<tfoot>
		<tr class="cart-subtotal"><th>商品合計</th><td><?php wc_cart_totals_subtotal_html(); ?></td></tr>
		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>"><th><?php wc_cart_totals_coupon_label( $coupon ); ?></th><td><?php wc_cart_totals_coupon_html( $coupon ); ?></td></tr>
		<?php endforeach; ?>
		<?php if ( ! WC()->cart->get_coupons() ) : ?>
			<tr class="vk-coupon-empty"><th>クーポン割引</th><td><span>−</span></td></tr>
		<?php endif; ?>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php $shipping_summary = vk_get_checkout_shipping_summary(); ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<tr class="woocommerce-shipping-total">
				<th>送料</th>
				<td><?php echo null !== $shipping_summary['cost'] && $shipping_summary['cost'] <= 0 ? '無料' : wp_kses_post( WC()->cart->get_cart_shipping_total() ); ?></td>
			</tr>
			<tr class="vk-shipping-method-row">
				<th>お届け方法</th>
				<td><?php echo esc_html( $shipping_summary['label'] ); ?></td>
			</tr>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee"><th><?php echo esc_html( $fee->name ); ?></th><td><?php wc_cart_totals_fee_html( $fee ); ?></td></tr>
		<?php endforeach; ?>
		<?php
		$chosen_payment = (string) WC()->session->get( 'chosen_payment_method', 'cooder' );
		$gateways       = WC()->payment_gateways()->payment_gateways();
		$payment_label  = isset( $gateways[ $chosen_payment ] ) ? $gateways[ $chosen_payment ]->get_title() : 'クレジットカード';
		?>
		<tr class="vk-payment-method-row"><th>お支払い方法</th><td><?php echo esc_html( $payment_label ); ?></td></tr>
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<tr class="order-total"><th>合計（税込）</th><td><?php wc_cart_totals_order_total_html(); ?></td></tr>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</tfoot>
</table>
