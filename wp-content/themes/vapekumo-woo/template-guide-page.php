<?php
/**
 * Shared guide.html-style template for guide and company information pages.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();

$current_id   = get_queried_object_id();
$current_slug = (string) get_post_field( 'post_name', $current_id );
$groups       = vk_guide_navigation_groups();
$leads        = vk_guide_page_leads();
$current_item = null;
$group_label  = 'ご利用ガイド';
$current_group = array();
$current_index = 0;

foreach ( $groups as $group ) {
	foreach ( $group['items'] as $slug => $item ) {
		if ( $slug === $current_slug ) {
			$current_group = $group;
			$current_item  = $item;
			$group_label   = $group['label'];
			$current_index = array_search( $slug, array_keys( $group['items'] ), true );
			break 2;
		}
	}
}

$group_items = isset( $current_group['items'] ) ? array_values( $current_group['items'] ) : array();
$previous    = $current_index > 0 && isset( $group_items[ $current_index - 1 ] ) ? $group_items[ $current_index - 1 ] : null;
$next        = isset( $group_items[ $current_index + 1 ] ) ? $group_items[ $current_index + 1 ] : null;
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="guide" aria-labelledby="guideTitle">
		<div class="container">
			<div class="row guide__layout">
				<aside class="col-12 col-lg-3">
					<button class="guide-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#guideSide" aria-controls="guideSide">
						<i class="bi bi-list guide-toggle__icon" aria-hidden="true"></i><?php echo esc_html( $group_label ); ?>
						<span class="guide-toggle__current"><?php echo esc_html( $current_item ? $current_item[0] : get_the_title( $current_id ) ); ?></span>
						<i class="bi bi-chevron-right guide-toggle__chev" aria-hidden="true"></i>
					</button>
					<div class="offcanvas-lg offcanvas-start guide-side" tabindex="-1" id="guideSide" aria-labelledby="guideSideLabel">
						<div class="offcanvas-header d-lg-none">
							<p class="mobile-nav__title" id="guideSideLabel">ご利用ガイド</p>
							<button class="btn-close" type="button" data-bs-dismiss="offcanvas" data-bs-target="#guideSide" aria-label="閉じる"></button>
						</div>
						<div class="offcanvas-body">
							<nav class="guide-nav" aria-label="ご利用ガイドと会社情報">
								<?php foreach ( $groups as $group ) : ?>
									<div class="guide-nav__group">
										<p class="guide-nav__label"><?php echo esc_html( $group['label'] ); ?></p>
										<ul class="guide-nav__list">
											<?php foreach ( $group['items'] as $slug => $item ) : ?>
												<li><a class="guide-nav__link<?php echo $slug === $current_slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( $item[1] ); ?>"<?php echo $slug === $current_slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item[0] ); ?></a></li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endforeach; ?>
							</nav>
							<a class="guide-side__cta" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
								<span class="guide-side__cta-title">解決しないときは</span>
								<span class="guide-side__cta-text">お問い合わせフォームからご相談ください。</span>
							</a>
						</div>
					</div>
				</aside>
				<div class="col-12 col-lg-9">
					<article class="guide-article" aria-labelledby="guideTitle">
						<?php while ( have_posts() ) : ?>
							<?php the_post(); ?>
							<header class="guide-article__head">
								<p class="guide-article__tag"><?php echo esc_html( $group_label ); ?></p>
								<h1 class="guide-article__title" id="guideTitle"><?php the_title(); ?></h1>
								<p class="guide-article__lead"><?php echo esc_html( $leads[ $current_slug ] ?? '7s7をご利用いただくための情報をご案内します。' ); ?></p>
								<ul class="guide-article__meta">
									<li><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">公開：<?php echo esc_html( get_the_date( 'Y年n月j日' ) ); ?></time></li>
									<li><time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">更新：<?php echo esc_html( get_the_modified_date( 'Y年n月j日' ) ); ?></time></li>
								</ul>
							</header>
							<div class="editor-content guide-article__body">
								<?php the_content(); ?>
							</div>
						<?php endwhile; ?>
						<?php if ( $previous || $next ) : ?>
							<nav class="guide-nav-bottom" aria-label="前後のページ">
								<?php if ( $previous ) : ?>
									<a class="guide-nav-bottom__item guide-nav-bottom__item--prev" href="<?php echo esc_url( $previous[1] ); ?>">
										<span class="guide-nav-bottom__dir">前のページ</span>
										<span class="guide-nav-bottom__title"><?php echo esc_html( $previous[0] ); ?></span>
									</a>
								<?php endif; ?>
								<?php if ( $next ) : ?>
									<a class="guide-nav-bottom__item guide-nav-bottom__item--next" href="<?php echo esc_url( $next[1] ); ?>">
										<span class="guide-nav-bottom__dir">次のページ</span>
										<span class="guide-nav-bottom__title"><?php echo esc_html( $next[0] ); ?></span>
									</a>
								<?php endif; ?>
							</nav>
						<?php endif; ?>
					</article>
				</div>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
