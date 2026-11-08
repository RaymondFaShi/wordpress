<?php
/**
 * Checkout presentation helpers for saved member addresses.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Japanese labels for WooCommerce's Japanese prefecture list.
 *
 * @return array
 */
function vk_japanese_prefecture_labels() {
	return array(
		'JP01' => '北海道',
		'JP02' => '青森県',
		'JP03' => '岩手県',
		'JP04' => '宮城県',
		'JP05' => '秋田県',
		'JP06' => '山形県',
		'JP07' => '福島県',
		'JP08' => '茨城県',
		'JP09' => '栃木県',
		'JP10' => '群馬県',
		'JP11' => '埼玉県',
		'JP12' => '千葉県',
		'JP13' => '東京都',
		'JP14' => '神奈川県',
		'JP15' => '新潟県',
		'JP16' => '富山県',
		'JP17' => '石川県',
		'JP18' => '福井県',
		'JP19' => '山梨県',
		'JP20' => '長野県',
		'JP21' => '岐阜県',
		'JP22' => '静岡県',
		'JP23' => '愛知県',
		'JP24' => '三重県',
		'JP25' => '滋賀県',
		'JP26' => '京都府',
		'JP27' => '大阪府',
		'JP28' => '兵庫県',
		'JP29' => '奈良県',
		'JP30' => '和歌山県',
		'JP31' => '鳥取県',
		'JP32' => '島根県',
		'JP33' => '岡山県',
		'JP34' => '広島県',
		'JP35' => '山口県',
		'JP36' => '徳島県',
		'JP37' => '香川県',
		'JP38' => '愛媛県',
		'JP39' => '高知県',
		'JP40' => '福岡県',
		'JP41' => '佐賀県',
		'JP42' => '長崎県',
		'JP43' => '熊本県',
		'JP44' => '大分県',
		'JP45' => '宮崎県',
		'JP46' => '鹿児島県',
		'JP47' => '沖縄県',
	);
}

/**
 * Use Japanese prefecture labels even when the site locale is not Japanese.
 *
 * @param array $states WooCommerce states keyed by country.
 * @return array
 */
function vk_localize_japanese_prefectures( $states ) {
	if ( isset( $states['JP'] ) ) {
		$states['JP'] = vk_japanese_prefecture_labels();
	}
	return $states;
}
add_filter( 'woocommerce_states', 'vk_localize_japanese_prefectures' );

/**
 * Render the WooCommerce state control used by address modals.
 *
 * @param string $key         Posted field name.
 * @param string $field_id    Field ID.
 * @param string $field_class Wrapper class.
 * @param string $label_class Label class.
 * @param string $input_class Select class.
 */
function vk_render_japanese_state_field( $key, $field_id, $field_class, $label_class, $input_class ) {
	if ( ! function_exists( 'woocommerce_form_field' ) ) {
		return;
	}
	woocommerce_form_field(
		$key,
		array(
			'type'              => 'state',
			'country'           => 'JP',
			'label'             => '都道府県',
			'required'          => true,
			'id'                => $field_id,
			'class'             => array( $field_class, 'vk-state-field' ),
			'label_class'       => array( $label_class ),
			'input_class'       => array( $input_class ),
			'placeholder'       => '都道府県を選択',
			'custom_attributes' => array(
				'required' => 'required',
			),
		),
		''
	);
}

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
	$fields['order']['vk_shipping_address_id'] = array(
		'type'     => 'hidden',
		'required' => false,
	);
	$fields['order']['vk_billing_address_id'] = array(
		'type'     => 'hidden',
		'required' => false,
	);
	$fields['order']['vk_billing_same_as_shipping'] = array(
		'type'     => 'hidden',
		'required' => false,
	);
	$fields['order']['vk_bill_different'] = array(
		'type'     => 'hidden',
		'required' => false,
	);
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'vk_checkout_japan_fields', 20 );

/**
 * Render one selectable checkout address card.
 *
 * @param array  $address     Address data.
 * @param string $type        shipping|billing.
 * @param bool   $is_selected Whether the address is selected.
 * @return string
 */
