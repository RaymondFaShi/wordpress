<?php
/**
 * Contact page.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section contact" aria-labelledby="contactTitle">
		<div class="container">
			<div class="page-head">
				<div>
					<h1 class="page-head__title" id="contactTitle">お問い合わせ</h1>
					<p class="page-head__lead">商品やご注文、配送、返品についてのご質問を受け付けています。内容を確認のうえ、担当者より順次ご連絡いたします。</p>
				</div>
			</div>
			<div class="row g-page contact-info">
				<div class="col-12 col-md-4"><div class="contact-info__card"><i class="bi bi-telephone-fill contact-info__icon" aria-hidden="true"></i><p class="contact-info__label">お電話でのお問い合わせ</p><p class="contact-info__value"><a href="tel:0312345678">03-1234-5678</a></p><p class="contact-info__note">受付時間：平日 10:00〜18:00（土日祝を除く）</p></div></div>
				<div class="col-12 col-md-4"><div class="contact-info__card"><i class="bi bi-envelope-fill contact-info__icon" aria-hidden="true"></i><p class="contact-info__label">メールでのお問い合わせ</p><p class="contact-info__value"><a href="mailto:admin@wpshop.com">admin@wpshop.com</a></p><p class="contact-info__note">24時間受け付け／返信は1〜2営業日を目安にお送りします。</p></div></div>
				<div class="col-12 col-md-4"><div class="contact-info__card"><i class="bi bi-geo-alt-fill contact-info__icon" aria-hidden="true"></i><p class="contact-info__label">よくあるご質問</p><p class="contact-info__value"><a href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">ご利用ガイドを見る</a></p><p class="contact-info__note">お問い合わせの前に、ガイドもあわせてご確認ください。</p></div></div>
			</div>
			<p class="contact-note">お問い合わせ内容によっては、回答までにお時間をいただく場合があります。あらかじめご了承ください。</p>
			<div class="contact-form">
				<h2 class="contact-form__title">お問い合わせフォーム</h2>
				<p class="contact-form__lead"><span class="contact-form__required-note">※</span> は必須項目です。</p>
				<form class="contact-form__form" id="contactForm" action="<?php echo esc_url( rest_url( 'vapekumo/v1/contact' ) ); ?>" method="post" data-contact-form novalidate>
					<div class="cf-row row g-page">
						<div class="col-12 col-sm-6"><div class="cf-field"><label class="cf-label" for="contactLastName">姓<span class="cf-required">必須</span></label><input class="cf-input" id="contactLastName" name="last_name" type="text" autocomplete="family-name" required><p class="cf-error" hidden></p></div></div>
						<div class="col-12 col-sm-6"><div class="cf-field"><label class="cf-label" for="contactFirstName">名<span class="cf-required">必須</span></label><input class="cf-input" id="contactFirstName" name="first_name" type="text" autocomplete="given-name" required><p class="cf-error" hidden></p></div></div>
					</div>
					<div class="cf-field"><label class="cf-label" for="contactEmail">メールアドレス<span class="cf-required">必須</span></label><input class="cf-input" id="contactEmail" name="email" type="email" autocomplete="email" inputmode="email" required><p class="cf-error" hidden></p></div>
					<div class="cf-field"><label class="cf-label" for="contactMessage">お問い合わせ内容<span class="cf-required">必須</span></label><textarea class="cf-input cf-input--area" id="contactMessage" name="message" rows="7" required></textarea><p class="cf-error" hidden></p></div>
					<div class="contact-form__actions"><button class="btn btn-cta contact-form__submit" type="submit">入力内容を確認する</button></div>
					<p class="contact-form__result" id="contactResult" role="status" aria-live="polite" hidden></p>
				</form>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
