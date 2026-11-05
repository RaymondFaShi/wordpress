<?php
/**
 * Single product, matching the original 7s7 PDP.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<?php while ( have_posts() ) : ?>
		<?php
		the_post();
		global $product;
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}

		$rating       = (float) $product->get_average_rating();
		$rating_count = (int) $product->get_review_count();
		$brand        = vk_product_brand( $product );
		$brand_term   = get_term_by( 'name', $brand, 'product_brand' );
		$brand_link   = $brand_term ? get_term_link( $brand_term ) : home_url( '/' . sanitize_title( $brand ) . '.html' );
		$user_favs    = is_user_logged_in() ? (array) get_user_meta( get_current_user_id(), '_vk_favorites', true ) : array();
		$is_fav       = in_array( $product->get_id(), array_map( 'intval', $user_favs ), true );
		$price        = (float) $product->get_price();
		$regular      = $product->is_type( 'variable' ) ? (float) $product->get_variation_regular_price( 'min', true ) : (float) $product->get_regular_price();
		$is_new       = strtotime( $product->get_date_created() ? $product->get_date_created()->date( 'c' ) : '' ) > strtotime( '-21 days' );
		$discount     = ( $product->is_on_sale() && $regular > $price ) ? (int) round( ( ( $regular - $price ) / $regular ) * 100 ) : 0;
		$image_ids    = array_values( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) );
		$image_ids    = array_values( array_unique( array_map( 'intval', $image_ids ) ) );
		$image_slots  = array();
		for ( $index = 0; $index < 20; $index++ ) {
			$image_slots[] = $image_ids ? $image_ids[ $index % count( $image_ids ) ] : 0;
		}
		?>
		<section class="pdp" aria-labelledby="pdpTitle">
			<div class="container">
				<div class="row pdp__grid">
					<div class="col-12 col-lg-6">
						<div class="pdp-gallery" id="pdpGallery">
							<div class="pdp-gallery__rail">
								<button class="pdp-gallery__nav js-pdp-up" type="button" aria-label="前の画像へ" disabled><i class="bi bi-chevron-up" aria-hidden="true"></i></button>
								<div class="pdp-gallery__thumbs" id="pdpThumbs" role="tablist" aria-label="商品画像を切り替え（全20枚）">
									<?php foreach ( $image_slots as $index => $image_id ) : ?>
										<?php
										$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : wc_placeholder_img_src( 'woocommerce_single' );
										$thumb_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : $image_url;
										$image_alt   = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
										$image_alt   = $image_alt ?: $product->get_name() . ' 商品画像' . ( $index + 1 );
										?>
										<button class="pdp-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-controls="pdpGalleryStage" data-index="<?php echo esc_attr( (string) $index ); ?>" data-src="<?php echo esc_url( $image_url ); ?>" data-alt="<?php echo esc_attr( $image_alt ); ?>">
											<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" width="480" height="480" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" decoding="async">
										</button>
									<?php endforeach; ?>
								</div>
								<button class="pdp-gallery__nav js-pdp-down" type="button" aria-label="次の画像へ"><i class="bi bi-chevron-down" aria-hidden="true"></i></button>
							</div>
							<div class="pdp-gallery__stage">
								<div class="pdp-gallery__frame" id="pdpGalleryStage" role="tabpanel">
									<span class="pdp-gallery__flags">
										<?php if ( $discount ) : ?><span class="pdp-flag"><?php echo esc_html( (string) $discount ); ?>%OFF</span><?php endif; ?>
										<?php if ( $is_new ) : ?><span class="pdp-flag pdp-flag--new">NEW</span><?php endif; ?>
									</span>
									<img class="pdp-gallery__img" id="pdpImage" src="<?php echo esc_url( $image_slots ? wp_get_attachment_image_url( $image_slots[0], 'large' ) : wc_placeholder_img_src( 'woocommerce_single' ) ); ?>" alt="<?php echo esc_attr( $product->get_name() . ' 商品画像1' ); ?>" width="480" height="480" fetchpriority="high" decoding="async">
									<span class="pdp-lens" id="pdpLens" aria-hidden="true"></span>
								</div>
								<span class="pdp-gallery__count" id="pdpCount">1 / 20</span>
								<div class="pdp-zoom" id="pdpZoom" aria-hidden="true"></div>
							</div>
						</div>
					</div>

					<div class="col-12 col-lg-6">
						<div class="pdp-info">
							<p class="pdp-info__brand"><a href="<?php echo esc_url( $brand_link ); ?>"><?php echo esc_html( $brand ); ?></a></p>
							<h1 class="pdp-info__title" id="pdpTitle"><?php the_title(); ?></h1>
							<div class="pdp-info__meta">
								<?php if ( $rating_count ) : ?>
									<span class="pdp-rating"><?php vk_rating_html( $rating, $rating_count ); ?><a class="pdp-rating__link js-woo-review-tab" href="#tab-reviews"><?php echo esc_html( (string) $rating_count ); ?>件のレビュー</a></span>
								<?php elseif ( function_exists( 'vk_current_member_bought_product' ) && vk_current_member_bought_product( $product->get_id() ) ) : ?>
									<span class="pdp-rating"><a class="pdp-rating__link js-woo-review-tab" href="#tab-reviews">購入者レビューを投稿</a></span>
								<?php else : ?>
									<span class="pdp-rating">購入者のみレビュー投稿できます</span>
								<?php endif; ?>
								<span>型番：<?php echo esc_html( $product->get_sku() ?: '—' ); ?></span>
							</div>

							<div class="pdp-price">
								<span class="pdp-price__now"><?php echo wp_kses_post( wc_price( $price ) ); ?><small>（税込）</small></span>
								<?php if ( $product->is_on_sale() && $regular > $price ) : ?><del class="pdp-price__was"><?php echo wp_kses_post( wc_price( $regular ) ); ?></del><span class="pdp-price__off"><?php echo esc_html( (string) $discount ); ?>%OFF</span><?php endif; ?>
								<span class="pdp-price__tags"><span class="badge pdp-badge pdp-badge--free"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上送料無料</span><span class="badge pdp-badge pdp-badge--genuine">正規品</span><?php if ( (int) $product->get_total_sales() >= 70 ) : ?><span class="badge pdp-badge pdp-badge--popular">人気商品</span><?php endif; ?></span>
							</div>

							<p class="pdp-stock"><?php echo esc_html( $product->is_in_stock() ? '在庫あり' : '在庫切れ' ); ?><span>／</span>15時までのご注文で当日発送</p>
							<p class="pdp-info__lead"><?php echo esc_html( wp_strip_all_tags( $product->get_short_description() ) ); ?></p>
							<div class="pdp-variants" id="pdpVariants"></div>

							<div class="pdp-buy pdp-buy--woo">
								<?php woocommerce_template_single_add_to_cart(); ?>
								<button class="pdp-actions__fav js-fav<?php echo $is_fav ? ' is-on' : ''; ?>" type="button" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>" aria-pressed="<?php echo esc_attr( $is_fav ? 'true' : 'false' ); ?>" aria-label="<?php echo esc_attr( $is_fav ? 'お気に入りから削除' : 'お気に入りに追加' ); ?>">
									<i class="bi <?php echo esc_attr( $is_fav ? 'bi-heart-fill' : 'bi-heart' ); ?>" aria-hidden="true"></i><span>お気に入り</span>
								</button>
							</div>

							<ul class="pdp-services">
								<li class="pdp-service"><i class="bi bi-shield-check" aria-hidden="true"></i><div><p class="pdp-service__title">正規品保証</p><p class="pdp-service__text">正規ルートで仕入れた商品のみ</p></div></li>
								<li class="pdp-service"><i class="bi bi-truck" aria-hidden="true"></i><div><p class="pdp-service__title">スムーズな配送</p><p class="pdp-service__text"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</p></div></li>
								<li class="pdp-service"><i class="bi bi-headset" aria-hidden="true"></i><div><p class="pdp-service__title">安心のサポート</p><p class="pdp-service__text">日本語でお問い合わせ可能</p></div></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="section pdp-tabsec product-details" aria-label="商品の詳細情報">
			<div class="container">
				<div class="pdp-tabsec__inner">
					<?php woocommerce_output_product_data_tabs(); ?>
				</div>
			</div>
		</section>
		<?php vk_render_related_products( $product ); ?>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
