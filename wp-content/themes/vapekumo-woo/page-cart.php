<?php
/**
 * Cart page.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="cart" aria-labelledby="cartTitle">
		<div class="container">
			<div class="cart__head">
				<div><h1 class="cart__title" id="cartTitle">ショッピングカート<span class="cart__count">（<b><?php echo esc_html( (string) vk_get_cart_count() ); ?></b>点）</span></h1><p class="cart__lead">ご注文内容をご確認ください。数量の変更や商品の削除ができます。</p></div>
				<a class="cart__back" href="<?php echo esc_url( vk_shop_url() ); ?>">買い物を続ける</a>
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
