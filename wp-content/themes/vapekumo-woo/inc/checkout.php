<?php
/**
 * Checkout presentation helpers for saved member addresses.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fix Japan as the only checkout country.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function vk_checkout_japan_fields( $fields ) {
	foreach ( array( 'billing', 'shipping' ) as $section ) {
		if ( isset( $fields[ $section ][ $section . '_country' ] ) ) {
			$fields[ $section ][ $section . '_country' ]['type']    = 'hidden';
			$fields[ $section ][ $section . '_country' ]['default'] = 'JP';
			$fields[ $section ][ $section . '_country' ]['required'] = false;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'vk_checkout_japan_fields', 20 );

/**
 * Render one selectable checkout address card.
 *
 * @param array  $address Address data.
 * @param string $type shipping|billing.
 * @param int    $index Display index.
 * @return string
 */
function vk_checkout_address_card_html( $address, $type, $index = 0 ) {
	$type = 'billing' === $type ? 'billing' : 'shipping';
	$name = trim( ( $address['last_name'] ?? '' ) . ' ' . ( $address['first_name'] ?? '' ) );
	$id   = 'vk-' . $type . '-' . sanitize_html_class( $address['id'] ?? wp_generate_uuid4() );
	ob_start();
	?>
	<label class="addr<?php echo ! empty( $address['default'] ) || 0 === $index ? ' is-selected' : ''; ?>" for="<?php echo esc_attr( $id ); ?>" data-address-id="<?php echo esc_attr( $address['id'] ); ?>">
		<input class="addr__radio" id="<?php echo esc_attr( $id ); ?>" type="radio" name="vk_saved_<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $address['id'] ); ?>" data-address="<?php echo esc_attr( wp_json_encode( $address ) ); ?>" <?php checked( ! empty( $address['default'] ) || 0 === $index ); ?>>
		<span class="addr__main">
			<span class="addr__top"><span class="addr__name"><?php echo esc_html( $name ?: 'お名前未設定' ); ?><?php if ( ! empty( $address['default'] ) ) : ?><em class="addr__default">デフォルト</em><?php endif; ?></span><span class="addr__phone"><?php echo esc_html( $address['phone'] ?? '' ); ?></span></span>
			<span class="addr__text"><?php echo esc_html( '〒' . ( $address['postcode'] ?? '' ) . ' 日本 ' . vk_japan_state_label( $address['state'] ?? '' ) . ( $address['city'] ?? '' ) . ( $address['address_1'] ?? '' ) . ' ' . ( $address['address_2'] ?? '' ) ); ?></span>
		</span>
		<button class="addr__edit js-checkout-address-edit" type="button" data-type="<?php echo esc_attr( $type ); ?>" data-address="<?php echo esc_attr( wp_json_encode( $address ) ); ?>">編集</button>
	</label>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render a complete checkout address list.
 *
 * @param array  $addresses Addresses.
 * @param string $type shipping|billing.
 * @return string
 */
function vk_checkout_address_list_html( $addresses, $type ) {
	$html = '';
	foreach ( array_values( (array) $addresses ) as $index => $address ) {
		$html .= vk_checkout_address_card_html( $address, $type, $index );
	}
	return $html;
}

/**
 * Render member shipping and billing cards in the static checkout layout.
 */
function vk_render_checkout_member_address_cards() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$shipping_addresses = vk_get_member_addresses( get_current_user_id(), 'shipping' );
	$billing_addresses  = vk_get_member_addresses( get_current_user_id(), 'billing' );
	?>
	<section class="co-card co-checkout-address" aria-labelledby="vkShippingAddressTitle">
		<div class="co-card__head">
			<span class="co-card__step" aria-hidden="true">1</span>
			<h2 class="co-card__title" id="vkShippingAddressTitle">配送先住所</h2>
			<p class="co-card__hint">お届け先を選択してください</p>
		</div>
		<div class="co-card__body ck-address-select" data-address-select="shipping">
			<div class="addr-list">
			<?php foreach ( $shipping_addresses as $index => $address ) : ?>
				<?php echo vk_checkout_address_card_html( $address, 'shipping', $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
			</div>
			<button class="addr-add js-checkout-address-add" type="button" data-type="shipping">新しい住所を追加</button>
		</div>
	</section>

	<section class="co-card co-checkout-address" aria-labelledby="vkBillingAddressTitle">
		<div class="co-card__head">
			<span class="co-card__step" aria-hidden="true">2</span>
			<h2 class="co-card__title" id="vkBillingAddressTitle">請求先住所</h2>
			<p class="co-card__hint">既定では配送先と同じです</p>
		</div>
		<div class="co-card__body ck-address-select ck-address-select--billing" data-address-select="billing">
			<label class="bill-toggle" for="vkBillDifferent"><input class="bill-toggle__input" id="vkBillDifferent" type="checkbox" name="vk_bill_different" value="1"><span>配送先と異なる請求先住所を使用する</span></label>
			<p class="bill-same" data-bill-same>請求先住所は配送先住所と同じです。</p>
			<div class="bill-wrap" data-billing-panel hidden>
			<div class="addr-list">
				<?php foreach ( $billing_addresses as $index => $address ) : ?>
					<?php echo vk_checkout_address_card_html( $address, 'billing', $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
			<button class="addr-add js-checkout-address-add" type="button" data-type="billing">新しい住所を追加</button>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Current automatic shipping method for the checkout summary card.
 *
 * @return array
 */
function vk_get_checkout_shipping_summary() {
	$summary = array(
		'label' => '通常配送',
		'cost'  => null,
	);
	if ( ! function_exists( 'WC' ) || ! WC()->shipping() ) {
		return $summary;
	}
	$chosen   = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$packages = WC()->shipping()->get_packages();
	foreach ( $packages as $package_key => $package ) {
		$rate_id = $chosen[ $package_key ] ?? '';
		$rate    = $package['rates'][ $rate_id ] ?? null;
		if ( $rate ) {
			$summary['label'] = $rate->get_label();
			$summary['cost']  = (float) $rate->get_cost();
			break;
		}
	}
	return $summary;
}

/**
 * Surface incomplete Cooder credentials to administrators only.
 */
function vk_cooder_configuration_notice() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$settings = (array) get_option( 'woocommerce_cooder_settings', array() );
	if ( empty( $settings['api_merno'] ) || empty( $settings['api_terno'] ) || empty( $settings['api_privatekey'] ) || empty( $settings['api_uri'] ) ) {
		wc_add_notice( '管理者向け：Cooderの加盟店番号、端末番号、APIキー、API URLが未設定です。WooCommerce → 設定 → 支払い → Cooderで設定してください。', 'error' );
	} elseif ( 'https' !== wp_parse_url( (string) $settings['api_uri'], PHP_URL_SCHEME ) ) {
		wc_add_notice( '管理者向け：CooderのAPI URLがHTTPSではありません。カード情報を扱うためHTTPSのURLへ変更してください。', 'error' );
	}
}
add_action( 'woocommerce_before_checkout_form', 'vk_cooder_configuration_notice', 5 );

/**
 * Indicate which checkout address mode is active.
 *
 * @param array $classes Body classes.
 * @return array
 */
function vk_checkout_body_classes( $classes ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
		$classes[] = is_user_logged_in() ? 'vk-checkout-member' : 'vk-checkout-guest';
	}
	return $classes;
}
add_filter( 'body_class', 'vk_checkout_body_classes', 20 );
