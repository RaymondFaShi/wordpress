<?php
/**
 * Member account forms matching the supplied static templates.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render the account-details form.
 */
function vk_render_edit_account_content() {
	$user = wp_get_current_user();
	?>
	<form class="woocommerce-EditAccountForm edit-account" method="post">
			<div class="u-card" id="email">
				<div class="u-card__head"><div><p class="u-card__title">登録情報</p><p class="u-card__sub">お名前とメールアドレスを変更できます。</p></div></div>
				<div class="u-card__body">
					<div class="u-form-grid">
						<div class="afield"><label class="afield__label" for="account_last_name">姓<span class="afield__req">必須</span></label><input class="afield__input" id="account_last_name" name="account_last_name" type="text" value="<?php echo esc_attr( $user->last_name ); ?>" autocomplete="family-name" required></div>
						<div class="afield"><label class="afield__label" for="account_first_name">名<span class="afield__req">必須</span></label><input class="afield__input" id="account_first_name" name="account_first_name" type="text" value="<?php echo esc_attr( $user->first_name ); ?>" autocomplete="given-name" required></div>
						<div class="afield"><label class="afield__label" for="account_display_name">ニックネーム</label><input class="afield__input" id="account_display_name" name="account_display_name" type="text" value="<?php echo esc_attr( $user->display_name ); ?>" autocomplete="nickname"><p class="afield__hint">レビュー投稿時に表示される名前です。</p></div>
						<div class="afield"><label class="afield__label" for="account_email">メールアドレス<span class="afield__req">必須</span></label><input class="afield__input" id="account_email" name="account_email" type="email" value="<?php echo esc_attr( $user->user_email ); ?>" autocomplete="email" required></div>
					</div>
				</div>
			</div>
			<div class="u-card">
				<div class="u-card__head"><div><p class="u-card__title">パスワードの変更</p><p class="u-card__sub">変更する場合のみ入力してください。</p></div></div>
				<div class="u-card__body">
					<div class="u-form-grid">
						<div class="afield u-form-grid--full"><label class="afield__label" for="password_current">現在のパスワード</label><div class="afield__pw"><input class="afield__input" id="password_current" name="password_current" type="password" autocomplete="current-password"><button class="afield__pwbtn js-pw-toggle" type="button" data-target="password_current" aria-label="パスワードを表示"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
						<div class="afield"><label class="afield__label" for="password_1">新しいパスワード</label><div class="afield__pw"><input class="afield__input" id="password_1" name="password_1" type="password" autocomplete="new-password" placeholder="8文字以上"><button class="afield__pwbtn js-pw-toggle" type="button" data-target="password_1" aria-label="パスワードを表示"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
						<div class="afield"><label class="afield__label" for="password_2">新しいパスワード（確認）</label><div class="afield__pw"><input class="afield__input" id="password_2" name="password_2" type="password" autocomplete="new-password" placeholder="もう一度入力"><button class="afield__pwbtn js-pw-toggle" type="button" data-target="password_2" aria-label="パスワードを表示"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
					</div>
				</div>
				<div class="u-card__foot u-card__foot--split"><p class="auth-note mb-0">変更内容は「保存する」を押すと反映されます。</p><button class="btn btn-cta" type="submit" name="save_account_details" value="保存する">保存する</button></div>
				<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
				<input type="hidden" name="action" value="save_account_details">
			</div>
	</form>
	<?php
}

/**
 * Multiple member addresses for a type.
 *
 * @param int    $user_id User ID.
 * @param string $type shipping|billing.
 * @return array
 */
function vk_get_member_addresses( $user_id, $type ) {
	$key      = '_vk_' . ( 'billing' === $type ? 'billing' : 'shipping' ) . '_addresses';
	$addresses = get_user_meta( $user_id, $key, true );
	if ( ! is_array( $addresses ) ) {
		$customer = new WC_Customer( $user_id );
		$prefix   = 'billing' === $type ? 'billing' : 'shipping';
		$address  = array(
			'id'         => wp_generate_uuid4(),
			'label'      => '自宅',
			'first_name' => $customer->{'get_' . $prefix . '_first_name'}(),
			'last_name'  => $customer->{'get_' . $prefix . '_last_name'}(),
			'postcode'   => $customer->{'get_' . $prefix . '_postcode'}(),
			'state'      => $customer->{'get_' . $prefix . '_state'}(),
			'city'       => $customer->{'get_' . $prefix . '_city'}(),
			'address_1'  => $customer->{'get_' . $prefix . '_address_1'}(),
			'address_2'  => $customer->{'get_' . $prefix . '_address_2'}(),
			'phone'      => $customer->{'get_' . $prefix . '_phone'}(),
			'country'    => 'JP',
			'default'    => true,
		);
		$addresses = ( $address['address_1'] || $address['postcode'] ) ? array( $address ) : array();
		update_user_meta( $user_id, $key, $addresses );
	}
	return array_values( $addresses );
}

