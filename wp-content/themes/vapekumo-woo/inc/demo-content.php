<?php
/**
 * Idempotent starter-store importer.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seed categories, products, pages, posts and navigation once.
 */
function vk_maybe_seed_demo_content() {
	if ( get_option( 'vk_demo_seed_done' ) || ! class_exists( 'WC_Product_Variable' ) ) {
		return;
	}
	if ( wp_installing() ) {
		return;
	}

	update_option( 'vk_demo_seed_started', time(), false );
	vk_seed_store_settings();
	$categories = vk_seed_product_categories();
	$attribute  = vk_seed_flavor_attribute();
	$images     = vk_seed_demo_images();
	vk_seed_products( $categories, $attribute, $images );
	if ( function_exists( 'vk_seed_real_catalog' ) ) {
		$catalog_seed = vk_seed_real_catalog();
		update_option( 'vk_real_catalog_seed', $catalog_seed, false );
	}
	if ( function_exists( 'vk_seed_member_demo_data' ) ) {
		update_option( 'vk_member_demo_seed', vk_seed_member_demo_data(), false );
	}
	vk_seed_pages_and_posts( $images );
	vk_seed_navigation();
	flush_rewrite_rules();
	update_option( 'vk_demo_seed_done', 1, false );
	delete_option( 'vk_demo_seed_started' );
}
add_action( 'init', 'vk_maybe_seed_demo_content', 40 );

/**
 * Store defaults.
 */
function vk_seed_store_settings() {
	update_option( 'blogname', '7s7' );
	update_option( 'blogdescription', '正規品の電子タバコ・ベイプ専門通販' );
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'woocommerce_currency', 'JPY' );
	update_option( 'woocommerce_price_num_decimals', 0 );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
	update_option( 'woocommerce_enable_reviews', 'yes' );
	update_option( 'woocommerce_review_rating_required', 'no' );
	update_option( 'woocommerce_cart_redirect_after_add', 'no' );
	update_option( 'woocommerce_enable_ajax_add_to_cart', 'yes' );
	update_option( 'woocommerce_manage_stock', 'yes' );
	update_option( 'woocommerce_default_country', 'JP' );
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'woocommerce_store_pages_only', 'no' );
	update_option( 'woocommerce_private_link', 'no' );
	update_option(
		'woocommerce_cod_settings',
		array(
			'enabled'      => 'no',
			'title'        => '代金引換',
			'description'  => '商品お受け取り時に現金でお支払いください。',
			'instructions' => '配達員に代金をお支払いください。',
		)
	);
	update_option(
		'woocommerce_bacs_settings',
		array(
			'enabled'      => 'no',
			'title'        => '銀行振込',
			'description'  => 'ご注文後に銀行口座をご案内します。',
			'instructions' => 'お振込み確認後に商品を発送します。',
		)
	);
	update_option(
		'woocommerce_cooder_settings',
		array(
			'enabled'     => 'yes',
			'title'       => 'クレジットカード',
			'description' => 'Visa / Mastercard / JCB / AMEX に対応しています。',
			'debug'       => 'no',
		)
	);

	if ( class_exists( 'WC_Shipping_Zones' ) && ! get_option( 'vk_shipping_seeded' ) ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( '日本全国' );
		$zone->add_location( 'JP', 'country' );
		$zone->save();

		$flat_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $flat_id . '_settings',
			array(
				'title'      => '通常配送',
				'tax_status' => 'taxable',
				'cost'       => '600',
			)
		);
		$free_id = $zone->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $free_id . '_settings',
			array(
				'title'      => '3,000円以上送料無料',
				'requires'   => 'min_amount',
				'min_amount' => '3000',
			)
		);
		update_option( 'vk_shipping_seeded', 1, false );
	}
}

/**
 * Import a theme image into the media library once.
 *
 * @param string $relative Relative theme asset path.
 * @param string $title Attachment title.
 * @return int
 */
