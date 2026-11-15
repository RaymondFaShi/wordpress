<?php
/**
 * Member order details.
 *
 * @package Vapekumo_Woo
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$order_id       = $order->get_id();
$order_status   = $order->get_status();
$status_labels  = array(
	'pending'    => array( 'お支払い待ち', 'pending' ),
	'on-hold'    => array( '確認中', 'pending' ),
	'processing' => array( '発送準備中', 'paid' ),
	'completed'  => array( 'お届け完了', 'completed' ),
	'cancelled'  => array( 'キャンセル', 'closed' ),
	'refunded'   => array( '返金済み', 'closed' ),
	'failed'     => array( 'お支払い失敗', 'closed' ),
);
$status_data    = $status_labels[ $order_status ] ?? array( wc_get_order_status_name( $order_status ), 'paid' );
$halted_statuses = array( 'cancelled', 'refunded', 'failed' );
$progress_stage = 0;
if ( 'processing' === $order_status ) {
	$progress_stage = 1;
} elseif ( 'completed' === $order_status ) {
	$progress_stage = 3;
} elseif ( 'on-hold' === $order_status ) {
	$progress_stage = 0;
}
$progress_steps = array( 'ご注文受付', 'お支払い確認', '発送準備', 'お届け完了' );
$actions        = wc_get_account_orders_actions( $order );
$notes          = $order->get_customer_order_notes();
$billing_address  = $order->get_formatted_billing_address();
$shipping_address = $order->get_formatted_shipping_address();
$billing_phone    = $order->get_billing_phone();
$billing_email    = $order->get_billing_email();
?>
<div class="u-order-detail">
	<section class="u-od-hero" aria-labelledby="uOrderDetailTitle">
		<div class="u-od-hero__top">
			<div>
				<p class="u-od-eyebrow">ORDER DETAILS</p>
				<p class="u-od-hero__number" id="uOrderDetailTitle">ご注文 <b>#<?php echo esc_html( $order->get_order_number() ); ?></b></p>
				<p class="u-od-hero__date"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'Y年n月j日 H:i' ) ); ?> にご注文</p>
			</div>
			<span class="u-status u-status--<?php echo esc_attr( $status_data[1] ); ?>"><?php echo esc_html( $status_data[0] ); ?></span>
		</div>

		<ol class="u-od-progress<?php echo in_array( $order_status, $halted_statuses, true ) ? ' is-halted' : ''; ?>" aria-label="注文状況">
			<?php foreach ( $progress_steps as $step_index => $step_label ) : ?>
				<li class="u-od-progress__step<?php echo $step_index <= $progress_stage && ! in_array( $order_status, $halted_statuses, true ) ? ' is-done' : ''; ?><?php echo $step_index === $progress_stage && 'completed' !== $order_status && ! in_array( $order_status, $halted_statuses, true ) ? ' is-current' : ''; ?>">
					<span class="u-od-progress__dot" aria-hidden="true"><i class="bi <?php echo 1 === $step_index ? 'bi-credit-card' : ( 2 === $step_index ? 'bi-box-seam' : ( 3 === $step_index ? 'bi-house-check' : 'bi-bag-check' ) ); ?>"></i></span>
					<span class="u-od-progress__label"><?php echo esc_html( $step_label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( in_array( $order_status, $halted_statuses, true ) ) : ?>
			<p class="u-od-halted">このご注文は<?php echo esc_html( $status_data[0] ); ?>の状態です。ご不明な点はお問い合わせください。</p>
		<?php endif; ?>
	</section>

	<div class="u-od-layout">
		<div class="u-od-main">
			<section class="u-od-card" aria-labelledby="uOrderItemsTitle">
				<div class="u-od-card__head"><h2 id="uOrderItemsTitle"><i class="bi bi-bag" aria-hidden="true"></i>注文商品</h2><span><?php echo esc_html( (string) $order->get_item_count() ); ?>点</span></div>
				<ul class="u-od-items">
					<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
						<?php
						$product = $item->get_product();
						if ( ! $product ) {
							continue;
						}
						$parent_product = $product instanceof WC_Product_Variation ? wc_get_product( $product->get_parent_id() ) : null;
						$display_product = $parent_product ?: $product;
						$variation_lines = vk_get_variation_attribute_lines( $product );
						$item_meta = $variation_lines ? '' : wc_display_item_meta( $item, array( 'echo' => false, 'separator' => ' / ' ) );
						?>
						<li class="u-od-item">
							<a class="u-od-item__thumb" href="<?php echo esc_url( $display_product->get_permalink() ); ?>"><?php echo $display_product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
							<div class="u-od-item__body">
								<p class="u-od-item__brand"><?php echo esc_html( vk_product_brand( $product ) ); ?></p>
								<p class="u-od-item__name"><a href="<?php echo esc_url( $display_product->get_permalink() ); ?>"><?php echo esc_html( $item->get_name() ); ?></a></p>
								<?php if ( $variation_lines ) : ?>
									<p class="u-od-item__meta"><?php echo esc_html( implode( '／', wp_list_pluck( $variation_lines, 'value' ) ) ); ?></p>
								<?php elseif ( $item_meta ) : ?>
									<div class="u-od-item__meta"><?php echo wp_kses_post( $item_meta ); ?></div>
								<?php endif; ?>
							</div>
							<div class="u-od-item__price"><span>数量 <?php echo esc_html( (string) $item->get_quantity() ); ?></span><b><?php echo wp_kses_post( wc_price( (float) $item->get_total() + (float) $item->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></b></div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<div class="u-od-address-grid">
				<section class="u-od-card" aria-labelledby="uShippingAddressTitle">
					<div class="u-od-card__head"><h2 id="uShippingAddressTitle"><i class="bi bi-geo-alt" aria-hidden="true"></i>配送先住所</h2></div>
					<div class="u-od-address"><?php echo wp_kses_post( $shipping_address ?: $billing_address ); ?></div>
				</section>
				<section class="u-od-card" aria-labelledby="uBillingAddressTitle">
					<div class="u-od-card__head"><h2 id="uBillingAddressTitle"><i class="bi bi-receipt" aria-hidden="true"></i>請求先住所</h2></div>
					<div class="u-od-address"><?php echo wp_kses_post( $billing_address ); ?><?php if ( $billing_phone ) : ?><p class="u-od-contact"><i class="bi bi-telephone" aria-hidden="true"></i><?php echo esc_html( $billing_phone ); ?></p><?php endif; ?><?php if ( $billing_email ) : ?><p class="u-od-contact"><i class="bi bi-envelope" aria-hidden="true"></i><?php echo esc_html( $billing_email ); ?></p><?php endif; ?></div>
				</section>
			</div>

			<section class="u-od-card" aria-labelledby="uOrderInfoTitle">
				<div class="u-od-card__head"><h2 id="uOrderInfoTitle"><i class="bi bi-info-circle" aria-hidden="true"></i>ご注文情報</h2></div>
				<dl class="u-od-info-grid">
					<div><dt>お支払い方法</dt><dd><?php echo esc_html( $order->get_payment_method_title() ?: '—' ); ?></dd></div>
					<div><dt>お届け方法</dt><dd><?php echo esc_html( $order->get_shipping_method() ?: '通常配送' ); ?></dd></div>
					<div><dt>注文番号</dt><dd>#<?php echo esc_html( $order->get_order_number() ); ?></dd></div>
					<div><dt>注文日時</dt><dd><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'Y年n月j日 H:i' ) ); ?></dd></div>
				</dl>
			</section>

			<?php if ( $order->get_customer_note() ) : ?>
				<section class="u-od-card" aria-labelledby="uCustomerNoteTitle">
					<div class="u-od-card__head"><h2 id="uCustomerNoteTitle"><i class="bi bi-chat-left-text" aria-hidden="true"></i>ご要望・備考</h2></div>
					<p class="u-od-note"><?php echo esc_html( $order->get_customer_note() ); ?></p>
				</section>
			<?php endif; ?>

			<?php if ( $notes ) : ?>
				<section class="u-od-card" aria-labelledby="uOrderUpdatesTitle">
					<div class="u-od-card__head"><h2 id="uOrderUpdatesTitle"><i class="bi bi-clock-history" aria-hidden="true"></i>注文の更新履歴</h2></div>
					<ol class="u-od-timeline">
						<?php foreach ( $notes as $note ) : ?>
							<li><span class="u-od-timeline__dot" aria-hidden="true"></span><div><time><?php echo esc_html( date_i18n( 'Y年n月j日 H:i', strtotime( $note->comment_date ) ) ); ?></time><p><?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?></p></div></li>
						<?php endforeach; ?>
					</ol>
				</section>
			<?php endif; ?>
		</div>

		<aside class="u-od-side">
			<section class="u-od-summary" aria-labelledby="uOrderTotalTitle">
				<div class="u-od-card__head"><h2 id="uOrderTotalTitle">ご注文金額</h2></div>
				<dl class="u-od-summary__rows">
					<div><dt>商品合計</dt><dd><?php echo wp_kses_post( $order->get_subtotal_to_display() ); ?></dd></div>
					<?php if ( (float) $order->get_discount_total() > 0 ) : ?><div class="is-discount"><dt>クーポン割引</dt><dd>−<?php echo wp_kses_post( wc_price( (float) $order->get_discount_total() + (float) $order->get_discount_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div><?php endif; ?>
					<div><dt>送料</dt><dd><?php echo (float) $order->get_shipping_total() > 0 ? wp_kses_post( wc_price( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), array( 'currency' => $order->get_currency() ) ) ) : '無料'; ?></dd></div>
					<?php if ( (float) $order->get_total_tax() > 0 ) : ?><div><dt>消費税</dt><dd><?php echo wp_kses_post( wc_price( (float) $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ) ); ?></dd></div><?php endif; ?>
					<div class="u-od-summary__total"><dt>合計（税込）</dt><dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd></div>
				</dl>
				<?php if ( $actions ) : ?>
					<div class="u-od-actions">
						<?php foreach ( $actions as $key => $action ) : ?>
							<?php if ( 'view' === $key ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a class="btn <?php echo 'pay' === $key ? 'btn-cta' : 'btn-line'; ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
			<a class="u-od-back" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i>注文履歴に戻る</a>
		</aside>
	</div>
</div>
