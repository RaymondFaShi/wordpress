<?php
/**
 * Shopping guide and payment information.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$sections = array(
	'guide-intro'    => 'ご利用ガイド',
	'guide-order'    => 'ご注文の流れ',
	'guide-payment'  => 'お支払い方法',
	'guide-shipping' => '配送について',
	'guide-returns'  => '返品・交換について',
	'guide-account'  => '会員登録とマイページ',
	'guide-age'      => '年齢確認について',
	'guide-faq'      => 'よくあるご質問',
);
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="guide" aria-labelledby="guideTitle">
		<div class="container">
			<div class="row guide__layout">
				<aside class="col-12 col-lg-3">
					<button class="guide-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#guideSide" aria-controls="guideSide">
						<i class="bi bi-list guide-toggle__icon" aria-hidden="true"></i>ご利用ガイド
						<span class="guide-toggle__current">お支払い方法</span>
						<i class="bi bi-chevron-right guide-toggle__chev" aria-hidden="true"></i>
					</button>
					<div class="offcanvas-lg offcanvas-start guide-side" tabindex="-1" id="guideSide" aria-labelledby="guideSideLabel">
						<div class="offcanvas-header d-lg-none"><p class="mobile-nav__title" id="guideSideLabel">ご利用ガイド</p><button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#guideSide" aria-label="閉じる"></button></div>
						<div class="offcanvas-body">
							<nav class="guide-nav" aria-label="ご利用ガイドの目次">
								<p class="guide-nav__label">ご利用ガイド</p>
								<ul class="guide-nav__list">
									<?php foreach ( $sections as $anchor => $label ) : ?>
										<li><a class="guide-nav__link<?php echo 'guide-payment' === $anchor ? ' is-active' : ''; ?>" href="#<?php echo esc_attr( $anchor ); ?>"<?php echo 'guide-payment' === $anchor ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
									<?php endforeach; ?>
								</ul>
							</nav>
							<a class="guide-side__cta" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><span class="guide-side__cta-title">解決しないときは</span><span class="guide-side__cta-text">お問い合わせフォームからご相談ください。</span></a>
						</div>
					</div>
				</aside>
				<div class="col-12 col-lg-9">
					<article class="guide-article" aria-labelledby="guideTitle">
						<header class="guide-article__head">
							<p class="guide-article__tag">ご利用ガイド</p>
							<h1 class="guide-article__title" id="guideTitle">お支払い方法</h1>
							<p class="guide-article__lead">7s7では、クレジットカード決済をご利用いただけます。</p>
							<ul class="guide-article__meta"><li><time datetime="2026-09-01">公開：2026年9月1日</time></li><li><time datetime="2026-11-02">更新：2026年11月2日</time></li></ul>
						</header>
						<div class="editor-content guide-article__body">
							<h2 id="guide-intro">ご利用について</h2>
							<p>当店は20歳以上の方のみご利用いただけます。商品のご注文からお届けまで、安心してご利用いただけるよう日本語でサポートしています。</p>
							<h2 id="guide-order">ご注文の流れ</h2>
							<ol><li>商品を選び、カートに追加します。</li><li>カートで数量を確認し、購入手続きへ進みます。</li><li>配送先とお支払い方法を入力して注文を確定します。</li><li>確認後、商品を発送します。</li></ol>
							<h2 id="guide-payment">お支払い方法</h2>
							<p>ご注文時に、以下のいずれかをお選びいただけます。</p>
							<table><caption>お支払い方法と手数料の一覧</caption><thead><tr><th>お支払い方法</th><th>手数料</th><th>お支払いのタイミング</th></tr></thead><tbody><tr><td>銀行振込</td><td>無料（振込手数料はお客様負担）</td><td>ご注文後7日以内</td></tr><tr><td>代金引換</td><td>無料</td><td>商品お受け取り時</td></tr></tbody></table>
							<h3>銀行振込</h3><p>ご注文後にご案内する口座へ、7日以内にお振込みください。入金確認後に商品を発送します。</p>
							<h3>代金引換</h3><p>商品お受け取り時に、配達員へ代金をお支払いください。</p>
							<blockquote><p>カード決済は現在準備中です。決済サービス導入後にチェックアウトへ追加します。</p></blockquote>
							<h2 id="guide-shipping">配送について</h2>
							<p><?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上のご注文は通常配送が無料です。15時までのご注文は当日発送を目安にしています。</p>
							<h2 id="guide-returns">返品・交換について</h2>
							<p>未開封かつ商品到着後7日以内の不良品について承ります。お問い合わせフォームより注文番号を添えてご連絡ください。</p>
							<h2 id="guide-account">会員登録とマイページ</h2>
							<p>会員登録をすると、注文履歴、アドレス帳、お気に入り、アカウント情報をマイページから管理できます。</p>
							<h2 id="guide-age">年齢確認について</h2>
							<p>20歳未満の方への販売は法律で禁止されています。ご注文者様が20歳以上であることを確認させていただく場合があります。</p>
							<h2 id="guide-faq">よくあるご質問</h2>
							<div class="accordion guide-faq" id="payFaq">
								<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payFaq1" aria-expanded="false" aria-controls="payFaq1">支払い方法は注文後に変更できますか？</button></h3><div class="accordion-collapse collapse" id="payFaq1" data-bs-parent="#payFaq"><div class="accordion-body">発送前であれば変更できる場合があります。注文番号を添えてお問い合わせください。</div></div></div>
								<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payFaq2" aria-expanded="false" aria-controls="payFaq2">領収書は発行できますか？</button></h3><div class="accordion-collapse collapse" id="payFaq2" data-bs-parent="#payFaq"><div class="accordion-body">領収書をご希望の場合は、宛名と但し書きを添えてお問い合わせください。</div></div></div>
								<div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payFaq3" aria-expanded="false" aria-controls="payFaq3">送料無料の条件を教えてください。</button></h3><div class="accordion-collapse collapse" id="payFaq3" data-bs-parent="#payFaq"><div class="accordion-body">商品小計が<?php echo esc_html( vk_free_shipping_minimum_label() ); ?>円以上の場合、通常配送の送料が無料になります。</div></div></div>
							</div>
						</div>
						<nav class="guide-nav-bottom" aria-label="前後のガイド"><a class="guide-nav-bottom__item guide-nav-bottom__item--prev" href="#guide-order"><span class="guide-nav-bottom__dir">前のガイド</span><span class="guide-nav-bottom__title">ご注文の流れ</span></a><a class="guide-nav-bottom__item guide-nav-bottom__item--next" href="#guide-shipping"><span class="guide-nav-bottom__dir">次のガイド</span><span class="guide-nav-bottom__title">配送について</span></a></nav>
					</article>
				</div>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
