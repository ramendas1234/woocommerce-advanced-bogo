<?php
/**
 * Uninstall plugin
 *
 * @package  WC_BOGOF
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

global $wpdb;

// Delete product metadata. Deprecated since 1.3.
foreach ( array( '_bogof_specific_variations', '_bogof_except_variations', '_bogof_variations_rule', '_bogof_enabled', '_bogof_mode', '_bogof_min_quantity', '_bogof_free_quantity', '_bogof_limit', '_bogof_product_id' ) as $meta_key ) {
	$wpdb->delete(
		$wpdb->postmeta,
		array(
			'meta_key' => $meta_key,
		),
		array( '%s' )
	);
}

// Delete BOGO rules.
$wc_bogof_post_ids = get_posts(
	array(
		'fields'         => 'ids',
		'post_type'      => 'shop_bogof_rule',
		'post_status'    => array( 'publish', 'future' ),
		'posts_per_page' => -1,
	)
);
foreach ( $wc_bogof_post_ids as $wc_bogof_id ) {
	wp_trash_post( $wc_bogof_id );
}

// Delete pages.
wp_trash_post( get_option( 'wc_bogof_cyg_page_id' ) );

// Delete options.
delete_option( 'wc_bogof_category_settings' ); // Deprecated.
delete_option( 'wc_bogof_cyg_display_on' );
delete_option( 'wc_bogof_cyg_title' );
delete_option( 'wc_bogof_cyg_page_id' );
delete_option( 'wc_bogof_cyg_notice' );
delete_option( 'wc_bogof_cyg_notice_button_text' );
delete_option( 'wc_bogof_version' );
delete_option( 'wc_bogof_mods' );