/**
 * Japanese prefecture label from a WooCommerce state code.
 *
 * @param string $state State code.
 * @return string
 */
function vk_japan_state_label( $state ) {
	$states = WC()->countries->get_states( 'JP' );
	return isset( $states[ $state ] ) ? $states[ $state ] : $state;
}

/**
 * Create or update a member address and return the current address list.
 *
 * @param int    $user_id User ID.
 * @param string $type shipping|billing.
 * @param array  $input Raw address fields.
 * @return array
 */
function vk_upsert_member_address( $user_id, $type, $input ) {
	$type      = 'billing' === $type ? 'billing' : 'shipping';
	$key       = '_vk_' . $type . '_addresses';
	$addresses = vk_get_member_addresses( $user_id, $type );
	$address_id = sanitize_text_field( $input['id'] ?? '' );
	$address    = array(
		'id'         => $address_id ?: wp_generate_uuid4(),
		'first_name' => sanitize_text_field( $input['first_name'] ?? '' ),
		'last_name'  => sanitize_text_field( $input['last_name'] ?? '' ),
		'postcode'   => sanitize_text_field( $input['postcode'] ?? '' ),
		'state'      => sanitize_text_field( $input['state'] ?? '' ),
		'city'       => sanitize_text_field( $input['city'] ?? '' ),
		'address_1'  => sanitize_text_field( $input['address_1'] ?? '' ),
		'address_2'  => sanitize_text_field( $input['address_2'] ?? '' ),
		'phone'      => sanitize_text_field( $input['phone'] ?? '' ),
		'country'    => 'JP',
		'default'    => ! empty( $input['default'] ) || ! $addresses,
	);
	$found = false;
	foreach ( $addresses as &$existing ) {
		if ( $existing['id'] === $address['id'] ) {
			$existing = $address;
			$found    = true;
		} elseif ( $address['default'] ) {
			$existing['default'] = false;
		}
	}
	unset( $existing );
	if ( ! $found ) {
		$addresses[] = $address;
	}
	$has_default = false;
	foreach ( $addresses as &$existing ) {
		$has_default = $has_default || ! empty( $existing['default'] );
	}
	unset( $existing );
	if ( ! $has_default && $addresses ) {
		$addresses[0]['default'] = true;
	}
	update_user_meta( $user_id, $key, array_values( $addresses ) );
	$saved_address = $address;
	foreach ( $addresses as $saved ) {
		if ( $saved['id'] === $address['id'] ) {
			$saved_address = $saved;
			break;
		}
	}

	$default = current(
		array_filter(
			$addresses,
			static function ( $item ) {
				return ! empty( $item['default'] );
			}
		)
	);
	if ( $default ) {
		$customer = new WC_Customer( $user_id );
		foreach ( array( 'first_name', 'last_name', 'postcode', 'state', 'city', 'address_1', 'address_2', 'phone' ) as $field ) {
			$customer->{'set_' . $type . '_' . $field}( $default[ $field ] ?? '' );
		}
		$customer->{'set_' . $type . '_country'}( 'JP' );
		$customer->save();
	}
	return array(
		'address'   => $saved_address,
		'addresses' => array_values( $addresses ),
	);
}

/**
 * Save addresses from the member address manager.
 */
