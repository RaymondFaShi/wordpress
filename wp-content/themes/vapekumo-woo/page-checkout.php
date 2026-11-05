<?php
/**
 * Checkout and order confirmation page.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="checkout" aria-labelledby="checkoutTitle">
		<div class="container">
			<div class="checkout__head">
				<div><h1 class="checkout__title" id="checkoutTitle">ご注文手続き<span class="checkout__count">（<b><?php echo esc_html( (string) vk_get_cart_count() ); ?></b>点）</span></h1><p class="checkout__lead">配送先とお支払い方法をご入力ください。内容をご確認のうえ注文を確定できます。</p></div>
				<a class="checkout__back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">カートに戻る</a>
			</div>
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</main>
<?php
get_footer();