function vk_checkout_address_card_html( $address, $type, $is_selected = false ) {
	$type       = 'billing' === $type ? 'billing' : 'shipping';
	$name       = trim( ( $address['last_name'] ?? '' ) . ' ' . ( $address['first_name'] ?? '' ) );
	$address_id = sanitize_text_field( $address['id'] ?? '' );
	$id         = 'vk-' . $type . '-' . sanitize_html_class( $address_id ?: wp_generate_uuid4() );
	$is_default = ! empty( $address['default'] );
	ob_start();
	?>
	<label class="addr<?php echo $is_selected ? ' is-selected' : ''; ?>" for="<?php echo esc_attr( $id ); ?>" data-address-id="<?php echo esc_attr( $address_id ); ?>" data-address-default="<?php echo $is_default ? '1' : '0'; ?>">
		<input class="addr__radio" id="<?php echo esc_attr( $id ); ?>" type="radio" name="vk_saved_<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $address_id ); ?>" data-address="<?php echo esc_attr( wp_json_encode( $address ) ); ?>" data-address-default="<?php echo $is_default ? '1' : '0'; ?>" <?php checked( $is_selected ); ?>>
		<span class="addr__main">
			<span class="addr__top"><span class="addr__name"><?php echo esc_html( $name ?: 'お名前未設定' ); ?><?php if ( $is_default ) : ?><em class="addr__default">デフォルト</em><?php endif; ?></span><span class="addr__phone"><?php echo esc_html( $address['phone'] ?? '' ); ?></span></span>
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
 * @param array  $addresses   Addresses.
 * @param string $type        shipping|billing.
 * @param string $selected_id Selected address ID.
 * @return string
 */
function vk_checkout_address_list_html( $addresses, $type, $selected_id = '' ) {
	$html = '';
	foreach ( array_values( (array) $addresses ) as $address ) {
		$is_selected = '' !== (string) $selected_id && (string) ( $address['id'] ?? '' ) === (string) $selected_id;
		$html       .= vk_checkout_address_card_html( $address, $type, $is_selected );
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
	$initial_shipping   = $shipping_addresses[0] ?? array();
	$initial_billing    = $billing_addresses[0] ?? array();
	$checkout           = function_exists( 'WC' ) ? WC()->checkout() : null;
	$posted_shipping_id = $checkout ? sanitize_text_field( (string) $checkout->get_value( 'vk_shipping_address_id' ) ) : '';
	$posted_billing_id  = $checkout ? sanitize_text_field( (string) $checkout->get_value( 'vk_billing_address_id' ) ) : '';
	$billing_different  = $checkout && wc_string_to_bool( (string) $checkout->get_value( 'vk_bill_different' ) );

	$posted_shipping = vk_get_member_address_by_id( get_current_user_id(), 'shipping', $posted_shipping_id );
	if ( $posted_shipping ) {
		$initial_shipping = $posted_shipping;
	}
	foreach ( $shipping_addresses as $address ) {
		if ( ! $posted_shipping && ! empty( $address['default'] ) ) {
			$initial_shipping = $address;
			break;
		}
	}

	$posted_billing = vk_get_member_address_by_id( get_current_user_id(), 'billing', $posted_billing_id );
	if ( $posted_billing ) {
		$initial_billing = $posted_billing;
	}
	foreach ( $billing_addresses as $address ) {
		if ( ! $posted_billing && ! empty( $address['default'] ) ) {
			$initial_billing = $address;
			break;
		}
	}
	$initial_shipping_id = (string) ( $initial_shipping['id'] ?? '' );
	$initial_billing_id  = (string) ( $initial_billing['id'] ?? '' );
	?>
	<section class="co-card co-checkout-address" aria-labelledby="vkShippingAddressTitle">
		<div class="co-card__head">
			<span class="co-card__step" aria-hidden="true">1</span>
			<h2 class="co-card__title" id="vkShippingAddressTitle">配送先住所</h2>
			<p class="co-card__hint">お届け先を選択してください</p>
		</div>
		<div class="co-card__body ck-address-select" data-address-select="shipping">
			<input type="hidden" name="vk_shipping_address_id" id="vkShippingAddressId" value="<?php echo esc_attr( $initial_shipping_id ); ?>">
			<div class="addr-list">
			<?php foreach ( $shipping_addresses as $address ) : ?>
				<?php echo vk_checkout_address_card_html( $address, 'shipping', (string) ( $address['id'] ?? '' ) === $initial_shipping_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
			<label class="bill-toggle" for="vkBillDifferent"><input class="bill-toggle__input" id="vkBillDifferent" type="checkbox" name="vk_bill_different" value="1" <?php checked( $billing_different ); ?>><span>配送先と異なる請求先住所を使用する</span></label>
			<input type="hidden" name="vk_billing_same_as_shipping" id="vkBillingSameAsShipping" value="<?php echo $billing_different ? '0' : '1'; ?>" data-own-id="<?php echo esc_attr( $initial_billing_id ); ?>">
			<input type="hidden" name="vk_billing_address_id" id="vkBillingAddressId" value="<?php echo esc_attr( $billing_different ? $initial_billing_id : $initial_shipping_id ); ?>">
			<p class="bill-same" data-bill-same<?php echo $billing_different ? ' hidden' : ''; ?>>請求先住所は配送先住所と同じです。</p>
			<div class="bill-wrap" data-billing-panel<?php echo $billing_different ? '' : ' hidden'; ?>>
			<div class="addr-list">
				<?php foreach ( $billing_addresses as $address ) : ?>
					<?php echo vk_checkout_address_card_html( $address, 'billing', $billing_different && (string) ( $address['id'] ?? '' ) === $initial_billing_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
			<button class="addr-add js-checkout-address-add" type="button" data-type="billing">新しい住所を追加</button>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Find one saved address by ID for the current member.
 *
 * @param int    $user_id    User ID.
 * @param string $type       shipping|billing.
 * @param string $address_id Saved address UUID.
 * @return array|null
 */
function vk_get_member_address_by_id( $user_id, $type, $address_id ) {
	$address_id = sanitize_text_field( $address_id );
	if ( '' === $address_id ) {
		return null;
	}
	foreach ( vk_get_member_addresses( $user_id, $type ) as $address ) {
		if ( isset( $address['id'] ) && hash_equals( (string) $address['id'], $address_id ) ) {
			return $address;
		}
	}
	return null;
}

/**
 * Load the default address when a member checkout form has no selected ID.
 *
 * @param int    $user_id User ID.
 * @param string $type    shipping|billing.
 * @return array|null
 */
function vk_get_default_member_address( $user_id, $type ) {
	$addresses = vk_get_member_addresses( $user_id, $type );
	foreach ( $addresses as $address ) {
		if ( ! empty( $address['default'] ) ) {
			return $address;
		}
	}
	return $addresses[0] ?? null;
}

/**
 * Replace member checkout fields with data loaded from saved address IDs.
 *
 * @param array $data WooCommerce checkout posted data.
 * @return array
 */
function vk_checkout_apply_saved_member_addresses( $data ) {
	if ( ! is_user_logged_in() ) {
		return $data;
	}
	$user_id     = get_current_user_id();
	$shipping_id = sanitize_text_field( $data['vk_shipping_address_id'] ?? '' );
	$shipping    = vk_get_member_address_by_id( $user_id, 'shipping', $shipping_id );
	if ( ! $shipping ) {
		$shipping    = vk_get_default_member_address( $user_id, 'shipping' );
		$shipping_id = (string) ( $shipping['id'] ?? '' );
	}

	$same_billing = empty( $data['vk_bill_different'] );
	$billing_id   = $same_billing ? $shipping_id : sanitize_text_field( $data['vk_billing_address_id'] ?? '' );
	$billing      = $same_billing ? $shipping : vk_get_member_address_by_id( $user_id, 'billing', $billing_id );
	if ( ! $billing && ! $same_billing ) {
		$billing    = vk_get_default_member_address( $user_id, 'billing' );
		$billing_id = (string) ( $billing['id'] ?? '' );
	}
	if ( ! $shipping || ! $billing ) {
		return $data;
	}

	$fields = array( 'first_name', 'last_name', 'company', 'country', 'state', 'postcode', 'city', 'address_1', 'address_2', 'phone' );
	foreach ( $fields as $field ) {
		$data[ 'shipping_' . $field ] = (string) ( $shipping[ $field ] ?? ( 'country' === $field ? 'JP' : '' ) );
		$data[ 'billing_' . $field ]  = (string) ( $billing[ $field ] ?? ( 'country' === $field ? 'JP' : '' ) );
	}
	$data['shipping_country']        = 'JP';
	$data['billing_country']         = 'JP';
	$data['ship_to_different_address'] = 1;
	$data['vk_shipping_address_id']  = $shipping_id;
	$data['vk_billing_address_id']   = $billing_id;
	$data['vk_billing_same_as_shipping'] = $same_billing ? 1 : 0;
	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'vk_checkout_apply_saved_member_addresses', 20 );

/**
 * Store the selected saved-address IDs on the created order.
 *
 * @param int   $order_id Order ID.
 * @param array $data     Checkout data.
 */
function vk_store_checkout_address_ids( $order_id, $data ) {
	if ( ! is_user_logged_in() ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$order->update_meta_data( '_vk_shipping_address_id', sanitize_text_field( $data['vk_shipping_address_id'] ?? '' ) );
	$order->update_meta_data( '_vk_billing_address_id', sanitize_text_field( $data['vk_billing_address_id'] ?? '' ) );
	$order->update_meta_data( '_vk_billing_same_as_shipping', ! empty( $data['vk_billing_same_as_shipping'] ) ? '1' : '0' );
	$order->save();
}
add_action( 'woocommerce_checkout_update_order_meta', 'vk_store_checkout_address_ids', 20, 2 );

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

/**
 * Hide WooCommerce's internal VAT exemption state from the order Custom Fields box.
 *
 * The value is still stored on the order and read by WooCommerce; this only prevents
 * the internal key from appearing as an editable custom field in wp-admin.
 *
 * @param bool   $protected Whether the meta key is protected.
 * @param string $meta_key  Meta key.
 * @param string $meta_type Meta object type.
 * @return bool
 */
function vk_protect_internal_order_meta( $protected, $meta_key, $meta_type ) {
	if ( $protected ) {
		return $protected;
	}
	if ( 'is_vat_exempt' === $meta_key && in_array( $meta_type, array( 'order', 'post' ), true ) ) {
		return true;
	}
	return $protected;
}
add_filter( 'is_protected_meta', 'vk_protect_internal_order_meta', 10, 3 );

/**
 * Render applied coupons in the checkout coupon card.
 *
 * @return string
 */
function vk_checkout_applied_coupons_html() {
	$coupons = WC()->cart ? WC()->cart->get_coupons() : array();
	ob_start();
	?>
	<div class="vk-coupon-applied-wrap">
		<?php if ( $coupons ) : ?>
			<div class="co-coupon__applied">
				<?php foreach ( $coupons as $coupon_code => $coupon ) : ?>
					<?php
					$discount = (float) WC()->cart->get_coupon_discount_amount(
						$coupon_code,
						WC()->cart->display_prices_including_tax()
					);
					?>
					<div class="co-coupon__applied-item">
						<div class="co-coupon__applied-head">
							<span class="co-coupon__tag"><?php echo esc_html( $coupon->get_code() ); ?></span>
							<span class="co-coupon__saved">−<?php echo wp_kses_post( wc_price( $discount ) ); ?></span>
							<button class="co-coupon__remove js-vk-coupon-remove" type="button" data-coupon="<?php echo esc_attr( $coupon_code ); ?>">解除</button>
						</div>
						<p class="co-coupon__target">対象：全商品</p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Refresh the applied-coupon box after WooCommerce recalculates checkout.
 *
 * @param array $fragments Checkout fragments.
 * @return array
 */
function vk_checkout_coupon_fragments( $fragments ) {
	$fragments['.vk-coupon-applied-wrap'] = vk_checkout_applied_coupons_html();
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'vk_checkout_coupon_fragments' );
