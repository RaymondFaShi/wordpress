<?php
/**
 * Account orders in the vapekumo card layout.
 *
 * @package Vapekumo_Woo
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );
?>
<?php if ( $has_orders ) : ?>
	<div class="u-orders">
		<?php foreach ( $customer_orders->orders as $customer_order ) : ?>
			<?php
			$order      = wc_get_order( $customer_order );
			$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			$status     = $order->get_status();
			$status_map = array(
				'pending'    => array( '支払い待ち', 'pending' ),
				'on-hold'    => array( '保留中', 'pending' ),
				'processing' => array( '処理中', 'paid' ),
				'completed'  => array( '完了', 'completed' ),
				'cancelled'  => array( 'キャンセル', 'closed' ),
				'refunded'   => array( '返金済み', 'closed' ),
				'failed'     => array( '失敗', 'closed' ),
			);
			$status_data = $status_map[ $status ] ?? array( wc_get_order_status_name( $status ), 'paid' );
			?>
			<article class="u-order" data-status="<?php echo esc_attr( $status ); ?>">
				<div class="u-order__head">
					<div class="u-order__meta"><span>注文番号 <b><?php echo esc_html( $order->get_order_number() ); ?></b></span><span>注文日 <b><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'Y年n月j日' ) ); ?></b></span><span>商品 <b><?php echo esc_html( (string) $item_count ); ?>点</b></span></div>
					<span class="u-status u-status--<?php echo esc_attr( $status_data[1] ); ?>"><?php echo esc_html( $status_data[0] ); ?></span>
				</div>
				<ul class="u-order__items">
					<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
						<?php
						$product = $item->get_product();
						if ( ! $product ) {
							continue;
						}
						$meta = wc_display_item_meta( $item, array( 'echo' => false, 'separator' => ' / ' ) );
						?>
						<li class="u-order__item">
							<a class="u-order__thumb" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo $product->get_image( 'thumbnail', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
							<div class="u-order__info"><p class="u-order__brand"><?php echo esc_html( vk_product_brand( $product ) ); ?></p><p class="u-order__name"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $item->get_name() ); ?></a></p><?php if ( $meta ) : ?><div class="u-order__opt"><?php echo wp_kses_post( $meta ); ?></div><?php endif; ?></div>
							<div class="u-order__nums"><p class="u-order__qty">数量：<?php echo esc_html( (string) $item->get_quantity() ); ?></p><p class="u-order__sum"><?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></p></div>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="u-order__foot">
					<p class="u-order__total">合計（税込）<b><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></b></p>
					<div class="u-order__actions">
						<a class="btn btn-line" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">注文詳細</a>
						<?php foreach ( wc_get_account_orders_actions( $order ) as $key => $action ) : ?>
							<?php if ( 'view' === $key ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<a class="btn <?php echo 'pay' === $key ? 'btn-cta' : 'btn-line'; ?>" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
	<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
		<nav class="pager" aria-label="注文履歴のページ送り">
			<?php if ( 1 !== $current_page ) : ?><a class="pager__link" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">前へ</a><?php endif; ?>
			<span class="pager__link is-active"><?php echo esc_html( (string) $current_page ); ?> / <?php echo esc_html( (string) $customer_orders->max_num_pages ); ?></span>
			<?php if ( (int) $customer_orders->max_num_pages !== $current_page ) : ?><a class="pager__link" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">次へ</a><?php endif; ?>
		</nav>
	<?php endif; ?>
<?php else : ?>
	<div class="u-empty"><span class="u-empty__icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span><p class="u-empty__title">注文履歴はまだありません</p><p class="u-empty__text">商品を探して、最初のお買い物を始めましょう。</p><a class="btn btn-cta" href="<?php echo esc_url( vk_shop_url() ); ?>">商品を探す</a></div>
<?php endif; ?>
<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
