<?php
/**
 * Homepage banner settings and defaults.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default homepage banner configuration.
 *
 * @return array
 */
function vk_home_banner_defaults() {
	return array(
		'hero'  => array(
			1 => array(
				'image' => 'images/banner/banner-01.jpg',
				'link'  => vk_product_category_url( 'sale' ),
				'alt'   => '今月のセール特集',
			),
			2 => array(
				'image' => 'images/banner/banner-02.jpg',
				'link'  => vk_product_category_url( 'starter-kits' ),
				'alt'   => '新商品入荷',
			),
			3 => array(
				'image' => 'images/banner/banner-03.jpg',
				'link'  => vk_product_category_url( 'disposable' ),
				'alt'   => '使い捨てベイプ特集',
			),
			4 => array(
				'image' => 'images/banner/banner-04.jpg',
				'link'  => home_url( '/brand/' ),
				'alt'   => 'ブランド特集',
			),
		),
		'promo' => array(
			1 => array(
				'image' => 'images/banner/banner-01.jpg',
				'link'  => vk_product_category_url( 'sale' ),
				'alt'   => 'セール特集',
			),
			2 => array(
				'image' => 'images/banner/banner-02.jpg',
				'link'  => vk_product_category_url( 'starter-kits' ),
				'alt'   => '新商品特集',
			),
			3 => array(
				'image' => 'images/banner/banner-03.jpg',
				'link'  => vk_product_category_url( 'disposable' ),
				'alt'   => '使い捨て特集',
			),
			4 => array(
				'image' => 'images/banner/banner-04.jpg',
				'link'  => vk_product_category_url( 'hookah' ),
				'alt'   => 'シーシャ特集',
			),
		),
	);
}

/**
 * Get resolved homepage banner data for a group.
 *
 * @param string $group Banner group, hero or promo.
 * @return array
 */
function vk_get_home_banners( $group = 'hero' ) {
	$defaults = vk_home_banner_defaults();
	$items    = isset( $defaults[ $group ] ) ? $defaults[ $group ] : array();
	$banners  = array();

	foreach ( $items as $index => $default ) {
		$setting_prefix = 'vk_home_' . $group . '_' . $index;
		$image_id       = absint( get_theme_mod( $setting_prefix . '_image_id', 0 ) );
		$image_url      = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
		$alt            = (string) get_theme_mod( $setting_prefix . '_alt', $default['alt'] );
		if ( '' === trim( $alt ) && $image_id ) {
			$alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
		}
		$banners[] = array(
			'image_id'  => $image_id,
			'image_url' => $image_url ?: vk_asset( $default['image'] ),
			'link'      => (string) get_theme_mod( $setting_prefix . '_link', $default['link'] ),
			'alt'       => $alt,
		);
	}

	return $banners;
}

/**
 * Register homepage banner controls in the WordPress Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function vk_register_home_banner_customizer( $wp_customize ) {
	$wp_customize->add_panel(
		'vk_home_banner_panel',
		array(
			'title'       => '首页 Banner',
			'description' => '首页主轮播与右侧四张专题 Banner。建议上传 1298 × 800px 或相同比例的横图。',
			'priority'    => 25,
		)
	);

	$groups = array(
		'hero'  => array(
			'title'       => '主轮播 Banner',
			'description' => '首页左侧自动轮播，共 4 张。第一张建议作为最重要的活动主图。',
			'label'       => '主轮播',
		),
		'promo' => array(
			'title'       => '右侧专题 Banner',
			'description' => '首页主轮播右侧的四个专题入口，共 4 张。',
			'label'       => '专题 Banner',
		),
	);

	$defaults = vk_home_banner_defaults();
	foreach ( $groups as $group => $group_config ) {
		$section_id = 'vk_home_banner_' . $group;
		$wp_customize->add_section(
			$section_id,
			array(
				'title'       => $group_config['title'],
				'description' => $group_config['description'],
				'panel'       => 'vk_home_banner_panel',
			)
		);

		foreach ( $defaults[ $group ] as $index => $default ) {
			$prefix        = 'vk_home_' . $group . '_' . $index;
			$image_setting = $prefix . '_image_id';
			$link_setting  = $prefix . '_link';
			$alt_setting   = $prefix . '_alt';

			$wp_customize->add_setting(
				$image_setting,
				array(
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				new WP_Customize_Media_Control(
					$wp_customize,
					$image_setting,
					array(
						'label'       => $group_config['label'] . ' ' . $index,
						'description' => '画像未更换时使用当前网站默认图片。',
						'section'     => $section_id,
						'settings'    => $image_setting,
						'mime_type'   => 'image',
						'button_labels' => array(
							'select'       => '画像を選択',
							'change'       => '画像を変更',
							'remove'       => '画像を削除',
							'default'      => '既定画像に戻す',
							'placeholder'  => '画像が選択されていません',
							'frame_title'  => 'Banner 画像を選択',
							'frame_button' => 'この画像を使用',
						),
					)
				)
			);

			$wp_customize->add_setting(
				$link_setting,
				array(
					'default'           => $default['link'],
					'sanitize_callback' => 'esc_url_raw',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$link_setting,
				array(
					'type'        => 'url',
					'label'       => '点击后的链接',
					'description' => '可填写分类、商品或任意站内/站外链接。',
					'section'     => $section_id,
					'settings'    => $link_setting,
				)
			);

			$wp_customize->add_setting(
				$alt_setting,
				array(
					'default'           => $default['alt'],
					'sanitize_callback' => 'sanitize_text_field',
					'transport'         => 'refresh',
				)
			);
			$wp_customize->add_control(
				$alt_setting,
				array(
					'type'        => 'text',
					'label'       => '图片替代文字',
					'description' => '用于无障碍和图片 SEO，建议用简短日文描述图片内容。',
					'section'     => $section_id,
					'settings'    => $alt_setting,
				)
			);
		}
	}
}
add_action( 'customize_register', 'vk_register_home_banner_customizer' );
