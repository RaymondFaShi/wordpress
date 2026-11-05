<?php
/**
 * Blog index fallback.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
get_header();
$blog_categories = get_categories( array( 'hide_empty' => true ) );
?>
<main id="main">
	<?php vk_breadcrumbs(); ?>
	<section class="section blog" aria-labelledby="blogTitle">
		<div class="container">
			<div class="page-head"><div><h1 class="page-head__title" id="blogTitle"><?php echo is_home() ? 'ブログ' : esc_html( get_the_archive_title() ); ?></h1><p class="page-head__lead">選び方やお手入れ方法など、電子タバコを安心して楽しむための情報をお届けします。</p></div><p class="page-head__side blog__count">全 <b><?php echo esc_html( (string) $GLOBALS['wp_query']->found_posts ); ?></b> 記事</p></div>
			<nav class="blog-tabs" aria-label="ブログカテゴリー"><ul class="blog-tabs__list"><li><a class="blog-tabs__link<?php echo is_home() ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">すべて</a></li><?php foreach ( $blog_categories as $blog_category ) : ?><li><a class="blog-tabs__link" href="<?php echo esc_url( get_category_link( $blog_category ) ); ?>"><?php echo esc_html( $blog_category->name ); ?></a></li><?php endforeach; ?></ul></nav>
			<?php if ( have_posts() ) : ?>
				<?php $article_index = 0; ?>
				<?php while ( have_posts() ) : ?>
					<?php
					the_post();
					$article_index++;
					if ( 1 === $article_index ) :
						?>
						<article class="blog-feature">
							<a class="blog-feature__link" href="<?php the_permalink(); ?>">
								<span class="blog-feature__media"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></span>
								<span class="blog-feature__body"><span class="blog-feature__meta"><span class="blog-feature__tag">注目記事</span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time></span><span class="blog-feature__title"><?php the_title(); ?></span><span class="blog-feature__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 58 ) ); ?></span><span class="blog-feature__more">記事を読む</span></span>
							</a>
						</article>
						<h2 class="visually-hidden">新着記事</h2><div class="row g-page blog__grid">
					<?php else : ?>
						<div class="col-12 col-sm-6 col-lg-4 col-xl-3"><article class="bcard"><a class="bcard__link" href="<?php the_permalink(); ?>"><span class="bcard__media"><?php the_post_thumbnail( 'vk-post-card', array( 'loading' => 'lazy' ) ); ?></span><span class="bcard__body"><span class="bcard__meta"><span class="bcard__tag">お知らせ</span><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?></time></span><span class="bcard__title"><?php the_title(); ?></span><span class="bcard__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32 ) ); ?></span></span></a></article></div>
					<?php endif; ?>
				<?php endwhile; ?>
				<?php if ( $article_index > 1 ) : ?></div><?php endif; ?>
				<?php the_posts_pagination( array( 'prev_text' => '前へ', 'next_text' => '次へ' ) ); ?>
			<?php else : ?>
				<div class="woocommerce-info">記事を準備しています。</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