function vk_seed_attachment( $relative, $title ) {
	$key  = 'vk_attachment_' . md5( $relative );
	$seen = (int) get_option( $key );
	if ( $seen && get_post( $seen ) ) {
		return $seen;
	}

	$source = VK_THEME_DIR . '/assets/' . ltrim( $relative, '/' );
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	$contents = file_get_contents( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$upload   = wp_upload_bits( wp_unique_filename( wp_get_upload_dir()['path'], basename( $source ) ), null, $contents );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype   = wp_check_filetype( $upload['file'] );
	$attachment = array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => sanitize_text_field( $title ),
		'post_name'      => 'vk-media-' . sanitize_title( pathinfo( basename( $source ), PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_option( $key, $attachment_id, false );
	return (int) $attachment_id;
}

/**
 * Import images reused by products and content.
 *
 * @return array
 */
function vk_seed_demo_images() {
	$paths = array(
		'product-01' => array( 'images/product/product-01.jpg', 'ELFBAR BC5000' ),
		'product-02' => array( 'images/product/product-02.jpg', 'Bang King 12000' ),
		'product-03' => array( 'images/product/product-03.jpg', 'Vaporesso XROS 4' ),
		'product-04' => array( 'images/product/product-04.jpg', 'Uwell Caliburn G3' ),
		'product-05' => array( 'images/product/product-05.jpg', 'BubbleMon 6000' ),
		'product-06' => array( 'images/product/product-06.jpg', 'Waspe 3000' ),
		'blog-01'    => array( 'images/blog/blog-01.jpg', '使い捨てベイプの選び方' ),
		'blog-02'    => array( 'images/blog/blog-02.jpg', 'リキッドの特徴' ),
		'banner-01'  => array( 'images/banner/banner-01.jpg', '今月のセール特集' ),
	);
	$images = array();
	foreach ( $paths as $key => $data ) {
		$images[ $key ] = vk_seed_attachment( $data[0], $data[1] );
	}
	return $images;
}

/**
 * Create category hierarchy.
 *
 * @return array
 */
function vk_seed_product_categories() {
	$definitions = array(
		'disposable' => array( '使い捨てベイプ', '充電もコイル交換も不要ですぐ使えるベイプ。', 'images/category/cat-disposable.jpg' ),
		'nicotine'   => array( 'ニコチン入りベイプ', '好みに合わせて選べる豊富なラインナップ。', 'images/category/cat-nicotine.jpg' ),
		'fruit'      => array( 'フルーツ味ベイプ', '定番から個性派まで選べるフレーバー。', 'images/category/cat-fruit.jpg' ),
		'shisha'     => array( 'シーシャ', '本体・フレーバー・アクセサリー。', 'images/category/cat-shisha.jpg' ),
		'starter'    => array( 'スターターキット', '初めての方にも扱いやすいデバイスセット。', 'images/category/cat-starter.jpg' ),
		'atomizer'   => array( 'アトマイザー', '買い替え・カスタマイズ用パーツ。', 'images/category/cat-atomizer.jpg' ),
		'liquid'     => array( 'リキッド', 'ノンニコチンを含む各種リキッド。', 'images/category/cat-fruit.jpg' ),
		'heating'    => array( '加熱式タバコ', '加熱式デバイスと関連アクセサリー。', 'images/category/cat-nicotine.jpg' ),
		'accessories'=> array( 'アクセサリー', 'コイル、ケーブル、収納用品などのアクセサリー。', 'images/category/cat-atomizer.jpg' ),
	);
	$categories = array();
	foreach ( $definitions as $slug => $data ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			$result = wp_insert_term( $data[0], 'product_cat', array( 'slug' => $slug, 'description' => $data[1] ) );
			if ( is_wp_error( $result ) ) {
				continue;
			}
			$term = get_term( $result['term_id'], 'product_cat' );
		}
		$image_id = vk_seed_attachment( $data[2], $data[0] );
		if ( $image_id ) {
			update_term_meta( $term->term_id, 'thumbnail_id', $image_id );
		}
		$categories[ $slug ] = (int) $term->term_id;
	}
	return $categories;
}

/**
 * Create the Flavor attribute and values.
 *
 * @return array
 */
function vk_seed_flavor_attribute() {
	$slug         = 'flavor';
	$attribute_id = (int) wc_attribute_taxonomy_id_by_name( $slug );
	if ( ! $attribute_id ) {
		$attribute_id = (int) wc_create_attribute(
			array(
				'name'         => 'フレーバー',
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		delete_transient( 'wc_attribute_taxonomies' );
	}
	register_taxonomy(
		'pa_' . $slug,
		array( 'product' ),
		array(
			'hierarchical' => false,
			'label'        => 'フレーバー',
			'public'       => false,
			'show_ui'      => false,
			'query_var'    => false,
			'rewrite'      => false,
		)
	);

	$values = array(
		'メンソール'         => 'menthol',
		'マンゴー'           => 'mango',
		'ブルーベリー'       => 'blueberry',
		'ピーチ'             => 'peach',
		'グレープ'           => 'grape',
		'ダブルアップル'     => 'double-apple',
		'レモンソーダ'       => 'lemon-soda',
		'ストロベリーアイス' => 'strawberry-ice',
	);
	$terms = array();
	foreach ( $values as $name => $term_slug ) {
		$term = get_term_by( 'slug', $term_slug, 'pa_flavor' );
		if ( ! $term ) {
			$result = wp_insert_term( $name, 'pa_flavor', array( 'slug' => $term_slug ) );
			if ( is_wp_error( $result ) ) {
				continue;
			}
			$term = get_term( $result['term_id'], 'pa_flavor' );
		}
		$terms[ $term_slug ] = array( 'id' => (int) $term->term_id, 'name' => $name );
	}
	return array( 'id' => $attribute_id, 'slug' => 'pa_' . $slug, 'terms' => $terms );
}

/**
 * Create the starter product catalog.
 *
 * @param array $categories Categories.
 * @param array $attribute Attribute details.
 * @param array $images Images.
 */
function vk_seed_products( $categories, $attribute, $images ) {
	$catalog = array(
		array( 'elfbar-bc5000', 'ELFBAR BC5000 使い捨てベイプ 5000puff', 'ELFBAR', 2480, 2980, 'product-01', array( 'disposable', 'nicotine', 'fruit' ), array( 'menthol', 'mango', 'blueberry', 'peach', 'grape' ), '最大5000回の吸引が可能なメッシュコイル採用モデル。箱から出してすぐに使えます。', 120, true ),
		array( 'bang-king-12000', 'Bang King 12000 使い捨てベイプ 12000puff', 'Bang King', 3280, 3680, 'product-02', array( 'disposable', 'nicotine', 'fruit' ), array( 'mango', 'grape', 'peach' ), '大容量12,000回クラスの使い捨てベイプ。長時間の外出にも便利です。', 96, true ),
		array( 'vaporesso-xros4', 'Vaporesso XROS 4 スターターキット', 'Vaporesso', 4980, 0, 'product-03', array( 'starter', 'atomizer' ), array( 'menthol', 'blueberry' ), '薄型ボディと調整可能な吸い応えを備えたスターターキット。予備コイル付き。', 82, true ),
		array( 'uwell-caliburn-g3', 'Uwell Caliburn G3 スターターキット', 'Uwell', 5480, 5980, 'product-04', array( 'starter', 'atomizer' ), array( 'menthol', 'mango' ), '扱いやすさと安定した味わいを両立した交換コイル式デバイスです。', 71, true ),
		array( 'bubblemon-6000', 'BubbleMon 6000 使い捨てベイプ 6000puff', 'BubbleMon', 2180, 2480, 'product-05', array( 'disposable', 'nicotine', 'fruit' ), array( 'grape', 'blueberry', 'peach' ), '果実感のあるフレーバーを楽しめるコンパクトな6000回クラス。', 64, false ),
		array( 'waspe-3000', 'Waspe 3000 使い捨てベイプ 3000puff', 'Waspe', 1480, 2180, 'product-06', array( 'disposable', 'fruit' ), array( 'peach', 'mango', 'grape' ), '軽量で持ち歩きやすく、初めての方にも選びやすいモデルです。', 58, false ),
		array( 'al-fakher-50g', 'Al Fakher シーシャフレーバー 50g', 'Al Fakher', 1280, 1480, 'product-05', array( 'shisha', 'fruit' ), array( 'double-apple', 'menthol', 'grape' ), '香り立ちのよい定番シーシャフレーバー。50g入り。', 43, false ),
		array( 'okso-8000', 'OKSO 8000 使い捨てベイプ 8000puff', 'OKSO', 3080, 0, 'product-02', array( 'disposable', 'nicotine', 'fruit' ), array( 'lemon-soda', 'blueberry', 'strawberry-ice' ), 'すっきりした味わいと8,000回クラスの容量を備えたモデル。', 39, false ),
		array( 'elfbar-te6000', 'ELFBAR TE6000 使い捨てベイプ 6000puff', 'ELFBAR', 2880, 0, 'product-03', array( 'disposable', 'nicotine', 'fruit' ), array( 'strawberry-ice', 'menthol', 'grape' ), '鮮明なフレーバーと扱いやすい形状が特徴の使い捨てベイプ。', 35, false ),
		array( 'vaporesso-luxe-x2', 'Vaporesso LUXE X2 アトマイザー', 'Vaporesso', 3980, 0, 'product-04', array( 'atomizer', 'starter' ), array( 'menthol', 'mango' ), '交換用タンクに対応した、メンテナンスしやすいアトマイザー。', 29, false ),
	);

	$product_category_ids = array_values( $categories );
	wp_insert_term( 'セール', 'product_cat', array( 'slug' => 'sale' ) );

	foreach ( $catalog as $index => $item ) {
		list( $slug, $name, $brand, $price, $compare_price, $image_key, $category_slugs, $flavor_slugs, $description, $sales, $is_featured ) = $item;
		$existing = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $existing ) {
			$product_id = (int) $existing->ID;
		} else {
			$product = new WC_Product_Variable();
			$product->set_name( $name );
			$product->set_slug( $slug );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_description( '<p>' . $description . '</p><h2>商品について</h2><p>20歳以上の方のみご購入いただけます。ご不明点はお問い合わせください。</p>' );
			$product->set_short_description( $description );
			$product->set_sku( 'VK-' . strtoupper( $slug ) );
			$product->set_regular_price( (string) ( $compare_price ?: $price ) );
			$product->set_sale_price( $compare_price ? (string) $price : '' );
			$product->set_featured( $is_featured );
			$product->set_menu_order( $index );

			$options = array();
			foreach ( $flavor_slugs as $flavor_slug ) {
				if ( isset( $attribute['terms'][ $flavor_slug ] ) ) {
					$options[] = $attribute['terms'][ $flavor_slug ]['id'];
				}
			}
			if ( $options ) {
				$taxonomy = new WC_Product_Attribute();
				$taxonomy->set_id( $attribute['id'] );
				$taxonomy->set_name( $attribute['slug'] );
				$taxonomy->set_options( $options );
				$taxonomy->set_visible( true );
				$taxonomy->set_variation( true );
				$product->set_attributes( array( $taxonomy ) );
			}
			$product_id = $product->save();
			if ( ! $product_id ) {
				continue;
			}

			foreach ( $flavor_slugs as $flavor_slug ) {
				if ( ! isset( $attribute['terms'][ $flavor_slug ] ) ) {
					continue;
				}
				$variation = new WC_Product_Variation();
				$variation->set_parent_id( $product_id );
				$variation->set_status( 'publish' );
				$variation->set_sku( 'VK-' . strtoupper( $slug ) . '-' . strtoupper( $flavor_slug ) );
				$variation->set_regular_price( (string) ( $compare_price ?: $price ) );
				$variation->set_sale_price( $compare_price ? (string) $price : '' );
				$variation->set_manage_stock( true );
				$variation->set_stock_quantity( 25 + ( $index * 3 ) );
				$variation->set_stock_status( 'instock' );
				$variation->set_attributes( array( $attribute['slug'] => $flavor_slug ) );
				$variation->save();
			}
			WC_Product_Variable::sync( $product_id );
		}

		$cat_ids = array();
		foreach ( $category_slugs as $category_slug ) {
			if ( isset( $categories[ $category_slug ] ) ) {
				$cat_ids[] = $categories[ $category_slug ];
			}
		}
		if ( $compare_price ) {
			$sale = get_term_by( 'slug', 'sale', 'product_cat' );
			if ( $sale ) {
				$cat_ids[] = (int) $sale->term_id;
			}
		}
		wp_set_object_terms( $product_id, $cat_ids ?: $product_category_ids, 'product_cat' );
		update_post_meta( $product_id, '_vk_brand', $brand );
		update_post_meta( $product_id, '_featured', $is_featured ? 'yes' : 'no' );
		update_post_meta( $product_id, 'total_sales', (string) $sales );
		if ( ! empty( $images[ $image_key ] ) ) {
			set_post_thumbnail( $product_id, $images[ $image_key ] );
		}
		$gallery_ids = array();
		foreach ( array( 'product-01', 'product-02', 'product-03', 'product-04', 'product-05', 'product-06' ) as $gallery_key ) {
			if ( ! empty( $images[ $gallery_key ] ) ) {
				$gallery_ids[] = (int) $images[ $gallery_key ];
			}
		}
		if ( $gallery_ids ) {
			update_post_meta( $product_id, '_product_image_gallery', implode( ',', array_unique( $gallery_ids ) ) );
		}
	}
}

/**
 * Create the content pages and initial editorial posts.
 *
 * @param array $images Images.
 */
function vk_seed_pages_and_posts( $images ) {
	$pages = array(
		'guide'     => array( 'ご利用ガイド', '<h1>ご利用ガイド</h1><h2>送料・配送</h2><p>3,000円以上のご注文は送料無料です。15時までのご注文は当日発送を目安にしています。</p><h2>年齢確認</h2><p>当店は20歳以上の方を対象としています。年齢確認にご協力ください。</p><h2>返品・交換</h2><p>未開封かつ商品到着後7日以内の不良品について、お問い合わせフォームよりご連絡ください。</p>' ),
		'brand'     => array( 'ブランド一覧', '<h1>ブランド一覧</h1><p>ELFBAR、Bang King、Uwell、Vaporesso、Al Fakherなどの正規品を取り扱っています。</p><h2>人気ブランド</h2><ul><li><a href="/product-category/disposable/">使い捨てベイプ</a></li><li><a href="/product-category/starter/">スターターキット</a></li><li><a href="/product-category/shisha/">シーシャ</a></li></ul>' ),
		'contact'   => array( 'お問い合わせ', '<h1>お問い合わせ</h1><p>商品やご注文についてのご質問を受け付けています。通常2営業日以内に返信いたします。</p><p>お名前、メールアドレス、ご注文番号、お問い合わせ内容を記載してください。</p>' ),
		'favorites' => array( 'お気に入り', '<h1>お気に入り</h1><p>気になる商品を保存して、あとからまとめて確認できます。</p>[vapekumo_favorites]' ),
		'privacy'   => array( 'プライバシーポリシー', '<h1>プライバシーポリシー</h1><p>当店は、注文処理、配送、サポートおよび法令上の義務のために必要な範囲で個人情報を取り扱います。</p><h2>取得する情報</h2><p>氏名、連絡先、配送先、注文内容、決済状況などの情報を取得します。</p><h2>お問い合わせ</h2><p>個人情報の確認・訂正・削除について、お問い合わせページよりご連絡ください。</p>' ),
		'blog'      => array( 'ブログ', '' ),
		'sitemap'   => array( 'サイトマップ', '' ),
	);
	$page_ids = array();
	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug );
		if ( ! $existing ) {
			$same_slug_pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'name'           => $slug,
				)
			);
			$existing = $same_slug_pages ? $same_slug_pages[0] : null;
		}
		$page_ids[ $slug ] = $existing ? (int) $existing->ID : (int) wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page[0],
				'post_name'    => $slug,
				'post_content' => $page[1],
			)
		);
	}
	update_option( 'wp_page_for_privacy_policy', $page_ids['privacy'] );
	update_option( 'page_for_posts', $page_ids['blog'] );
	update_option( 'show_on_front', 'page' );

	$woocommerce_pages = array(
		'woocommerce_shop_page_id'      => array( 'オンラインストア', '' ),
		'woocommerce_cart_page_id'      => array( 'ショッピングカート', '[woocommerce_cart]' ),
		'woocommerce_checkout_page_id'  => array( 'ご注文手続き', '[woocommerce_checkout]' ),
		'woocommerce_myaccount_page_id' => array( 'マイアカウント', '[woocommerce_my_account]' ),
	);
	foreach ( $woocommerce_pages as $option_key => $page_data ) {
		$page_id = (int) get_option( $option_key );
		if ( $page_id ) {
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_title'   => $page_data[0],
					'post_content' => $page_data[1],
				)
			);
		}
	}

	$home_page = get_page_by_path( 'home' );
	$home_id   = $home_page ? (int) $home_page->ID : (int) wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'ホーム',
			'post_name'   => 'home',
		)
	);
	update_option( 'page_on_front', $home_id );
	$admin_user = get_user_by( 'login', 'wpshop_admin' );
	if ( $admin_user ) {
	}

	$category_id = term_exists( 'お知らせ', 'category' );
	if ( ! $category_id ) {
		$category_id = wp_insert_term( 'お知らせ', 'category' );
	}
	$category_id = is_array( $category_id ) ? (int) $category_id['term_id'] : (int) $category_id;
	$posts       = array(
		array( 'disposable-vape-guide', '使い捨てベイプの選び方｜最初に確認したい5つのポイント', '吸入口数、フレーバー、本体サイズ、バッテリー表示、購入時の年齢確認を順に確認しましょう。用途に合う一台を選ぶことで、日常使いがより快適になります。', 'blog-01' ),
		array( 'non-nicotine-liquid', 'ノンニコチンリキッドとは？味わいと特徴をわかりやすく解説', 'ノンニコチンリキッドはニコチンを含まないリキッドです。フレーバー選びとデバイスとの相性を確認し、20歳以上の方がご自身に合う製品を選びましょう。', 'blog-02' ),
		array( 'coil-maintenance', 'コイル交換のタイミングと正しい手順｜長持ちさせるコツ', '味の変化や独特な焦げた香りを感じたときが交換の目安です。電源を切り、取扱説明書に従ってゆっくり交換してください。', 'product-03' ),
		array( 'starter-kit-guide', 'スターターキットの選び方｜本体とリキッドの相性を確認するポイント', '初めてのデバイスを選ぶときは、本体の操作しやすさ、対応するコイル、リキッドとの相性を確認することが大切です。', 'product-04' ),
		array( 'flavor-selection', 'フレーバーの選び方｜味の系統を整理して好みを見つける', 'フルーツ系、メンソール系、スイーツ系の特徴を整理し、日常的に楽しみやすい味わいを選びましょう。', 'product-05' ),
	);
	$article_bodies = array(
		'disposable-vape-guide' => '<p>使い捨てベイプは手軽に使える反面、種類が多くどれを選べばよいか迷いがちです。購入前に確認しておきたい5つのポイントを順番に整理します。</p><h2>1. 吸引回数（パフ数）で選ぶ</h2><p>製品名に記載されている数字は、おおよその吸引回数を表します。外出先で少しだけ使いたい場合は3,000回前後、毎日持ち歩く場合は5,000〜6,000回、交換の手間を減らしたい場合は8,000回以上が目安です。</p><h2>2. フレーバーの系統で選ぶ</h2><p>味わいは大きく「フルーツ系」「メンソール系」「スイーツ系」に分かれます。系統ごとの特徴を押さえておくと選びやすくなります。</p><h2>3. 本体サイズと携帯性で選ぶ</h2><p>ペン型は持ち運びやすく、小型の角型はポケットに収まります。大きめの本体はグリップが安定し、パフ数も多い傾向があります。</p><h2>4. バッテリーと充電方式を確認する</h2><p>充電式モデルはUSB Type-Cに対応しているかを確認しておきましょう。充電しながらの使用は避け、説明書に記載された方法で充電してください。</p><h2>5. 保管方法まで考えて選ぶ</h2><p>まとめ買いをした場合は、直射日光や高温を避けて保管し、開封後は早めに使い切るようにしましょう。</p><h2>まとめ</h2><p>吸引回数・フレーバー・サイズ・バッテリー・保管方法の5点を順番に確認すると、自分に合った一台を見つけやすくなります。</p>',
		'non-nicotine-liquid' => '<p>ノンニコチンリキッドはニコチンを含まないリキッドです。フレーバー選びとデバイスとの相性を確認し、20歳以上の方がご自身に合う製品を選びましょう。</p><h2>ノンニコチンリキッドの特徴</h2><p>香りや味わいを中心に楽しめるタイプで、使用するデバイスの出力やコイル抵抗によって感じ方が変わります。</p><h2>選ぶときのポイント</h2><ul><li>対応するデバイスとコイルを確認する</li><li>容量と保管方法を確認する</li><li>使用期限と開封後の扱いを守る</li></ul>',
		'coil-maintenance' => '<p>味の変化や独特な焦げた香りを感じたときがコイル交換の目安です。電源を切り、取扱説明書に従ってゆっくり交換してください。</p><h2>交換前の準備</h2><p>デバイスの電源を切り、リキッドを抜いてから作業します。適合する交換コイルをご用意ください。</p><h2>交換後の注意</h2><p>新しいコイルはリキッドを十分に含ませ、説明書に記載された待ち時間を守ってから使用します。</p>',
		'starter-kit-guide' => '<p>スターターキットは、本体と必要なパーツがまとまった初めての方向けのセットです。購入前に操作のしやすさとメンテナンス性を確認しましょう。</p><h2>本体の操作方式</h2><p>吸うだけで使える自動吸引式と、ボタン操作式があります。日常の使い方に合う方式を選びましょう。</p><h2>消耗品の入手性</h2><p>交換コイルやカートリッジが継続して購入できるかも重要なポイントです。</p>',
		'flavor-selection' => '<p>フレーバーは大きくフルーツ系、メンソール系、スイーツ系に分かれます。香りの強さと後味を意識すると選びやすくなります。</p><h2>系統ごとの特徴</h2><ul><li>フルーツ系：香りが明るく飲み物のような味わい</li><li>メンソール系：清涼感があり、切り替えに向く</li><li>スイーツ系：甘い香りとコクを楽しめる</li></ul><h2>選ぶときのコツ</h2><p>最初は扱いやすい単一フレーバーを選び、好みが分かれてきたら組み合わせを試すのがおすすめです。</p>',
	);
	foreach ( $posts as $post_data ) {
		if ( get_page_by_path( $post_data[0], OBJECT, 'post' ) ) {
			continue;
		}
		$body = $article_bodies[ $post_data[0] ] ?? '<p>' . $post_data[2] . '</p><h2>安心してご利用いただくために</h2><p>当店は20歳以上の方のみを対象としています。商品の取扱説明書をよく読み、ご自身に合った使い方をしてください。</p>';
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $post_data[1],
				'post_name'    => $post_data[0],
				'post_content' => $body,
				'post_excerpt' => $post_data[2],
			)
		);
		wp_set_post_categories( $post_id, array( $category_id ) );
		if ( ! empty( $images[ $post_data[3] ] ) ) {
			set_post_thumbnail( $post_id, $images[ $post_data[3] ] );
		}
	}
}

/**
 * Create and assign the main menu.
 */
function vk_seed_navigation() {
	$menu_name = '7s7 メインメニュー';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
		$items = array(
			array( 'オンラインストア', vk_shop_url() ),
			array( '使い捨てベイプ', get_term_link( 'disposable', 'product_cat' ) ),
			array( 'スターターキット', get_term_link( 'starter', 'product_cat' ) ),
			array( 'シーシャ', get_term_link( 'shisha', 'product_cat' ) ),
			array( 'セール', get_term_link( 'sale', 'product_cat' ) ),
			array( 'ブランド', home_url( '/brand/' ) ),
			array( 'ご利用ガイド', home_url( '/guide/' ) ),
			array( 'お問い合わせ', home_url( '/contact/' ) ),
		);
		foreach ( $items as $position => $item ) {
			if ( is_wp_error( $item[1] ) || ! $item[1] ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'   => $item[0],
					'menu-item-url'     => $item[1],
					'menu-item-status'  => 'publish',
					'menu-item-position'=> $position + 1,
				)
			);
		}
		$locations            = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = $menu_id;
		$locations['footer']  = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}
