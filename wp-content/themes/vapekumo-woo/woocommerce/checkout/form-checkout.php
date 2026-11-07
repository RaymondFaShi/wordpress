<?php
/**
 * Checkout form matching the supplied checkout.html layout.
 *
 * @package Vapekumo_Woo
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$order_fields    = $checkout->get_checkout_fields( 'order' );
$shipping_method = vk_get_checkout_shipping_summary();
$order_button    = apply_filters( 'woocommerce_order_button_text', __( '注文を確定する', 'woocommerce' ) );
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
	<div class="row checkout__grid">
		<div class="col-12 col-lg-8 vk-checkout-main">
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<?php if ( is_user_logged_in() ) : ?>
				<?php vk_render_checkout_member_address_cards(); ?>
				<div id="customer_details" class="vk-native-checkout-fields" aria-hidden="true">
					<div class="col-1"><?php do_action( 'woocommerce_checkout_billing' ); ?></div>
					<div class="col-2"><?php do_action( 'woocommerce_checkout_shipping' ); ?></div>
				</div>
			<?php else : ?>
				<section class="co-card vk-guest-contact" aria-labelledby="vkGuestContactTitle">
					<div class="co-card__head">
						<span class="co-card__step co-card__step--icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
						<h2 class="co-card__title" id="vkGuestContactTitle">お客様情報</h2>
						<p class="co-card__hint">ゲスト購入</p>
					</div>
					<div class="co-card__body">
						<div class="guest-checkout__intro"><div><p class="guest-checkout__title">アカウントなしで購入できます</p><p class="guest-checkout__text">ご注文確認や発送のお知らせを受け取る情報をご入力ください。</p></div><a class="guest-checkout__login" href="<?php echo esc_url( vk_account_url() ); ?>">会員の方はログイン</a></div>
						<div class="vk-guest-email-slot" data-vk-guest-email-slot></div>
						<p class="field__hint">注文完了後に、確認メールをお送りします。</p>
					</div>
				</section>
				<section class="co-card vk-guest-shipping" aria-labelledby="vkGuestShippingTitle">
					<div class="co-card__head">
						<span class="co-card__step" aria-hidden="true">1</span>
						<h2 class="co-card__title" id="vkGuestShippingTitle">配送先住所</h2>
						<p class="co-card__hint">お届け先を入力してください</p>
					</div>
					<div class="co-card__body">
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
				</section>
				<section class="co-card vk-guest-billing" aria-labelledby="vkGuestBillingTitle">
					<div class="co-card__head">
						<span class="co-card__step" aria-hidden="true">2</span>
						<h2 class="co-card__title" id="vkGuestBillingTitle">請求先住所</h2>
						<p class="co-card__hint">既定では配送先と同じです</p>
					</div>
					<div class="co-card__body">
						<label class="bill-toggle" for="vkGuestBillDifferent"><input class="bill-toggle__input" id="vkGuestBillDifferent" type="checkbox" name="vk_guest_bill_different" value="1"><span>配送先と異なる請求先住所を使用する</span></label>
						<p class="bill-same" data-vk-guest-bill-same>請求先住所は配送先住所と同じです。</p>
						<div class="bill-wrap" data-vk-guest-billing-fields hidden>
							<div id="customer_details" class="col2-set">
								<div class="col-1"><?php do_action( 'woocommerce_checkout_billing' ); ?></div>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<section class="co-card vk-shipping-summary-card" aria-labelledby="vkShippingMethodTitle">
				<div class="co-card__head">
					<span class="co-card__step" aria-hidden="true">3</span>
					<h2 class="co-card__title" id="vkShippingMethodTitle">お届け方法・送料</h2>
					<p class="co-card__hint">商品金額に応じて自動計算</p>
				</div>
				<div class="co-card__body">
					<div class="method is-active">
						<span class="method__radio-mark" aria-hidden="true"></span>
						<span class="method__body"><span class="method__top"><span class="method__name"><?php echo esc_html( $shipping_method['label'] ); ?></span><span class="method__fee"><?php echo null === $shipping_method['cost'] ? '自動計算' : wp_kses_post( wc_price( $shipping_method['cost'] ) ); ?></span></span><span class="method__desc"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上のお買い上げで送料無料です。お届け方法は住所と商品に応じて自動で選択されます。</span></span>
					</div>
				</div>
			</section>

			<section class="co-card vk-payment-card" aria-labelledby="vkPaymentTitle">
				<div class="co-card__head">
					<span class="co-card__step" aria-hidden="true">4</span>
					<h2 class="co-card__title" id="vkPaymentTitle">お支払い方法</h2>
					<p class="co-card__hint">1つ選択してください</p>
				</div>
				<div class="co-card__body">
					<div class="pay-list">
						<?php woocommerce_checkout_payment(); ?>
					</div>
					<div class="co-coupon vk-checkout-coupon">
						<p class="co-coupon__label"><i class="bi bi-receipt" aria-hidden="true"></i>クーポン</p>
						<div class="co-coupon__form">
							<label class="visually-hidden" for="vkCouponCode">クーポンコード</label>
							<input class="co-coupon__input" id="vkCouponCode" name="vk_coupon_code" type="text" placeholder="クーポンコードを入力" autocomplete="off" spellcheck="false">
							<button class="btn btn-cta co-coupon__apply" type="button">適用</button>
						</div>
						<p class="co-coupon__msg" data-vk-coupon-message role="status" aria-live="polite" hidden></p>
					</div>
				</div>
			</section>

			<section class="co-card vk-order-notes-card" aria-labelledby="vkOrderNotesTitle">
				<div class="co-card__head">
					<span class="co-card__step" aria-hidden="true">5</span>
					<h2 class="co-card__title" id="vkOrderNotesTitle">ご要望・備考</h2>
					<p class="co-card__hint">任意</p>
				</div>
				<div class="co-card__body">
					<?php
					if ( isset( $order_fields['order_comments'] ) ) {
						woocommerce_form_field( 'order_comments', $order_fields['order_comments'], $checkout->get_value( 'order_comments' ) );
					}
					?>
					<p class="field__hint">ご記入いただいた内容によっては、ご対応できない場合があります。</p>
				</div>
			</section>
		</div>

		<div class="col-12 col-lg-4">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
			<aside class="co-summary" aria-labelledby="order_review_heading">
				<h2 class="co-summary__head" id="order_review_heading">ご注文内容</h2>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php woocommerce_order_review(); ?>
				</div>
				<div class="co-summary__cta">
					<button type="submit" class="btn btn-cta co-summary__submit" id="vkPlaceOrder" name="woocommerce_checkout_place_order" value="<?php echo esc_attr( $order_button ); ?>" data-value="<?php echo esc_attr( $order_button ); ?>"><?php echo esc_html( $order_button ); ?></button>
					<a class="co-summary__back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">カートに戻る</a>
					<p class="co-summary__note">ご注文内容をご確認のうえ確定してください。確定後、確認メールをお送りします。20歳以上の方のみご購入いただけます。</p>
				</div>
			</aside>
			<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
		</div>
	</div>
</form>

<div class="modal fade addr-modal vk-checkout-address-modal" id="vkCheckoutAddressModal" tabindex="-1" aria-labelledby="vkCheckoutAddressModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<form id="vkCheckoutAddressForm">
				<div class="modal-header"><p class="modal-title" id="vkCheckoutAddressModalTitle">住所を追加</p><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="閉じる"></button></div>
				<div class="modal-body">
					<input type="hidden" name="type" id="vkCheckoutAddressType" value="shipping">
					<input type="hidden" name="id" id="vkCheckoutAddressId" value="">
					<div class="form-grid">
						<div class="field"><label class="field__label" for="vkCheckoutAddressLastName">姓<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressLastName" name="last_name" autocomplete="family-name" required></div>
						<div class="field"><label class="field__label" for="vkCheckoutAddressFirstName">名<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressFirstName" name="first_name" autocomplete="given-name" required></div>
						<div class="field"><label class="field__label" for="vkCheckoutAddressPostcode">郵便番号<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressPostcode" name="postcode" inputmode="numeric" autocomplete="postal-code" required></div>
						<?php vk_render_japanese_state_field( 'state', 'vkCheckoutAddressState', 'field', 'field__label', 'field__select' ); ?>
						<div class="field form-grid__full"><label class="field__label" for="vkCheckoutAddressCity">市区町村<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressCity" name="city" autocomplete="address-level2" required></div>
						<div class="field form-grid__full"><label class="field__label" for="vkCheckoutAddressLine1">住所<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressLine1" name="address_1" autocomplete="address-line1" required></div>
						<div class="field form-grid__full"><label class="field__label" for="vkCheckoutAddressLine2">建物名・部屋番号</label><input class="field__input" id="vkCheckoutAddressLine2" name="address_2" autocomplete="address-line2"></div>
						<div class="field form-grid__full"><label class="field__label" for="vkCheckoutAddressPhone">電話番号<span class="field__req" aria-hidden="true">必須</span></label><input class="field__input" id="vkCheckoutAddressPhone" name="phone" type="tel" autocomplete="tel" required></div>
					</div>
					<label class="check" for="vkCheckoutAddressDefault"><input type="checkbox" id="vkCheckoutAddressDefault" name="default" value="1"><span>この住所を既定の住所にする</span></label>
					<p class="field__error" data-vk-address-error role="alert" hidden></p>
				</div>
				<div class="modal-footer"><button class="btn co-btn-ghost" type="button" data-bs-dismiss="modal">キャンセル</button><button class="btn btn-cta" type="submit">住所を保存する</button></div>
			</form>
		</div>
	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