function vk_handle_member_address_action() {
	if ( ! is_user_logged_in() || ! isset( $_POST['vk_address_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vk_address_nonce'] ) ), 'vk_member_address' ) ) {
		wp_die( '不正なリクエストです。', 'エラー', array( 'response' => 403 ) );
	}
	$user_id   = get_current_user_id();
	$type      = 'billing' === sanitize_key( wp_unslash( $_POST['address_type'] ?? '' ) ) ? 'billing' : 'shipping';
	$operation = sanitize_key( wp_unslash( $_POST['operation'] ?? 'save' ) );
	$key       = '_vk_' . $type . '_addresses';
	$addresses = vk_get_member_addresses( $user_id, $type );
	$address_id = sanitize_text_field( wp_unslash( $_POST['address_id'] ?? '' ) );

	if ( 'delete' === $operation ) {
		$addresses = array_values(
			array_filter(
				$addresses,
				static function ( $address ) use ( $address_id ) {
					return $address['id'] !== $address_id;
				}
			)
		);
	} elseif ( 'default' === $operation ) {
		foreach ( $addresses as &$address ) {
			$address['default'] = $address['id'] === $address_id;
		}
		unset( $address );
	} else {
		$address = array(
			'id'         => $address_id ?: wp_generate_uuid4(),
			'first_name' => sanitize_text_field( wp_unslash( $_POST['address_first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( wp_unslash( $_POST['address_last_name'] ?? '' ) ),
			'postcode'   => sanitize_text_field( wp_unslash( $_POST['address_postcode'] ?? '' ) ),
			'state'      => sanitize_text_field( wp_unslash( $_POST['address_state'] ?? '' ) ),
			'city'       => sanitize_text_field( wp_unslash( $_POST['address_city'] ?? '' ) ),
			'address_1'  => sanitize_text_field( wp_unslash( $_POST['address_line1'] ?? '' ) ),
			'address_2'  => sanitize_text_field( wp_unslash( $_POST['address_line2'] ?? '' ) ),
			'phone'      => sanitize_text_field( wp_unslash( $_POST['address_phone'] ?? '' ) ),
			'country'    => 'JP',
			'default'    => ! empty( $_POST['address_default'] ) || ! $addresses,
		);
		$found = false;
		foreach ( $addresses as &$existing ) {
			if ( $existing['id'] === $address['id'] ) {
				$existing = $address;
				$found    = true;
			} elseif ( $address['default'] ) {
				$existing['default'] = false;
			}
		}
		unset( $existing );
		if ( ! $found ) {
			$addresses[] = $address;
		}
	}

	$has_default = false;
	foreach ( $addresses as &$address ) {
		$has_default = $has_default || ! empty( $address['default'] );
	}
	unset( $address );
	if ( ! $has_default && $addresses ) {
		$addresses[0]['default'] = true;
	}
	update_user_meta( $user_id, $key, array_values( $addresses ) );

	$default = current(
		array_filter(
			$addresses,
			static function ( $address ) {
				return ! empty( $address['default'] );
			}
		)
	);
	if ( $default ) {
		$customer = new WC_Customer( $user_id );
		foreach ( array( 'first_name', 'last_name', 'postcode', 'state', 'city', 'address_1', 'address_2', 'phone' ) as $field ) {
			$customer->{'set_' . $type . '_' . $field}( $default[ $field ] ?? '' );
		}
		$customer->{'set_' . $type . '_country'}( 'JP' );
		$customer->save();
	}
	wp_safe_redirect( add_query_arg( 'address_updated', '1', vk_account_url( 'edit-address' ) ) );
	exit;
}
add_action( 'admin_post_vk_member_address_action', 'vk_handle_member_address_action' );

/**
 * Render multiple shipping and billing addresses.
 */
function vk_render_edit_address_content() {
	$user_id = get_current_user_id();
	$types   = array(
		'shipping' => array( '配送先住所', '商品をお届けする住所です。複数登録できます。' ),
		'billing'  => array( '請求先住所', '請求書や領収書に記載する住所です。' ),
	);
	if ( isset( $_GET['address_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wc_add_notice( '住所を更新しました。', 'success' );
		wc_print_notices();
	}
	foreach ( $types as $type => $labels ) :
		$addresses = vk_get_member_addresses( $user_id, $type );
		?>
		<div class="u-card user-address-card" data-address-type="<?php echo esc_attr( $type ); ?>">
			<div class="u-card__head"><div><p class="u-card__title"><?php echo esc_html( $labels[0] ); ?></p><p class="u-card__sub"><?php echo esc_html( $labels[1] ); ?></p></div><button class="btn btn-cta js-addr-add" type="button" data-type="<?php echo esc_attr( $type ); ?>">住所を追加</button></div>
			<div class="u-card__body">
				<div class="u-addr-list">
					<?php foreach ( $addresses as $address ) : ?>
						<article class="u-addr<?php echo ! empty( $address['default'] ) ? ' is-default' : ''; ?>" data-address="<?php echo esc_attr( wp_json_encode( $address ) ); ?>">
							<div class="u-addr__top"><span class="u-addr__name"><?php echo esc_html( trim( $address['last_name'] . ' ' . $address['first_name'] ) ); ?> 様</span><?php if ( ! empty( $address['default'] ) ) : ?><span class="u-addr__badge">既定</span><?php endif; ?></div>
							<p class="u-addr__lines"><span class="u-addr__zip">〒<?php echo esc_html( $address['postcode'] ); ?></span><span class="u-addr__pref"><?php echo esc_html( vk_japan_state_label( $address['state'] ) . $address['city'] . $address['address_1'] . ' ' . $address['address_2'] ); ?></span><span class="u-addr__tel">TEL：<?php echo esc_html( $address['phone'] ); ?></span></p>
							<div class="u-addr__actions">
								<button class="btn btn-cta js-addr-edit" type="button" data-type="<?php echo esc_attr( $type ); ?>" data-address="<?php echo esc_attr( wp_json_encode( $address ) ); ?>">変更</button>
								<?php if ( empty( $address['default'] ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="vk_member_address_action"><input type="hidden" name="operation" value="default"><input type="hidden" name="address_type" value="<?php echo esc_attr( $type ); ?>"><input type="hidden" name="address_id" value="<?php echo esc_attr( $address['id'] ); ?>"><?php wp_nonce_field( 'vk_member_address', 'vk_address_nonce' ); ?><button class="btn btn-line" type="submit">既定にする</button></form><?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="vk_member_address_action"><input type="hidden" name="operation" value="delete"><input type="hidden" name="address_type" value="<?php echo esc_attr( $type ); ?>"><input type="hidden" name="address_id" value="<?php echo esc_attr( $address['id'] ); ?>"><?php wp_nonce_field( 'vk_member_address', 'vk_address_nonce' ); ?><button class="btn btn-line" type="submit">削除</button></form>
							</div>
						</article>
					<?php endforeach; ?>
					<button class="u-addr u-addr--add js-addr-add" type="button" data-type="<?php echo esc_attr( $type ); ?>"><i class="bi bi-plus-lg u-addr__addicon" aria-hidden="true"></i><span class="u-addr__addtext"><?php echo esc_html( $labels[0] ); ?>を追加</span></button>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
	<p class="auth-note mt-3 mb-0">登録いただいた個人情報は、プライバシーポリシーに基づき適切に管理します。</p>

	<div class="modal fade addr-modal" id="addrModal" tabindex="-1" aria-labelledby="addrModalTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="addrManagerForm">
					<div class="modal-header"><p class="modal-title" id="addrModalTitle">住所を追加</p><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="閉じる"></button></div>
					<div class="modal-body">
						<input type="hidden" name="action" value="vk_member_address_action">
						<input type="hidden" name="operation" value="save">
						<input type="hidden" name="address_type" id="addrType" value="shipping">
						<input type="hidden" name="address_id" id="addrId" value="">
						<input type="hidden" name="address_country" value="JP">
						<div class="u-form-grid">
							<div class="afield"><label class="afield__label" for="addrLastName">姓<span class="afield__req">必須</span></label><input class="afield__input" id="addrLastName" name="address_last_name" type="text" autocomplete="family-name" placeholder="山田" required></div>
							<div class="afield"><label class="afield__label" for="addrFirstName">名<span class="afield__req">必須</span></label><input class="afield__input" id="addrFirstName" name="address_first_name" type="text" autocomplete="given-name" placeholder="太郎" required></div>
							<div class="afield"><label class="afield__label" for="addrPostcode">郵便番号</label><input class="afield__input" id="addrPostcode" name="address_postcode" type="text" inputmode="numeric" required></div>
							<div class="afield"><label class="afield__label" for="addrState">都道府県</label><select class="afield__select" id="addrState" name="address_state" required><?php foreach ( (array) WC()->countries->get_states( 'JP' ) as $state_code => $state_name ) : ?><option value="<?php echo esc_attr( $state_code ); ?>"><?php echo esc_html( $state_name ); ?></option><?php endforeach; ?></select></div>
							<div class="afield"><label class="afield__label" for="addrCity">市区町村</label><input class="afield__input" id="addrCity" name="address_city" type="text" required></div>
							<div class="afield u-form-grid--full"><label class="afield__label" for="addrLine1">住所</label><input class="afield__input" id="addrLine1" name="address_line1" type="text" required></div>
							<div class="afield u-form-grid--full"><label class="afield__label" for="addrLine2">建物名・部屋番号</label><input class="afield__input" id="addrLine2" name="address_line2" type="text"></div>
							<div class="afield u-form-grid--full"><label class="afield__label" for="addrPhone">電話番号</label><input class="afield__input" id="addrPhone" name="address_phone" type="tel"></div>
							<label class="auth-check u-form-grid--full" for="addrDefault"><input type="checkbox" id="addrDefault" name="address_default" value="1"><span>この住所を既定の住所にする</span></label>
						</div>
					</div>
					<div class="modal-footer"><button class="btn btn-cta" type="submit">住所を保存する</button></div>
					<?php wp_nonce_field( 'vk_member_address', 'vk_address_nonce' ); ?>
				</form>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render custom account endpoint content without WooCommerce's duplicate sidebar.
 *
 * @param string $endpoint Endpoint.
 */
function vk_render_account_endpoint_content( $endpoint ) {
	if ( 'edit-account' === $endpoint ) {
		vk_render_edit_account_content();
		return;
	}
	if ( 'edit-address' === $endpoint ) {
		vk_render_edit_address_content();
		return;
	}
	if ( in_array( $endpoint, array( 'favorites', 'reviews', 'newsletter' ), true ) ) {
		do_action( 'woocommerce_account_' . $endpoint . '_endpoint' );
		return;
	}
	echo do_shortcode( '[woocommerce_my_account]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
