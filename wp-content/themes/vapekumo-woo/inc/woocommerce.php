<?php
/**
 * WooCommerce integration.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read the configured WooCommerce flat-rate and free-shipping settings.
 *
 * @return array{threshold:float,fee:float}
 */
function vk_get_shipping_config() {
	static $config = null;
	if ( null !== $config ) {
		return $config;
	}
	$config = array(
		'threshold' => 3000.0,
		'fee'       => 600.0,
	);
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return $config;
	}
	$zones   = WC_Shipping_Zones::get_zones();
	$zones[] = WC_Shipping_Zones::get_zone( 0 );
	foreach ( $zones as $zone_data ) {
		$zone = $zone_data instanceof WC_Shipping_Zone ? $zone_data : WC_Shipping_Zones::get_zone( (int) ( $zone_data['zone_id'] ?? 0 ) );
		if ( ! $zone instanceof WC_Shipping_Zone ) {
			continue;
		}
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! is_object( $method ) || 'yes' !== ( $method->enabled ?? 'yes' ) ) {
				continue;
			}
			if ( 'free_shipping' === $method->id ) {
				$minimum = $method->get_instance_option( 'min_amount', '' );
				if ( is_numeric( $minimum ) && (float) $minimum > 0 ) {
					$config['threshold'] = (float) $minimum;
				}
			}
			if ( 'flat_rate' === $method->id ) {
				$cost = $method->get_instance_option( 'cost', '' );
				if ( is_numeric( $cost ) && (float) $cost >= 0 ) {
					$config['fee'] = (float) $cost;
				}
			}
		}
	}
	return $config;
}

/**
 * Formatted free-shipping minimum amount without currency symbol.
 *
 * @return string
 */
function vk_free_shipping_minimum_label() {
	$config = vk_get_shipping_config();
	return number_format_i18n( $config['threshold'] );
}

/**
 * Formatted standard shipping fee without currency symbol.
 *
 * @return string
 */
function vk_standard_shipping_fee_label() {
	$config = vk_get_shipping_config();
	return number_format_i18n( $config['fee'] );
}

/**
 * Use only the Cooder card gateway configured by the client.
 *
 * @param array $gateways Available gateways.
 * @return array
 */
function vk_only_cooder_gateway( $gateways ) {
	return isset( $gateways['cooder'] ) ? array( 'cooder' => $gateways['cooder'] ) : $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'vk_only_cooder_gateway', 99 );

/**
 * Replace shipping choices with one automatic rate based on the item subtotal.
 *
 * @param array $rates   Available rates.
 * @param array $package Shipping package.
 * @return array
 */
function vk_automatic_shipping_rates( $rates, $package ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $rates;
	}
	$config    = vk_get_shipping_config();
	$subtotal  = (float) WC()->cart->get_subtotal();
	$is_free   = $subtotal >= $config['threshold'];
	$rate_id   = $is_free ? 'vk_free_shipping' : 'vk_flat_shipping';
	$label     = $is_free ? '送料無料（' . vk_free_shipping_minimum_label() . '円以上）' : '通常配送';
	$rate      = new WC_Shipping_Rate( $rate_id, $label, $is_free ? 0 : $config['fee'], array(), $is_free ? 'free_shipping' : 'flat_rate' );
	return array( $rate_id => $rate );
}
add_filter( 'woocommerce_package_rates', 'vk_automatic_shipping_rates', 100, 2 );

/**
 * Remove the default Woo wrappers; the theme owns page layout.
 */
function vk_remove_woocommerce_wrappers() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'init', 'vk_remove_woocommerce_wrappers' );

/**
 * Add WooCommerce body class.
 *
 * @param array $classes Classes.
 * @return array
 */
function vk_woo_body_classes( $classes ) {
	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		$classes[] = 'vk-commerce';
	}
	return $classes;
}
add_filter( 'body_class', 'vk_woo_body_classes' );

/**
 * Keep cart count and mini cart current through Woo fragments.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function vk_cart_count_fragment( $fragments ) {
	$count = vk_get_cart_count();

	ob_start();
	?>
	<span class="cart-menu__badge"><?php echo esc_html( (string) $count ); ?></span>
	<?php
	$fragments['.cart-menu__badge'] = ob_get_clean();

	ob_start();
	vk_mini_cart();
	$fragments['.cart-menu__panel .cart-menu__inner'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'vk_cart_count_fragment' );

/**
 * Product loop columns and count.
 *
 * @return int
 */
function vk_loop_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'vk_loop_columns' );

/**
 * Products per page.
 *
 * @return int
 */
function vk_products_per_page() {
	return 20;
}
add_filter( 'loop_shop_per_page', 'vk_products_per_page' );

/**
 * Japanese add-to-cart labels.
 *
 * @return string
 */
function vk_single_add_to_cart_text() {
	return 'カートに入れる';
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'vk_single_add_to_cart_text' );

/**
 * Persist the selected cosmetic body colour as cart item data.
 *
 * @param array $cart_item_data Cart item data.
 * @return array
 */
