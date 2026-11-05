<?php
/**
 * Site footer.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer">
	<div class="container">
		<div class="row site-footer__main">
			<div class="col-12 col-lg-4">
				<?php vk_logo( true ); ?>
				<p class="site-footer__desc">7s7は、正規品のみを取り扱う電子タバコ・ベイプ専門通販店です。使い捨てベイプからリキッド、スターターキット、シーシャまで、20歳以上の方に向けてお届けします。</p>
				<ul class="site-footer__social" aria-label="ソーシャルメディア">
					<li><a href="https://x.com/" aria-label="X（旧Twitter）" rel="noopener"><i class="bi bi-twitter-x" aria-hidden="true"></i></a></li>
					<li><a href="https://www.instagram.com/" aria-label="Instagram" rel="noopener"><i class="bi bi-instagram" aria-hidden="true"></i></a></li>
					<li><a href="https://www.youtube.com/" aria-label="YouTube" rel="noopener"><i class="bi bi-youtube" aria-hidden="true"></i></a></li>
					<li><a href="https://www.tiktok.com/" aria-label="TikTok" rel="noopener"><i class="bi bi-tiktok" aria-hidden="true"></i></a></li>
				</ul>
			</div>
			<div class="col-6 col-lg-3">
				<p class="site-footer__title">ユーザーガイド</p>
				<ul class="site-footer__list">
					<li><a href="<?php echo esc_url( home_url( '/guide/' ) ); ?>">ご利用ガイド</a></li>
					<li><a href="<?php echo esc_url( home_url( '/payment/' ) ); ?>">お支払い方法</a></li>
					<li><a href="<?php echo esc_url( home_url( '/shipping/' ) ); ?>">配送について</a></li>
					<li><a href="<?php echo esc_url( home_url( '/returns/' ) ); ?>">返品・交換について</a></li>
					<li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">よくあるご質問</a></li>
					<li><a href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">ゲスト注文・支払い確認</a></li>
				</ul>
			</div>
			<div class="col-6 col-lg-2">
				<p class="site-footer__title">会社情報</p>
				<ul class="site-footer__list">
					<li><a href="<?php echo esc_url( home_url( '/company/' ) ); ?>">会社概要</a></li>
					<li><a href="<?php echo esc_url( get_privacy_policy_url() ?: home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a></li>
					<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">利用規約</a></li>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせ</a></li>
				</ul>
			</div>
			<div class="col-12 col-lg-3">
				<p class="site-footer__title">メールマガジン登録</p>
				<p class="site-footer__lead">新商品やセール情報をいち早くお届けします。</p>
				<form class="site-footer__form" action="<?php echo esc_url( home_url( '/wp-json/vapekumo/v1/newsletter' ) ); ?>" method="post" data-newsletter-form>
					<label class="visually-hidden" for="newsletterEmail">メールアドレス</label>
					<input class="site-footer__input" id="newsletterEmail" name="email" type="email" placeholder="メールアドレスを入力" required>
					<button class="btn btn-cta site-footer__submit" type="submit">登録する</button>
				</form>
				<p class="site-footer__note" data-newsletter-message>登録は無料です。いつでも解除できます。</p>
			</div>
		</div>
		<div class="site-footer__notice">
			<p><strong>20歳以上の方のみご購入いただけます。</strong> 当店は年齢確認を実施しています。20歳未満の方への販売、ならびに未成年者の喫煙は法律で禁止されています。商品のご使用は取扱説明書に従ってください。</p>
		</div>
		<div class="site-footer__bottom">
			<p class="site-footer__copy">© <?php echo esc_html( gmdate( 'Y' ) ); ?> 7s7 All rights reserved.</p>
			<ul class="site-footer__cards" aria-label="対応決済ブランド">
				<li class="site-footer__card">VISA</li>
				<li class="site-footer__card site-footer__card--mc" aria-label="Mastercard"><i></i><i></i></li>
				<li class="site-footer__card">JCB</li>
				<li class="site-footer__card">AMEX</li>
				<li class="site-footer__card">Diners</li>
			</ul>
		</div>
	</div>
</footer>

<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
	<aside class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
		<div class="offcanvas-header cart-drawer__head">
			<p class="cart-drawer__title" id="cartDrawerLabel">カートに追加しました</p>
			<button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="閉じる"></button>
		</div>
		<div class="offcanvas-body cart-drawer__body">
			<p class="cart-drawer__empty" id="cartDrawerEmpty"<?php echo WC()->cart->is_empty() ? '' : ' hidden'; ?>>カートに商品がありません。</p>
			<div id="cartDrawerItems"><?php echo function_exists( 'vk_get_mini_cart_markup' ) ? wp_kses_post( vk_get_mini_cart_markup() ) : ''; ?></div>
		</div>
		<div class="cart-drawer__foot">
			<p class="cart-drawer__total"><span>小計（<b id="cartDrawerCount"><?php echo esc_html( (string) vk_get_cart_count() ); ?></b>点）</span><b id="cartDrawerTotal"><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></b></p>
			<p class="cart-drawer__note"><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上で送料無料</p>
			<a class="btn btn-cta cart-drawer__checkout<?php echo WC()->cart->is_empty() ? ' is-disabled' : ''; ?>" id="cartDrawerCheckout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>" aria-disabled="<?php echo WC()->cart->is_empty() ? 'true' : 'false'; ?>">レジに進む</a>
			<a class="cart-drawer__link" href="<?php echo esc_url( wc_get_cart_url() ); ?>">カートを見る</a>
		</div>
	</aside>

	<div class="modal fade option-modal" id="optionModal" tabindex="-1" aria-labelledby="optionModalTitle" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<p class="modal-title option-modal__title" id="optionModalTitle">規格を選択してください</p>
					<button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="閉じる"></button>
				</div>
				<div class="modal-body">
					<div class="option-modal__product">
						<img class="option-modal__thumb" id="optionModalThumb" src="" alt="" width="72" height="72">
						<div class="option-modal__meta"><p class="option-modal__brand" id="optionModalBrand"></p><p class="option-modal__name" id="optionModalName"></p><p class="option-modal__price" id="optionModalPrice"></p></div>
					</div>
					<div class="option-group"><p class="option-group__title">フレーバー</p><ul class="option-group__list" id="optionList" role="radiogroup" aria-label="フレーバーを選択"></ul><p class="option-stock-note" id="optionStockNote" aria-live="polite"></p></div>
					<p class="option-modal__error" id="optionModalError" role="alert" hidden></p>
				</div>
				<div class="modal-footer"><button class="btn btn-cta option-modal__submit" type="button" id="optionSubmit">カートに入れる</button></div>
			</div>
		</div>
	</div>
<?php endif; ?>

<button class="to-top" id="toTop" type="button" aria-label="ページの先頭へ戻る">
	<i class="bi bi-chevron-up" aria-hidden="true"></i>
</button>
<div class="toast-area" id="toastArea" aria-live="polite" aria-atomic="true"></div>

<?php if ( is_front_page() ) : ?>
	<div class="modal fade age-gate" id="ageGate" tabindex="-1" aria-labelledby="ageGateTitle" aria-describedby="ageGateText" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body" id="ageGateAsk">
					<i class="bi bi-shield-check age-gate__icon" aria-hidden="true"></i>
					<h2 class="age-gate__title" id="ageGateTitle">年齢確認</h2>
					<p class="age-gate__text" id="ageGateText">当店は電子タバコ製品を取り扱っています。20歳以上の方のみご利用いただけます。</p>
					<div class="age-gate__actions">
						<button class="btn btn-cta" type="button" id="ageGateYes">20歳以上です</button>
						<button class="btn btn-line" type="button" id="ageGateNo">20歳未満です</button>
					</div>
					<p class="age-gate__note">20歳未満の方への販売は法律で禁止されています。</p>
				</div>
				<div class="modal-body age-gate__deny" id="ageGateDeny" hidden tabindex="-1">
					<i class="bi bi-x-circle age-gate__icon" aria-hidden="true"></i>
					<h2 class="age-gate__title" id="ageGateDenyTitle">ご利用いただけません</h2>
					<p class="age-gate__text">20歳未満の方には商品を販売できません。ご理解をお願いします。</p>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
