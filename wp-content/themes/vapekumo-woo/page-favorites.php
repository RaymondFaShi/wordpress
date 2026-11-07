<?php
/**
 * Favorites page, using the account shell for signed-in customers.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	vk_render_custom_account_page( 'favorites', 'お気に入り', '気になる商品を保存して、あとからまとめて確認できます。', 'vk_favorites_shortcode' );
	return;
}

get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section">
		<div class="container">
			<div class="page-head"><div><h1 class="page-head__title">お気に入り</h1><p class="page-head__lead">気になる商品を保存して、あとからまとめて確認できます。</p></div><a class="page-head__side" href="<?php echo esc_url( vk_account_url() ); ?>">ログインして同期する</a></div>
			<?php echo do_shortcode( '[vapekumo_favorites]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
