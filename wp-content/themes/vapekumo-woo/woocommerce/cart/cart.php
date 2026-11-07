<?php
/**
 * Cart page matching the supplied static template.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/*
 * Deployment compatibility: the one-click sync extracts this template before
 * the current PHP process has loaded the new helpers.php. Keep a guarded
 * fallback here so updating pages cannot trigger a missing-function fatal.
 */
if ( ! function_exists( 'vk_get_variation_attribute_lines' ) ) {
	/**
	 * Human-readable variation attributes for the cart.
	 *
	 * @param WC_Product $product Product object.
	 * @return array
	 */
	function vk_get_variation_attribute_lines( $product ) {
		if ( ! $product instanceof WC_Product_Variation ) {
			return array();
		}
		$labels = array(
			'pa_flavor'   => 'フレーバー',
			'pa_nicotine' => 'ニコチン',
			'pa_puffs'    => '吸引回数',
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
}

do_action( 'woocommerce_before_cart' );

$cart             = WC()->cart;
$shipping_config  = vk_get_shipping_config();
$free_threshold   = (float) $shipping_config['threshold'];
$subtotal         = (float) $cart->get_subtotal();
$shipping_fill    = min( 100, $subtotal / $free_threshold * 100 );
$shipping_is_free = $subtotal >= $free_threshold;
$shipping_total   = $shipping_is_free ? 0 : (float) $shipping_config['fee'];
$has_unavailable  = false;
foreach ( $cart->get_cart() as $cart_item ) {
	$product = $cart_item['data'] ?? null;
	if ( $product && ( ! $product->is_in_stock() || ! $product->has_enough_stock( $cart_item['quantity'] ) ) ) {
		$has_unavailable = true;
		break;
	}
}
?>
<div class="row cart__grid">
	<div class="col-12 col-lg-8">
		<form class="woocommerce-cart-form" id="cartForm" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
			<?php do_action( 'woocommerce_before_cart_table' ); ?>
			<?php if ( ! $cart->is_empty() ) : ?>
				<?php if ( $has_unavailable ) : ?>
					<div class="alert cart-alert d-flex align-items-center flex-wrap" role="status"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><p class="cart-alert__text">在庫切れ、または在庫数が不足している商品があります。対象商品は購入対象に選択できません。</p><button class="btn cart-alert__btn ms-auto" type="submit" name="vk_remove_out_of_stock" value="1">在庫切れ商品を一括削除</button></div>
				<?php endif; ?>
				<div class="cart-pick"><label class="cart-pick__all"><input class="form-check-input" id="cartPickAll" type="checkbox" checked><span class="cart-pick__label">すべての商品を選択</span></label><p class="cart-pick__count"><b id="cartPickCount"><?php echo esc_html( (string) $cart->get_cart_contents_count() ); ?></b>点を選択中</p></div>
				<div class="cart-list">
					<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
						<?php
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
						if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							continue;
						}
						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						$stock_quantity    = $_product->get_stock_quantity();
						$is_unavailable    = ! $_product->is_in_stock() || ! $_product->has_enough_stock( $cart_item['quantity'] );
						$is_low_stock      = ! $is_unavailable && null !== $stock_quantity && $stock_quantity > 0 && $stock_quantity < 10;
						$regular_price     = (float) $_product->get_regular_price();
						$current_price     = (float) $_product->get_price();
						$discount_percent  = $regular_price > $current_price && $regular_price > 0 ? (int) round( ( ( $regular_price - $current_price ) / $regular_price ) * 100 ) : 0;
						$variation_lines   = vk_get_variation_attribute_lines( $_product );
						$parent_product    = $_product instanceof WC_Product_Variation ? wc_get_product( $_product->get_parent_id() ) : null;
						$display_name      = $parent_product ? $parent_product->get_name() : $_product->get_name();
						?>
						<article class="cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?><?php echo $is_unavailable ? ' is-out' : ''; ?>">
							<label class="cart-item__pick"><input class="form-check-input js-cart-pick" type="checkbox" name="vk_selected[]" value="<?php echo esc_attr( $cart_item_key ); ?>" <?php checked( ! $is_unavailable ); ?> <?php disabled( $is_unavailable ); ?>><span class="visually-hidden"><?php echo esc_html( $_product->get_name() ); ?>を購入対象にする</span></label>
							<a class="cart-item__thumb" href="<?php echo esc_url( $product_permalink ); ?>"><?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
							<div class="cart-item__body">
								<div class="cart-item__head">
									<div class="cart-item__info">
									<h2 class="cart-item__title"><?php if ( $is_unavailable ) : ?><span class="cart-item__badge cart-item__badge--out">在庫切れ</span><?php elseif ( $is_low_stock ) : ?><span class="cart-item__badge">残り<?php echo esc_html( (string) $stock_quantity ); ?>点</span><?php endif; ?><a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $display_name, $cart_item, $cart_item_key ) ); ?></a></h2>
										<div class="cart-item__opts">
											<?php foreach ( $variation_lines as $variation_line ) : ?>
												<span class="cart-item__variant"><?php echo esc_html( $variation_line['label'] ); ?>：<b><?php echo esc_html( $variation_line['value'] ); ?></b></span>
											<?php endforeach; ?>
											<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>
										</div>
									</div>
									<p class="cart-item__price"><?php if ( $discount_percent ) : ?><span class="cart-item__was"><?php echo wp_kses_post( wc_price( $regular_price ) ); ?></span><?php endif; ?><span class="cart-item__now"><?php echo apply_filters( 'woocommerce_cart_item_price', $cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php if ( $discount_percent ) : ?><span class="cart-item__off"><?php echo esc_html( (string) $discount_percent ); ?>%OFF</span><?php endif; ?></p>
								</div>
								<div class="cart-item__foot">
									<div class="qty woocommerce-quantity">
										<?php
										if ( $_product->is_sold_individually() ) {
											$min = 1;
											$max = 1;
										} else {
											$min = 0;
											$max = $_product->get_max_purchase_quantity();
										}
										echo woocommerce_quantity_input( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											array(
												'input_name'   => "cart[{$cart_item_key}][qty]",
												'input_value'  => $cart_item['quantity'],
												'max_value'    => $max,
												'min_value'    => $min,
												'product_name' => $_product->get_name(),
											),
											$_product,
											false
										);
										?>
									</div>
									<div class="cart-item__actions">
										<p class="cart-item__sum"><span>小計</span><b><?php echo apply_filters( 'woocommerce_cart_item_subtotal', $cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></b></p>
										<a class="cart-item__del cart_item__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="<?php echo esc_attr( sprintf( '%s を削除', $_product->get_name() ) ); ?>"><i class="bi bi-trash" aria-hidden="true"></i><span>削除</span></a>
									</div>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="cart-empty">
					<p class="cart-empty__title">カートに商品がありません</p>
					<p class="cart-empty__text">お気に入りの商品を探してカートに追加してください。</p>
					<a class="btn btn-cta" href="<?php echo esc_url( vk_shop_url() ); ?>">商品を探す</a>
				</div>
			<?php endif; ?>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>
			<?php do_action( 'woocommerce_cart_contents' ); ?>
			<?php if ( ! $cart->is_empty() ) : ?>
				<div class="cart-actions">
					<?php if ( wc_coupons_enabled() ) : ?>
						<label class="cart-coupon"><span class="visually-hidden">クーポンコード</span><input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="クーポンコード"><button type="submit" class="btn btn-line" name="apply_coupon" value="クーポンを適用">適用</button></label>
					<?php endif; ?>
					<button type="submit" class="btn btn-line" name="update_cart" value="カートを更新">カートを更新</button>
					<button type="submit" class="btn btn-cta" name="vk_checkout_selected" value="1">選択した商品でレジへ</button>
					<?php do_action( 'woocommerce_cart_actions' ); ?>
					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					<?php wp_nonce_field( 'vk_cart_selection', 'vk_cart_selection_nonce' ); ?>
				</div>
			<?php endif; ?>
			<?php do_action( 'woocommerce_after_cart_table' ); ?>
		</form>
		<ul class="cart-assure"><li>正規品のみを取り扱い</li><li><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</li><li>15時までのご注文は当日発送</li></ul>
	</div>
	<div class="col-12 col-lg-4">
		<aside class="cart-summary" aria-labelledby="summaryTitle">
			<h2 class="cart-summary__head" id="summaryTitle">ご注文内容</h2>
			<dl class="cart-summary__rows">
				<div class="cart-summary__row"><dt>商品合計</dt><dd><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></dd></div>
				<?php if ( $cart->get_discount_total() > 0 ) : ?><div class="cart-summary__row"><dt>クーポン割引</dt><dd>−<?php echo wp_kses_post( wc_price( $cart->get_discount_total() ) ); ?></dd></div><?php endif; ?>
				<div class="cart-summary__row"><dt>送料</dt><dd><?php echo $shipping_is_free ? '無料' : wp_kses_post( wc_price( $shipping_total ) ); ?></dd></div>
				<div class="cart-summary__row cart-summary__row--total"><dt>合計（税込）</dt><dd><?php echo wp_kses_post( $cart->get_total() ); ?></dd></div>
			</dl>
			<div class="cart-ship<?php echo $shipping_is_free ? ' is-free' : ''; ?>" id="cartShip">
				<div class="cart-ship__bar"><span class="cart-ship__fill" style="width:<?php echo esc_attr( (string) $shipping_fill ); ?>%"></span></div>
				<p class="cart-ship__text"><?php echo $shipping_is_free ? '送料無料が適用されました。' : 'あと' . wp_kses_post( wc_price( $free_threshold - $subtotal ) ) . 'で送料無料です。'; ?></p>
			</div>
			<div class="cart-summary__cta">
				<button class="btn btn-cta cart-summary__checkout<?php echo $cart->is_empty() ? ' is-disabled' : ''; ?>" type="submit" form="cartForm" name="vk_checkout_selected" value="1" <?php disabled( $cart->is_empty() ); ?>>選択した商品でレジへ</button>
				<a class="cart-summary__continue" href="<?php echo esc_url( vk_shop_url() ); ?>">買い物を続ける</a>
				<p class="cart-summary__note">送料は商品合計に応じて自動計算されます。20歳以上の方のみご購入いただけます。</p>
			</div>
		</aside>
	</div>
</div>
<?php do_action( 'woocommerce_after_cart' ); ?>
