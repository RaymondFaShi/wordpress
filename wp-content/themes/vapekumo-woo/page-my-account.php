<?php
/**
 * Template Name: Member account
 * Customer authentication and account pages.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>

	<?php if ( ! is_user_logged_in() ) : ?>
		<?php
		$is_lost_password = isset( $GLOBALS['wp']->query_vars['lost-password'] ) || isset( $_GET['lost-password'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $is_lost_password ) :
			?>
			<section class="auth auth--narrow" aria-labelledby="forgotTitle">
				<div class="container">
					<div class="auth__head"><h1 class="auth__title" id="forgotTitle">パスワード再設定</h1><p class="auth__lead">ご登録のユーザー名またはメールアドレスを入力してください。パスワード再設定用のご案内をお送りします。</p></div>
					<div class="auth-card"><div class="auth-card__body">
						<?php wc_print_notices(); ?>
						<form class="auth-form" method="post" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>">
							<input type="hidden" name="redirect_to" value="<?php echo esc_url( vk_account_url() ); ?>">
							<div class="afield"><label class="afield__label" for="forgotId">ユーザー名またはメールアドレス<span class="afield__req">必須</span></label><input class="afield__input" id="forgotId" name="user_login" type="text" autocomplete="username" required></div>
							<button class="btn btn-cta auth-submit" type="submit">再設定用メールを送信する</button>
						</form>
						<p class="auth-note auth-note--center">ご登録のメールアドレスがご不明な場合は、<a class="auth-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせ</a>よりご連絡ください。</p>
						<p class="auth-back"><a class="auth-link" href="<?php echo esc_url( vk_account_url() ); ?>">ログイン画面に戻る</a></p>
					</div></div>
				</div>
			</section>
		<?php else : ?>
			<section class="auth" aria-labelledby="authTitle">
				<div class="container">
					<div class="auth__head"><h1 class="auth__title" id="authTitle">ログイン・新規会員登録</h1><p class="auth__lead">アカウントをお持ちの方はログイン、はじめての方は会員登録へお進みください。</p></div>
					<?php wc_print_notices(); ?>
					<div class="row auth__grid">
						<div class="col-12 col-lg-7 order-1 order-lg-2">
							<div class="auth-card">
								<div class="auth-card__top"><div class="auth-switch" role="tablist" aria-label="ログインと新規会員登録の切替" data-active="login"><span class="auth-switch__slider" aria-hidden="true"></span><button class="auth-switch__btn active" id="tabLoginBtn" data-bs-toggle="tab" data-bs-target="#tabLogin" type="button" role="tab" aria-controls="tabLogin" aria-selected="true">ログイン</button><button class="auth-switch__btn" id="tabRegisterBtn" data-bs-toggle="tab" data-bs-target="#tabRegister" type="button" role="tab" aria-controls="tabRegister" aria-selected="false">新規会員登録</button></div></div>
								<div class="tab-content">
									<div class="tab-pane fade show active" id="tabLogin" role="tabpanel" aria-labelledby="tabLoginBtn" tabindex="0">
										<div class="auth-card__body">
											<form class="auth-form" method="post" action="<?php echo esc_url( wp_login_url( vk_account_url() ) ); ?>">
												<div class="afield"><label class="afield__label" for="loginId">ユーザー名またはメールアドレス<span class="afield__req">必須</span></label><input class="afield__input" id="loginId" name="log" type="text" autocomplete="username" placeholder="例）7s7 または you@example.com" required></div>
												<div class="afield"><label class="afield__label" for="loginPassword">パスワード<span class="afield__req">必須</span></label><div class="afield__pw"><input class="afield__input" id="loginPassword" name="pwd" type="password" autocomplete="current-password" placeholder="パスワードを入力" required><button class="afield__pwbtn js-pw-toggle" type="button" data-target="loginPassword" aria-label="パスワードを表示" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
												<div class="auth-form__row"><label class="auth-check" for="loginKeep"><input type="checkbox" id="loginKeep" name="rememberme" value="forever" checked><span>ログイン状態を保持する</span></label><a class="auth-link" href="<?php echo esc_url( wc_lostpassword_url() ); ?>">パスワードをお忘れですか？</a></div>
												<input type="hidden" name="redirect_to" value="<?php echo esc_url( vk_account_url() ); ?>">
												<button class="btn btn-cta auth-submit" type="submit">ログイン</button>
												<p class="auth-note">初めてのご利用ですか？ <a class="auth-link js-goto-tab" href="#tabRegister" data-bs-target="#tabRegister">新規会員登録はこちら</a></p>
												<p class="auth-note auth-note--center">会員登録なしでご注文された方は <a class="auth-link" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">ゲスト注文・支払いを確認</a></p>
											</form>
										</div>
									</div>
									<div class="tab-pane fade" id="tabRegister" role="tabpanel" aria-labelledby="tabRegisterBtn" tabindex="0">
										<div class="auth-card__body">
											<form class="auth-form woocommerce-form-register register" method="post" action="<?php echo esc_url( vk_account_url() ); ?>">
												<?php do_action( 'woocommerce_register_form_start' ); ?>
												<div class="afield"><label class="afield__label" for="regEmail">メールアドレス<span class="afield__req">必須</span></label><input class="afield__input" id="regEmail" name="email" type="email" autocomplete="email" inputmode="email" placeholder="you@example.com" required></div>
												<div class="afield"><label class="afield__label" for="regPassword">パスワード<span class="afield__req">必須</span></label><div class="afield__pw"><input class="afield__input" id="regPassword" name="password" type="password" autocomplete="new-password" placeholder="8文字以上で入力" required><button class="afield__pwbtn js-pw-toggle" type="button" data-target="regPassword" aria-label="パスワードを表示" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
												<div class="afield"><label class="afield__label" for="regPasswordConfirm">パスワード（確認）<span class="afield__req">必須</span></label><div class="afield__pw"><input class="afield__input" id="regPasswordConfirm" name="password_confirm" type="password" autocomplete="new-password" placeholder="もう一度入力" minlength="8" required><button class="afield__pwbtn js-pw-toggle" type="button" data-target="regPasswordConfirm" aria-label="パスワードを表示" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div><p class="afield__hint">確認のため、同じパスワードを再入力してください。</p></div>
												<div class="afield"><span class="afield__label">年齢確認<span class="afield__req">必須</span></span><label class="auth-check" for="regAgree"><input type="checkbox" id="regAgree" name="age_confirmed" value="1" required><span>私は20歳以上で、利用規約とプライバシーポリシーに同意します</span></label><p class="auth-terms">詳しくは<a class="auth-link" href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">利用規約</a>・<a class="auth-link" href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">プライバシーポリシー</a>をご確認ください。</p></div>
												<?php do_action( 'woocommerce_register_form' ); ?>
												<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
												<input type="hidden" name="action" value="register">
												<button class="btn btn-cta auth-submit" type="submit" name="register" value="会員登録する">会員登録する</button>
												<p class="auth-note">すでにアカウントをお持ちですか？ <a class="auth-link js-goto-tab" href="#tabLogin" data-bs-target="#tabLogin">ログインはこちら</a></p>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
						<aside class="col-12 col-lg-5 order-2 order-lg-1"><div class="auth-aside"><p class="auth-aside__badge">7s7 会員特典</p><h2 class="auth-aside__title">会員になると<br>もっと便利に</h2><p class="auth-aside__text">アカウントを作成すると、お買い物がスムーズになり、会員向けの情報をご利用いただけます。</p><ul class="auth-aside__list"><li>会員限定のセール・クーポンをお届け</li><li>注文履歴・お気に入りを保存</li><li>お届け先を登録できる</li><li>再入荷のお知らせをメールで受け取れる</li></ul><p class="auth-aside__note">登録は無料です。いつでも退会できます。<br>ご不明な点はお問い合わせください。</p></div></aside>
					</div>
				</div>
			</section>
		<?php endif; ?>
	<?php else : ?>
		<?php
		$endpoint = WC()->query->get_current_endpoint();
		if ( ! $endpoint ) {
			foreach ( array( 'orders', 'reviews', 'favorites', 'edit-address', 'edit-account', 'newsletter', 'view-order' ) as $endpoint_key ) {
				if ( is_wc_endpoint_url( $endpoint_key ) ) {
					$endpoint = $endpoint_key;
					break;
				}
			}
		}
		$titles   = array(
			''             => array( 'マイページ', 'ご注文の状況・お気に入り・会員情報をここからまとめて確認できます。' ),
			'orders'       => array( '注文履歴', 'ご注文の状況を確認できます。発送後は配送状況の追跡がご利用いただけます。' ),
			'reviews'      => array( '投稿したレビュー', 'ご購入いただいた商品に投稿したレビューです。' ),
			'favorites'    => array( 'お気に入り', '気になる商品を保存して、あとからまとめて確認できます。' ),
			'edit-address' => array( 'アドレス帳', 'ご登録の配送先・請求先住所を管理できます。' ),
			'edit-account' => array( 'アカウント詳細', 'ご登録情報の確認と変更、パスワードの変更ができます。' ),
			'newsletter'   => array( 'メール配信設定', '受け取りたいお知らせをオン／オフで選べます。' ),
			'view-order'   => array( '注文詳細', 'ご注文内容と配送状況を確認できます。' ),
		);
		$current = $titles[ $endpoint ] ?? array( 'マイページ', 'アカウント情報を管理できます。' );
		$order_count = (int) wc_get_customer_order_count( get_current_user_id() );
		$favorite_count = vk_get_user_favorite_count();
		$review_count = count( get_comments( array( 'user_id' => get_current_user_id(), 'status' => 'approve', 'type' => 'review', 'number' => 100 ) ) );
		$recommended_products = wc_get_products( array( 'status' => 'publish', 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC' ) );
		?>
		<section class="user-page" aria-labelledby="myPageTitle">
			<div class="container">
				<?php wc_print_notices(); ?>
				<div class="row user-layout">
					<aside class="col-12 col-lg-3"><?php vk_account_sidebar( $endpoint ); ?></aside>
					<div class="col-12 col-lg-9">
						<div class="user-head">
							<div><h1 class="user-head__title" id="myPageTitle"><?php echo esc_html( $current[0] ); ?></h1><p class="user-head__lead"><?php echo esc_html( $current[1] ); ?></p></div>
							<a class="user-head__more" href="<?php echo esc_url( vk_shop_url() ); ?>">買い物を続ける</a>
						</div>
						<?php if ( '' === $endpoint ) : ?>
							<div class="u-hello">
								<div><p class="u-hello__title">ようこそ、<b><?php echo esc_html( vk_current_member_display_name() ); ?></b> 様</p><p class="u-hello__text">7s7へおかえりなさい。ご注文の状況、お気に入り、会員情報をここからまとめて確認できます。</p></div>
								<div class="u-hello__actions"><a class="btn u-hello__btn" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>">注文履歴を見る</a><a class="btn u-hello__btn--ghost" href="<?php echo esc_url( vk_shop_url() ); ?>">買い物を続ける</a></div>
							</div>
							<div class="u-stats">
								<a class="u-stat" href="<?php echo esc_url( vk_account_url( 'orders' ) ); ?>"><span class="u-stat__label"><i class="bi bi-box-seam" aria-hidden="true"></i>注文履歴</span><span class="u-stat__value"><?php echo esc_html( (string) $order_count ); ?><small>件</small></span><span class="u-stat__link">一覧を見る</span></a>
								<a class="u-stat" href="<?php echo esc_url( vk_account_url( 'reviews' ) ); ?>"><span class="u-stat__label"><i class="bi bi-star" aria-hidden="true"></i>レビュー</span><span class="u-stat__value"><?php echo esc_html( (string) $review_count ); ?><small>件</small></span><span class="u-stat__link">レビューを見る</span></a>
								<a class="u-stat" href="<?php echo esc_url( vk_account_url( 'favorites' ) ); ?>"><span class="u-stat__label"><i class="bi bi-heart" aria-hidden="true"></i>お気に入り</span><span class="u-stat__value"><?php echo esc_html( (string) $favorite_count ); ?><small>件</small></span><span class="u-stat__link">一覧を見る</span></a>
							</div>
							<section class="u-recommend" aria-labelledby="accountRecommendTitle">
								<div class="section__head"><div><h2 class="section__title" id="accountRecommendTitle">おすすめ商品</h2><p class="section__sub">最近入荷した商品をチェックできます。</p></div><a class="section__more" href="<?php echo esc_url( vk_shop_url() ); ?>">商品一覧<i class="bi bi-chevron-right" aria-hidden="true"></i></a></div>
								<div class="row row-cols-2 row-cols-lg-4 g-page"><?php foreach ( $recommended_products as $recommended_product ) : ?><div class="col"><?php vk_product_card( $recommended_product ); ?></div><?php endforeach; ?></div>
							</section>
						<?php endif; ?>
						<?php if ( '' !== $endpoint ) : ?>
							<div class="user-account-content<?php echo in_array( $endpoint, array( 'edit-account', 'edit-address', 'favorites', 'reviews', 'newsletter' ), true ) ? ' user-account-content--custom' : ''; ?>">
								<?php vk_render_account_endpoint_content( $endpoint ); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
