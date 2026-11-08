<?php
/**
 * Checkout order review matching the static checkout summary.
 *
 * @package Vapekumo_Woo
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$cart            = WC()->cart;
$shipping_method = vk_get_checkout_shipping_summary();
$coupon_discount = (float) $cart->get_discount_total() + (float) $cart->get_discount_tax();
?>
<div class="woocommerce-checkout-review-order-table">
	<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
	<ul class="co-order">
		<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
			<?php
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$visible  = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );
			if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
				continue;
			}
			$parent_product  = $_product instanceof WC_Product_Variation ? wc_get_product( $_product->get_parent_id() ) : null;
			$display_product = $parent_product ?: $_product;
			$display_name    = apply_filters(
				'woocommerce_cart_item_name',
				$display_product->get_name(),
				$cart_item,
				$cart_item_key
			);
			$variation_lines = vk_get_variation_attribute_lines( $_product );
			$item_total      = apply_filters(
				'woocommerce_cart_item_subtotal',
				$cart->get_product_subtotal( $_product, $cart_item['quantity'] ),
				$cart_item,
				$cart_item_key
			);
			?>
			<li class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'co-order__item', $cart_item, $cart_item_key ) ); ?>">
				<a class="co-order__thumb" href="<?php echo esc_url( $display_product->get_permalink() ); ?>">
					<?php echo $display_product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<div class="co-order__info">
					<p class="co-order__name"><a href="<?php echo esc_url( $display_product->get_permalink() ); ?>"><?php echo wp_kses_post( $display_name ); ?></a></p>
					<?php if ( $variation_lines ) : ?>
						<p class="co-order__opts">
							<?php
							echo esc_html(
								implode(
									'／',
									array_map(
										static function ( $line ) {
											return $line['label'] . '：' . $line['value'];
										},
										$variation_lines
									)
								)
							);
							?>
						</p>
					<?php endif; ?>
					<p class="co-order__meta"><span>数量：<?php echo esc_html( (string) $cart_item['quantity'] ); ?></span><b><?php echo wp_kses_post( $item_total ); ?></b></p>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

	<dl class="co-summary__rows">
		<div class="co-summary__row">
			<dt>商品合計</dt>
			<dd><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></dd>
		</div>
		<div class="co-summary__row co-summary__row--coupon">
			<dt>クーポン割引</dt>
			<dd>
				<?php if ( $coupon_discount > 0 ) : ?>
					−<?php echo wp_kses_post( wc_price( $coupon_discount ) ); ?>
				<?php else : ?>
					<span class="is-none">−</span>
				<?php endif; ?>
			</dd>
		</div>
		<?php if ( $cart->needs_shipping() && $cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<div class="co-summary__row">
				<dt>送料</dt>
				<dd><?php echo null !== $shipping_method['cost'] && $shipping_method['cost'] <= 0 ? '無料' : wp_kses_post( $cart->get_cart_shipping_total() ); ?></dd>
			</div>
			<div class="co-summary__row co-summary__row--method">
				<dt>お届け方法</dt>
				<dd><?php echo esc_html( $shipping_method['label'] ); ?></dd>
			</div>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
		<?php foreach ( $cart->get_fees() as $fee ) : ?>
			<div class="co-summary__row fee">
				<dt><?php echo esc_html( $fee->name ); ?></dt>
				<dd><?php wc_cart_totals_fee_html( $fee ); ?></dd>
			</div>
		<?php endforeach; ?>
		<?php
		$chosen_payment = (string) WC()->session->get( 'chosen_payment_method', 'cooder' );
		$gateways       = WC()->payment_gateways()->payment_gateways();
		$payment_label  = isset( $gateways[ $chosen_payment ] ) ? $gateways[ $chosen_payment ]->get_title() : 'クレジットカード';
		?>
		<div class="co-summary__row co-summary__row--method vk-payment-method-row">
			<dt>お支払い方法</dt>
			<dd><?php echo esc_html( $payment_label ); ?></dd>
		</div>
		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<div class="co-summary__row co-summary__row--total order-total">
			<dt>合計（税込）</dt>
			<dd><?php wc_cart_totals_order_total_html(); ?></dd>
		</div>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</dl>
</div>
