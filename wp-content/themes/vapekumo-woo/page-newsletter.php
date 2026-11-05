<?php
/**
 * Account newsletter preferences.
 *
 * @package Vapekumo_Woo
 */

defined( 'ABSPATH' ) || exit;
vk_render_custom_account_page( 'newsletter', 'メール配信設定', '受け取りたいお知らせをオン／オフで選べます。', 'vk_account_newsletter_content' );
