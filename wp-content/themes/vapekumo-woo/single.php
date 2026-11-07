<?php
/**
 * Single post.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<section class="article-wrap" aria-label="記事本文">
		<article class="article" aria-labelledby="articleTitle">
			<div class="container">
				<div class="article__layout">
				<header class="article__head">
					<p class="article__tag">お知らせ</p>
					<h1 class="article__title" id="articleTitle"><?php the_title(); ?></h1>
					<p class="article__lead"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 65 ) ); ?></p>
					<ul class="article__meta"><li><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">公開：<?php echo esc_html( get_the_date( 'Y年n月j日' ) ); ?></time></li><li>著者：7s7 編集部</li></ul>
				</header>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="article__hero"><?php the_post_thumbnail( 'large' ); ?><figcaption><?php echo esc_html( get_the_excerpt() ); ?></figcaption></figure>
				<?php endif; ?>
				<div class="editor-content article__body"><?php the_content(); ?></div>
				<div class="article-share"><p class="article-share__label">この記事をシェアする</p><ul class="article-share__list"><li><a class="article-share__link article-share__link--x" href="<?php echo esc_url( 'https://x.com/intent/tweet?url=' . rawurlencode( get_permalink() ) ); ?>" rel="noopener nofollow" target="_blank">X</a></li><li><a class="article-share__link article-share__link--fb" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( get_permalink() ) ); ?>" rel="noopener nofollow" target="_blank">Facebook</a></li><li><a class="article-share__link article-share__link--line" href="<?php echo esc_url( 'https://social-plugins.line.me/lineit/share?url=' . rawurlencode( get_permalink() ) ); ?>" rel="noopener nofollow" target="_blank">LINE</a></li></ul></div>
				<nav class="article-nav" aria-label="前後の記事"><a class="article-nav__item article-nav__item--prev" href="<?php echo esc_url( get_previous_post_link() ? get_permalink( get_previous_post() ) : get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><span class="article-nav__dir">前の記事</span><span class="article-nav__title"><?php echo esc_html( get_previous_post() ? get_the_title( get_previous_post() ) : '記事一覧へ' ); ?></span></a><a class="article-nav__item article-nav__item--next" href="<?php echo esc_url( get_next_post() ? get_permalink( get_next_post() ) : get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><span class="article-nav__dir">次の記事</span><span class="article-nav__title"><?php echo esc_html( get_next_post() ? get_the_title( get_next_post() ) : '記事一覧へ' ); ?></span></a></nav>
				<aside class="article-related" aria-labelledby="relatedTitle"><div class="section__head"><div><h2 class="section__title" id="relatedTitle">関連記事</h2></div><a class="section__more" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">記事一覧<i class="bi bi-chevron-right" aria-hidden="true"></i></a></div><div class="row g-page"><?php foreach ( get_posts( array( 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ) ) ) as $related_post ) : ?><div class="col-12 col-sm-6 col-lg-4"><article class="bcard"><a class="bcard__link" href="<?php echo esc_url( get_permalink( $related_post ) ); ?>"><span class="bcard__media"><?php echo get_the_post_thumbnail( $related_post, 'vk-post-card', array( 'loading' => 'lazy' ) ); ?></span><span class="bcard__body"><span class="bcard__meta"><span class="bcard__tag">お知らせ</span><time datetime="<?php echo esc_attr( get_the_date( 'c', $related_post ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d', $related_post ) ); ?></time></span><span class="bcard__title"><?php echo esc_html( get_the_title( $related_post ) ); ?></span></span></a></article></div><?php endforeach; ?></div></aside>
				</div>
			</div>
		</article>
		</section>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