function vk_add_body_color_to_cart( $cart_item_data ) {
	if ( ! empty( $_POST['vk_color'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cart_item_data['vk_color'] = sanitize_text_field( wp_unslash( $_POST['vk_color'] ) );
	}
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'vk_add_body_color_to_cart' );

/**
 * Display the selected body colour in cart and checkout summaries.
 *
 * @param array $item_data Existing display data.
 * @param array $cart_item Cart item.
 * @return array
 */
function vk_display_body_color( $item_data, $cart_item ) {
	if ( ! empty( $cart_item['vk_color'] ) ) {
		$item_data[] = array(
			'key'   => '本体カラー',
			'value' => wc_clean( $cart_item['vk_color'] ),
		);
	}
	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'vk_display_body_color', 10, 2 );

/**
 * Keep different cosmetic colours as distinct cart rows.
 *
 * @param string $cart_id Cart item hash.
 * @return string
 */
function vk_body_color_cart_id( $cart_id ) {
	if ( ! empty( $_POST['vk_color'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$cart_id .= md5( sanitize_text_field( wp_unslash( $_POST['vk_color'] ) ) );
	}
	return $cart_id;
}
add_filter( 'woocommerce_cart_id', 'vk_body_color_cart_id' );

/**
 * Japanese variation placeholder.
 *
 * @param array $args Dropdown arguments.
 * @return array
 */
function vk_variation_dropdown_args( $args ) {
	$args['show_option_none'] = 'フレーバーを選択';
	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'vk_variation_dropdown_args' );

/**
 * Remove default archive title and description because the theme renders them.
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );

/**
 * Truncate product tabs labels.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function vk_product_tabs( $tabs ) {
	unset( $tabs['additional_information'] );
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title']    = '商品の詳細';
		$tabs['description']['callback'] = 'vk_product_description_tab';
		$tabs['description']['priority'] = 10;
	}
	$tabs['faq'] = array(
		'title'    => 'よくある質問',
		'priority' => 15,
		'callback' => 'vk_product_faq_tab',
	);
	if ( isset( $tabs['reviews'] ) ) {
		$tabs['reviews']['title']    = 'レビュー';
		$tabs['reviews']['priority'] = 20;
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'vk_product_tabs', 20 );

/**
 * Product description tab content.
 */
function vk_product_description_tab() {
	global $product;
	$flavors = wc_get_product_terms( $product->get_id(), 'pa_flavor', array( 'fields' => 'names' ) );
	$brand   = vk_product_brand( $product );
	$description = $product->get_description();
	if ( mb_strlen( wp_strip_all_tags( $description ) ) < 260 ) {
		$description .= '<p>電源ボタンや複雑な設定は不要で、商品の説明に従ってすぐに使い始められます。カートリッジやコイルのメンテナンス条件は商品ごとに異なるため、付属の取扱説明書をご確認ください。</p><p>フレーバーや本体仕様は購入前にこのページで確認できます。在庫状況はカートに追加した時点で最新の情報が反映されます。</p>';
	}
	$puffs = preg_match( '/([0-9][0-9,]{2,6})\s*(?:puff|回)/iu', $product->get_name(), $puff_match ) ? $puff_match[1] : '—';
	?>
	<div class="editor-content pdp-desc__body">
		<?php echo wp_kses_post( wpautop( $description ) ); ?>
		<h3>メッシュコイルで味わいがクリア</h3>
		<p>フレーバーの輪郭を感じやすい仕様で、毎日の使用にも馴染む味わいです。開封後すぐに使用でき、初めての方でも扱いやすい設計です。</p>
		<ul><li>箱から出してすぐに使えるシンプル設計</li><li>20歳以上の方のみご購入いただけます</li><li>在庫と発送状況は購入時に最新情報を表示します</li></ul>
		<h3>用途に合わせて選べる</h3>
		<p>フレーバーごとの在庫を確認しながら、お好みの組み合わせを選べます。ご不明点は<a href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">ご利用ガイド</a>またはお問い合わせください。</p>
	</div>
	<section class="pdp-block pdp-block--spec" aria-labelledby="pdpSpecTitle">
		<h2 class="pdp-subtitle" id="pdpSpecTitle">商品スペック</h2>
		<table class="pdp-spec__table">
			<tbody>
				<tr><th scope="row">ブランド</th><td><?php echo esc_html( $brand ); ?></td></tr>
				<tr><th scope="row">商品番号</th><td><?php echo esc_html( $product->get_sku() ?: '—' ); ?></td></tr>
				<tr><th scope="row">タイプ</th><td><?php echo esc_html( $product->is_type( 'variable' ) ? 'フレーバー選択式' : '単品' ); ?></td></tr>
				<tr><th scope="row">吸入口数</th><td><?php echo esc_html( $puffs ); ?></td></tr>
				<tr><th scope="row">フレーバー</th><td><?php echo esc_html( $flavors ? implode( '／', $flavors ) : '商品ページでご確認ください' ); ?></td></tr>
				<tr><th scope="row">本体カラー</th><td>ブラック／ホワイト／ブルー／ピンク／ミントグリーン</td></tr>
				<tr><th scope="row">充電端子</th><td>USB Type-C</td></tr>
				<tr><th scope="row">セット内容</th><td>本体、取扱説明書</td></tr>
				<tr><th scope="row">在庫</th><td><?php echo esc_html( $product->is_in_stock() ? '在庫あり' : '在庫切れ' ); ?></td></tr>
				<tr><th scope="row">配送</th><td><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</td></tr>
				<!-- <tr><th scope="row">発送目安</th><td>15時までのご注文で当日発送</td></tr> -->
				<tr><th scope="row">対象年齢</th><td>20歳以上</td></tr>
			</tbody>
		</table>
		<p class="pdp-spec__note">※掲載の仕様は商品情報に基づく内容です。実際の商品と異なる場合があります。</p>
	</section>
	<?php
}

/**
 * Product FAQ tab content.
 */
function vk_product_faq_tab() {
	?>
	<h2 class="pdp-subtitle">よくある質問</h2>
	<div class="accordion pdp-faq" id="pdpFaq">
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody1" aria-expanded="true" aria-controls="faqBody1">届いた商品はすぐに使えますか。</button></h3><div class="accordion-collapse collapse show" id="faqBody1" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>はい。商品の取扱説明書に従って、そのままご使用いただけます。</p></div></div></div>
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody2" aria-expanded="false" aria-controls="faqBody2">フレーバーはどのように選べばよいですか。</button></h3><div class="accordion-collapse collapse" id="faqBody2" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>清涼感ならメンソール、甘い香りならフルーツ系が選びやすいです。商品ページの選択肢からお好みでお選びください。</p></div></div></div>
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody3" aria-expanded="false" aria-controls="faqBody3">充電はどのくらい持ちますか。</button></h3><div class="accordion-collapse collapse" id="faqBody3" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>使用頻度や製品によって異なります。充電式の場合は説明書に記載された方法で充電してください。</p></div></div></div>
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody4" aria-expanded="false" aria-controls="faqBody4">対象年齢を教えてください。</button></h3><div class="accordion-collapse collapse" id="faqBody4" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>20歳以上の方のみご購入いただけます。必要に応じて年齢確認を行います。</p></div></div></div>
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody5" aria-expanded="false" aria-controls="faqBody5">送料はかかりますか。</button></h3><div class="accordion-collapse collapse" id="faqBody5" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>通常配送は<?php echo esc_html( vk_standard_shipping_fee_label() ); ?>円です。<?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上のご注文は送料無料です。</p></div></div></div>
		<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqBody6" aria-expanded="false" aria-controls="faqBody6">返品や交換はできますか。</button></h3><div class="accordion-collapse collapse" id="faqBody6" data-bs-parent="#pdpFaq"><div class="accordion-body"><p>未開封かつ商品到着後7日以内の不良品について対応します。注文番号を添えてお問い合わせください。</p></div></div></div>
	</div>
	<?php
}

/**
 * Upsell heading.
 *
 * @return string
 */
function vk_related_products_heading() {
	return '関連商品';
}
add_filter( 'woocommerce_product_related_products_heading', 'vk_related_products_heading' );

/**
 * Render related products with the theme card.
 *
 * @param WC_Product $product Current product.
 */
function vk_render_related_products( $product ) {
	$related_ids = wc_get_related_products( $product->get_id(), 10, $product->get_upsell_ids() );
	if ( ! $related_ids ) {
		return;
	}
	?>
	<section class="section section--tint pdp-related" aria-labelledby="relatedTitle">
		<div class="container">
			<div class="section__head">
				<div><h2 class="section__title" id="relatedTitle">この商品を買った人はこちらも購入しています</h2><p class="section__sub">こちらもあわせてご覧ください。</p></div>
				<a class="section__more" href="<?php echo esc_url( vk_shop_url() ); ?>">商品一覧<i class="bi bi-chevron-right" aria-hidden="true"></i></a>
			</div>
			<div class="row row-cols-2 row-cols-lg-3 row-cols-xl-5 g-page">
				<?php foreach ( $related_ids as $related_id ) : ?>
					<div class="col"><?php vk_product_card( $related_id ); ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Rich product content below the WooCommerce tabs.
 *
 * @param WC_Product $product Product.
 */
function vk_render_product_details( $product ) {
	$flavors = wc_get_product_terms( $product->get_id(), 'pa_flavor', array( 'fields' => 'names' ) );
	$brand   = vk_product_brand( $product );
	?>
	<section class="pdp-features" aria-label="商品の特長">
		<div class="pdp-features__grid">
			<div class="pdp-feature"><i class="bi bi-stars" aria-hidden="true"></i><h3>味わいをしっかり設計</h3><p>フレーバーの輪郭を感じやすい仕様で、毎日の使用にも馴染む味わいです。</p></div>
			<div class="pdp-feature"><i class="bi bi-lightning-charge" aria-hidden="true"></i><h3>すぐに使える手軽さ</h3><p>開封後すぐに使用でき、初めての方でも扱いやすい設計です。</p></div>
			<div class="pdp-feature"><i class="bi bi-shield-check" aria-hidden="true"></i><h3>用途に合わせて選べる</h3><p>フレーバーごとの在庫を確認しながら、お好みの組み合わせを選べます。</p></div>
		</div>
	</section>
	<section class="pdp-block pdp-block--spec" aria-labelledby="pdpSpecTitle">
		<h2 class="pdp-subtitle" id="pdpSpecTitle">商品スペック</h2>
		<table class="pdp-spec__table">
			<tbody>
				<tr><th scope="row">ブランド</th><td><?php echo esc_html( $brand ); ?></td></tr>
				<tr><th scope="row">商品番号</th><td><?php echo esc_html( $product->get_sku() ?: '—' ); ?></td></tr>
				<tr><th scope="row">タイプ</th><td><?php echo esc_html( $product->is_type( 'variable' ) ? 'フレーバー選択式' : '単品' ); ?></td></tr>
				<tr><th scope="row">フレーバー</th><td><?php echo esc_html( $flavors ? implode( '、', $flavors ) : '商品ページでご確認ください' ); ?></td></tr>
				<tr><th scope="row">在庫</th><td><?php echo esc_html( $product->is_in_stock() ? '在庫あり' : '在庫切れ' ); ?></td></tr>
				<tr><th scope="row">配送</th><td><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</td></tr>
				<!-- <tr><th scope="row">発送目安</th><td>15時までのご注文で当日発送</td></tr> -->
				<tr><th scope="row">対象年齢</th><td>20歳以上</td></tr>
			</tbody>
		</table>
	</section>
	<section class="pdp-block pdp-faq" aria-labelledby="pdpFaqTitle">
		<h2 class="pdp-subtitle" id="pdpFaqTitle">よくある質問</h2>
		<div class="accordion" id="productFaq">
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq1" aria-expanded="false" aria-controls="productFaq1">注文後、どのくらいで発送されますか？</button></h3><div class="accordion-collapse collapse" id="productFaq1" data-bs-parent="#productFaq"><div class="accordion-body">15時までのご注文は当日発送を目安としています。在庫状況により翌営業日となる場合があります。</div></div></div>
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq2" aria-expanded="false" aria-controls="productFaq2">フレーバーは変更できますか？</button></h3><div class="accordion-collapse collapse" id="productFaq2" data-bs-parent="#productFaq"><div class="accordion-body">発送前であれば変更できる場合があります。注文番号を添えてお問い合わせください。</div></div></div>
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq3" aria-expanded="false" aria-controls="productFaq3">返品・交換はできますか？</button></h3><div class="accordion-collapse collapse" id="productFaq3" data-bs-parent="#productFaq"><div class="accordion-body">未開封かつ到着後7日以内の不良品について対応します。お問い合わせフォームよりご連絡ください。</div></div></div>
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq4" aria-expanded="false" aria-controls="productFaq4">年齢確認は必要ですか？</button></h3><div class="accordion-collapse collapse" id="productFaq4" data-bs-parent="#productFaq"><div class="accordion-body">20歳以上の方のみご購入いただけます。必要に応じて年齢確認を行います。</div></div></div>
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq5" aria-expanded="false" aria-controls="productFaq5">送料はいくらですか？</button></h3><div class="accordion-collapse collapse" id="productFaq5" data-bs-parent="#productFaq"><div class="accordion-body">通常配送は<?php echo esc_html( vk_standard_shipping_fee_label() ); ?>円です。商品小計が<?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上の場合、送料が無料になります。</div></div></div>
			<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#productFaq6" aria-expanded="false" aria-controls="productFaq6">在庫はありますか？</button></h3><div class="accordion-collapse collapse" id="productFaq6" data-bs-parent="#productFaq"><div class="accordion-body">在庫状況は商品ページとチェックアウト時に更新されます。品切れの場合は再入荷をお待ちください。</div></div></div>
		</div>
	</section>
	<?php
}

/**
 * Use the configured yen format.
 */
function vk_price_format( $format, $currency_pos ) {
	if ( 'JPY' === get_woocommerce_currency() ) {
		return '<span class="woocommerce-Price-currencySymbol">¥</span>%2$s';
	}
	return $format;
}
add_filter( 'woocommerce_price_format', 'vk_price_format', 10, 2 );

/**
 * Japanese checkout defaults.
 *
 * @param array $fields Fields.
 * @return array
 */
function vk_checkout_fields( $fields ) {
	$field_map = array(
		'first_name' => array(
			'label'       => '名',
			'placeholder' => '太郎',
			'required'    => true,
		),
		'last_name' => array(
			'label'       => '姓',
			'placeholder' => '山田',
			'required'    => true,
		),
		'phone' => array(
			'label'       => '電話番号',
			'placeholder' => '09012345678',
			'required'    => true,
		),
		'postcode' => array(
			'label'       => '郵便番号',
			'placeholder' => '150-0001',
			'required'    => true,
		),
		'state' => array(
			'label'       => '都道府県',
			'placeholder' => '都道府県を選択',
			'required'    => true,
		),
		'city' => array(
			'label'       => '市区町村（市・町・村）',
			'placeholder' => '渋谷区',
			'required'    => true,
		),
		'address_1' => array(
			'label'       => '詳細住所1（番地・建物名）',
			'placeholder' => '神宮前1-2-3 サンプルマンション',
			'required'    => true,
		),
		'address_2' => array(
			'label'       => '詳細住所2（部屋番号など・任意）',
			'placeholder' => '101号室',
			'required'    => false,
		),
	);

	foreach ( array( 'billing', 'shipping' ) as $section ) {
		foreach ( $field_map as $field_name => $field_config ) {
			$key = $section . '_' . $field_name;
			if ( isset( $fields[ $section ][ $key ] ) ) {
				$fields[ $section ][ $key ]['label']       = $field_config['label'];
				$fields[ $section ][ $key ]['placeholder'] = $field_config['placeholder'];
				$fields[ $section ][ $key ]['required']    = $field_config['required'];
			}
		}
	}

	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['label']       = 'メールアドレス';
		$fields['billing']['billing_email']['placeholder'] = 'you@example.com';
		$fields['billing']['billing_email']['required']    = true;
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'ご要望・備考';
		$fields['order']['order_comments']['placeholder'] = '配達時のご希望、領収書の宛名など';
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'vk_checkout_fields' );

/**
 * Require a shipping address for shippable checkout orders.
 *
 * @param bool $needs_shipping_address Current value.
 * @return bool
 */
function vk_require_checkout_shipping_address( $needs_shipping_address ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() && WC()->cart && WC()->cart->needs_shipping() ) {
		return true;
	}
	return $needs_shipping_address;
}
add_filter( 'woocommerce_cart_needs_shipping_address', 'vk_require_checkout_shipping_address' );

/**
 * Keep WooCommerce's shipping address visible on the custom checkout.
 *
 * @return bool
 */
function vk_checkout_ship_to_different_address() {
	return true;
}
add_filter( 'woocommerce_ship_to_different_address_checked', 'vk_checkout_ship_to_different_address' );

/**
 * Japanese checkout privacy text.
 *
 * @param string $text Privacy text.
 * @param string $type Context type.
 * @return string
 */
function vk_checkout_privacy_text( $text, $type ) {
	if ( 'checkout' === $type ) {
		return '個人情報は、ご注文の処理、配送、サポート、およびプライバシーポリシーに定める目的のために利用します。';
	}
	return $text;
}
add_filter( 'woocommerce_get_privacy_policy_text', 'vk_checkout_privacy_text', 10, 2 );

/**
 * Keep account navigation concise.
 *
 * @param array $items Menu items.
 * @return array
 */
function vk_account_menu_items( $items ) {
	unset( $items['downloads'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'vk_account_menu_items' );

/**
 * Add a favorites entry to the account navigation.
 *
 * @param array $items Menu items.
 * @return array
 */
function vk_account_favorites_link( $items ) {
	$items['favorites'] = 'お気に入り';
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'vk_account_favorites_link', 20 );

/**
 * Endpoints for the static-template account pages.
 */
function vk_register_favorites_endpoint() {
	foreach ( array( 'favorites', 'reviews', 'newsletter' ) as $endpoint ) {
		add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
	}
}
add_action( 'init', 'vk_register_favorites_endpoint' );

/**
 * Redirect the retired points page to the member dashboard.
 */
function vk_redirect_removed_account_points_page() {
	if ( is_admin() ) {
		return;
	}
	$request_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$account_path = trim( (string) wp_parse_url( vk_account_url(), PHP_URL_PATH ), '/' );
	if ( 'points' === $request_path || ( $account_path && $account_path . '/points' === $request_path ) ) {
		wp_safe_redirect( vk_account_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'vk_redirect_removed_account_points_page', 1 );

/**
 * Register the custom account endpoints with WooCommerce.
 *
 * @param array $query_vars Query vars.
 * @return array
 */
function vk_account_query_vars( $query_vars ) {
	foreach ( array( 'favorites', 'reviews', 'newsletter' ) as $endpoint ) {
		$query_vars[ $endpoint ] = $endpoint;
	}
	return $query_vars;
}
add_filter( 'woocommerce_get_query_vars', 'vk_account_query_vars' );

/**
 * Render account favorites.
 */
function vk_account_favorites_content() {
	echo do_shortcode( '[vapekumo_favorites]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$recommended = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => 4,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
	if ( ! $recommended ) {
		return;
	}
	?>
	<section class="u-recommend" aria-labelledby="favRecommendTitle">
		<div class="u-recommend__head"><div><p class="u-recommend__title" id="favRecommendTitle">おすすめ商品</p><p class="u-recommend__sub">お気に入りとあわせて人気のアイテムです。</p></div><a class="u-recommend__more" href="<?php echo esc_url( vk_shop_url() ); ?>">もっと見る</a></div>
		<div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-2 g-xl-3">
			<?php foreach ( $recommended as $product ) : ?>
				<?php
				$brand = vk_product_brand( $product );
				$name  = $product->get_name();
				if ( $brand && 0 === stripos( $name, $brand . ' ' ) ) {
					$name = substr( $name, strlen( $brand ) + 1 );
				}
				?>
				<div class="col"><article class="u-mini"><a class="u-mini__media" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo $product->get_image( 'vk-product-card', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a><div class="u-mini__body"><p class="u-mini__brand"><?php echo esc_html( $brand ); ?></p><p class="u-mini__name"><a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"><?php echo esc_html( $name ); ?></a></p><p class="u-mini__price"><?php echo wp_kses_post( $product->get_price_html() ); ?><span class="product-card__tax">（税込）</span></p></div></article></div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_account_favorites_endpoint', 'vk_account_favorites_content' );

/**
 * Add custom account links to WooCommerce's internal navigation.
 *
 * @param array $items Menu items.
 * @return array
 */
function vk_account_extra_links( $items ) {
	$items['reviews']    = '投稿したレビュー';
	$items['newsletter'] = 'メール配信設定';
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'vk_account_extra_links', 30 );

/**
 * Require age confirmation during account registration.
 *
 * @param string $username Username.
 * @param string $email Email.
 * @param array  $errors Registration errors.
 */
function vk_validate_registration_age( $username, $email, $errors ) {
	if ( empty( $_POST['age_confirmed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add( 'age_confirmation', '20歳以上であることへの同意が必要です。' );
	}
	if ( empty( $_POST['password_confirm'] ) || ! isset( $_POST['password'] ) || ! hash_equals( (string) wp_unslash( $_POST['password'] ), (string) wp_unslash( $_POST['password_confirm'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$errors->add( 'password_confirmation', '確認用パスワードが一致しません。' );
	}
}
add_action( 'woocommerce_register_post', 'vk_validate_registration_age', 10, 3 );

/**
 * Whether the current member has bought a product.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function vk_current_member_bought_product( $product_id ) {
	if ( ! is_user_logged_in() || ! function_exists( 'wc_customer_bought_product' ) ) {
		return false;
	}
	$user = wp_get_current_user();
	return wc_customer_bought_product( $user->user_email, $user->ID, $product_id );
}

/**
 * Only verified buyers may open or submit product reviews.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function vk_purchased_customer_reviews_only( $open, $post_id ) {
	if ( ! $open || 'product' !== get_post_type( $post_id ) ) {
		return $open;
	}
	return current_user_can( 'moderate_comments' ) || vk_current_member_bought_product( $post_id );
}
add_filter( 'comments_open', 'vk_purchased_customer_reviews_only', 20, 2 );

/**
 * Reject forged review submissions from customers without a purchase.
 *
 * @param array $comment_data Comment data.
 * @return array|WP_Error
 */
function vk_validate_review_purchase( $comment_data ) {
	$post_id = (int) ( $comment_data['comment_post_ID'] ?? 0 );
	if ( 'product' === get_post_type( $post_id ) && ! current_user_can( 'moderate_comments' ) && ! vk_current_member_bought_product( $post_id ) ) {
		return new WP_Error( 'vk_review_purchase_required', 'レビューは購入済みの会員のみ投稿できます。' );
	}
	return $comment_data;
}
add_filter( 'preprocess_comment', 'vk_validate_review_purchase' );

/**
 * Account review history.
 */
function vk_account_reviews_content() {
	$comments = get_comments(
		array(
			'user_id' => get_current_user_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => 20,
		)
	);
	?>
	<div class="u-card">
		<?php if ( $comments ) : ?>
			<div class="u-review-list">
				<?php foreach ( $comments as $comment ) : ?>
					<?php $rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true ); ?>
					<article class="u-review">
						<div class="u-review__head">
							<div>
								<p class="u-review__product"><a href="<?php echo esc_url( get_permalink( $comment->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $comment->comment_post_ID ) ); ?></a></p>
								<p class="u-review__date"><?php echo esc_html( get_comment_date( 'Y.m.d', $comment ) ); ?></p>
							</div>
							<span class="rating rating--<?php echo esc_attr( (string) ( $rating * 20 ) ); ?>" role="img" aria-label="評価 <?php echo esc_attr( (string) $rating ); ?>点"></span>
						</div>
						<p class="u-review__text"><?php echo esc_html( $comment->comment_content ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="u-empty"><i class="bi bi-star" aria-hidden="true"></i><p>投稿したレビューはまだありません。</p><a class="btn btn-line" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">注文履歴を見る</a></div>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'woocommerce_account_reviews_endpoint', 'vk_account_reviews_content' );

/**
 * Account newsletter preferences.
 */
function vk_account_newsletter_content() {
	$preferences = (array) get_user_meta( get_current_user_id(), '_vk_newsletter_preferences', true );
	$options     = array(
		'offers'  => 'クーポン・セール情報',
		'new'     => '新商品・再入荷情報',
		'content' => 'ブログ・ガイド更新情報',
	);
	?>
	<div class="u-card">
		<form data-newsletter-preferences>
			<div class="u-switch-list">
				<?php foreach ( $options as $key => $label ) : ?>
					<div class="u-switch"><div class="u-switch__info"><label class="u-switch__title" for="newsletter-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label><p class="u-switch__text"><?php echo esc_html( 'offers' === $key ? 'お得な情報を優先してお届けします。' : '必要なお知らせだけを受け取れます。' ); ?></p></div><div class="u-switch__ctrl"><div class="form-check form-switch"><input class="form-check-input" id="newsletter-<?php echo esc_attr( $key ); ?>" type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! isset( $preferences[ $key ] ) || ! empty( $preferences[ $key ] ) ); ?>></div></div></div>
				<?php endforeach; ?>
			</div>
			<div class="u-card__foot u-card__foot--split"><span class="u-switch__text">変更内容は保存後すぐに反映されます。</span><button class="btn btn-cta" type="submit">設定を保存する</button></div>
			<p class="u-pref-result" data-newsletter-preference-result role="status" aria-live="polite"></p>
		</form>
	</div>
	<p class="auth-note mt-3 mb-0">WooCommerce標準にはメール配信機能がないため、ここでは配信希望を会員情報として保存します。実際の配信にはMailchimp、Brevo、Klaviyo等のメールサービスとの連携が必要です。初期状態はすべて受信に設定されています。</p>
	<?php
}
add_action( 'woocommerce_account_newsletter_endpoint', 'vk_account_newsletter_content' );

/**
 * Render a custom account child page with the shared sidebar.
 *
 * @param string   $active   Active menu slug.
 * @param string   $title    Page title.
 * @param string   $lead     Page lead.
 * @param callable $callback Content callback.
 */
function vk_render_custom_account_page( $active, $title, $lead, $callback ) {
	get_header();
	?>
	<main id="main">
		<nav class="crumbs" aria-label="パンくずリスト">
			<div class="container">
				<ol class="crumbs__list">
					<li class="crumbs__item"><a class="crumbs__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a></li>
					<li class="crumbs__item"><a class="crumbs__link" href="<?php echo esc_url( vk_account_url() ); ?>">マイアカウント</a></li>
					<li class="crumbs__item"><span class="crumbs__current" aria-current="page"><?php echo esc_html( $title ); ?></span></li>
				</ol>
			</div>
		</nav>
		<section class="user-page" aria-labelledby="myPageTitle">
			<div class="container">
				<?php if ( ! is_user_logged_in() ) : ?>
					<div class="u-empty"><span class="u-empty__icon"><i class="bi bi-lock" aria-hidden="true"></i></span><p class="u-empty__title">ログインが必要です</p><p class="u-empty__text">このページを表示するには会員アカウントにログインしてください。</p><a class="btn btn-cta" href="<?php echo esc_url( vk_account_url() ); ?>">ログインする</a></div>
				<?php else : ?>
					<div class="row user-layout">
						<aside class="col-12 col-lg-3"><?php vk_account_sidebar( $active ); ?></aside>
						<div class="col-12 col-lg-9">
							<div class="user-head"><div><h1 class="user-head__title" id="myPageTitle"><?php echo esc_html( $title ); ?></h1><p class="user-head__lead"><?php echo esc_html( $lead ); ?></p></div><a class="user-head__more" href="<?php echo esc_url( vk_shop_url() ); ?>">買い物を続ける</a></div>
							<?php
							$content = call_user_func( $callback );
							if ( is_string( $content ) ) {
								echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</main>
	<?php
	get_footer();
}

/**
 * Resolve account extra pages even when WooCommerce's account rewrite handles the path.
 *
 * @param string $template Template path.
 * @return string
 */
function vk_account_extra_template( $template ) {
	$request_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$account_path = trim( (string) wp_parse_url( vk_account_url(), PHP_URL_PATH ), '/' );
	if ( ! $account_path || 0 !== strpos( $request_path, $account_path . '/' ) ) {
		return $template;
	}
	$slug = trim( substr( $request_path, strlen( $account_path ) + 1 ), '/' );
	if ( ! in_array( $slug, array( 'reviews', 'newsletter' ), true ) ) {
		return $template;
	}
	$custom = get_theme_file_path( 'page-' . $slug . '.php' );
	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'vk_account_extra_template', 99 );

/**
 * Document titles for account extra pages.
 *
 * @param string $title Document title.
 * @return string
 */
function vk_account_extra_title( $title ) {
	$request_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$account_path = trim( (string) wp_parse_url( vk_account_url(), PHP_URL_PATH ), '/' );
	$titles       = array(
		'reviews'    => '投稿したレビュー',
		'newsletter' => 'メール配信設定',
	);
	foreach ( $titles as $slug => $label ) {
		if ( $account_path && $request_path === $account_path . '/' . $slug ) {
			return $label . ' – ' . get_bloginfo( 'name' );
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'vk_account_extra_title', 20 );

/**
 * Account sidebar markup.
 *
 * @param string $active Active endpoint.
 */
function vk_account_sidebar( $active = '' ) {
	$current_user = wp_get_current_user();
	$display_name = vk_current_member_display_name();
	$initial      = mb_substr( $display_name, 0, 1 );
	$order_count  = (int) wc_get_customer_order_count( $current_user->ID );
	$favorites    = vk_get_user_favorite_count();
	$items        = array(
		''            => array( 'マイページ', 'bi-house-fill' ),
		'orders'      => array( '注文履歴', 'bi-box-seam-fill' ),
		'reviews'     => array( '投稿したレビュー', 'bi-star-fill' ),
		'favorites'   => array( 'お気に入り', 'bi-heart' ),
		'edit-address'=> array( 'アドレス帳', 'bi-geo-alt-fill' ),
		'edit-account'=> array( 'アカウント詳細', 'bi-person-fill' ),
		'newsletter'  => array( 'メール配信設定', 'bi-envelope' ),
	);
	?>
	<button class="user-sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#userSide" aria-controls="userSide">
		<i class="bi bi-list user-sidebar-toggle__icon" aria-hidden="true"></i>マイメニュー
		<span class="user-sidebar-toggle__name"><?php echo esc_html( $display_name ); ?></span>
		<i class="bi bi-chevron-right user-sidebar-toggle__chev" aria-hidden="true"></i>
	</button>
	<div class="offcanvas-lg offcanvas-start user-side" tabindex="-1" id="userSide" aria-labelledby="userSideLabel">
		<div class="offcanvas-header d-lg-none"><p class="mobile-nav__title" id="userSideLabel">マイメニュー</p><button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#userSide" aria-label="閉じる"></button></div>
		<div class="offcanvas-body"><div class="user-side__inner">
			<div class="user-profile">
				<span class="user-profile__avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
				<p class="user-profile__name"><?php echo esc_html( $display_name ); ?> 様</p>
				<p class="user-profile__mail"><?php echo esc_html( $current_user->user_email ); ?></p>
			</div>
			<nav class="user-nav" aria-label="マイページメニュー">
				<p class="user-nav__label">マイメニュー</p>
				<ul class="user-nav__list">
					<?php foreach ( $items as $endpoint => $item ) : ?>
						<li><a class="user-nav__link<?php echo $active === $endpoint ? ' is-active' : ''; ?>" href="<?php echo esc_url( vk_account_url( $endpoint ) ); ?>"<?php echo $active === $endpoint ? ' aria-current="page"' : ''; ?>><i class="bi <?php echo esc_attr( $item[1] ); ?>" aria-hidden="true"></i><?php echo esc_html( $item[0] ); ?>
							<?php if ( 'orders' === $endpoint && $order_count ) : ?><span class="user-nav__badge"><?php echo esc_html( (string) $order_count ); ?></span><?php endif; ?>
							<?php if ( 'favorites' === $endpoint && $favorites ) : ?><span class="user-nav__badge"><?php echo esc_html( (string) $favorites ); ?></span><?php endif; ?>
						</a></li>
					<?php endforeach; ?>
				</ul>
				<div class="user-nav__foot"><a class="user-nav__logout" href="<?php echo esc_url( wc_logout_url() ); ?>">ログアウト</a></div>
			</nav>
		</div></div>
	</div>
	<?php
}

/**
 * Shared shell for WooCommerce shortcode pages.
 */
function vk_commerce_page_shell() {
	get_header();
	?>
	<main id="main">
		<?php vk_breadcrumbs(); ?>
		<section class="commerce-page">
			<div class="container">
				<header class="page-head">
					<div>
						<h1 class="page-head__title">
							<?php if ( is_cart() ) : ?>
								ショッピングカート<span class="cart__count">（<b><?php echo esc_html( (string) vk_get_cart_count() ); ?></b>点）</span>
							<?php else : ?>
								<?php echo esc_html( get_the_title() ); ?>
							<?php endif; ?>
						</h1>
						<?php if ( is_cart() ) : ?>
							<p class="page-head__lead">ご注文内容をご確認ください。数量の変更や商品の削除ができます。</p>
						<?php elseif ( is_checkout() && ! is_order_received_page() ) : ?>
							<p class="page-head__lead">配送先とお支払い方法をご入力ください。</p>
						<?php elseif ( is_account_page() ) : ?>
							<p class="page-head__lead">ご注文履歴、アカウント情報、お気に入りを管理できます。</p>
						<?php endif; ?>
					</div>
					<?php if ( is_cart() || ( is_checkout() && ! is_order_received_page() ) ) : ?>
						<a class="page-head__side cart__back" href="<?php echo esc_url( is_checkout() ? wc_get_cart_url() : vk_shop_url() ); ?>">
							<?php echo esc_html( is_checkout() ? 'カートに戻る' : '買い物を続ける' ); ?>
						</a>
					<?php endif; ?>
				</header>
				<div class="commerce-page__content">
					<?php
					while ( have_posts() ) :
						the_post();
						the_content();
					endwhile;
					?>
				</div>
			</div>
		</section>
	</main>
	<?php
	get_footer();
}

/**
 * Flush rewrite rules once after theme activation.
 */
function vk_flush_rewrite_once() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'vk_flush_rewrite_once' );
