<?php
/*
Plugin Name: Manage Privacy Options
Description: Add the roles choice for the privacy options page
Version: 1.1
Author: Julio Potier
Author URI: https://21douze.fr
License: GPLv2
*/

add_action( 'admin_init', 'bawmpo_manage_meta_cap_for_privacy_options' );
function bawmpo_manage_meta_cap_for_privacy_options() {
	$current_user  = wp_get_current_user();
	$allowed_roles = get_option( 'roles_for_privacy_policy', [] );
	if ( array_intersect( array_keys( $allowed_roles ), $current_user->roles ) ) {
		add_action( 'map_meta_cap', 'bawmpo_allow_editor_manage_privacy_options', 1, 4 );
	}
}

function bawmpo_allow_editor_manage_privacy_options( $caps, $cap, $user_id, $args ) {
	if ( 'manage_privacy_options' === $cap ) {
		$caps = array_diff( $caps, ['manage_options'] );
	}
	return $caps;
}

add_action( 'load-privacy.php', 'bawmpo_add_roles_for_privacy_options_on_load' ); // < WP 5.3
add_action( 'load-options-privacy.php', 'bawmpo_add_roles_for_privacy_options_on_load' );
function bawmpo_add_roles_for_privacy_options_on_load() {
	add_filter( 'wp_dropdown_pages', 'bawmpo_add_roles_for_privacy_options' );
}
function bawmpo_add_roles_for_privacy_options( $output ) {
	$label          = 'fr_' === substr( function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(), 0, 3 ) ? 'Quels rôles peuvent éditer cette page' : 'Which roles can edit this page';
	$roles          = new WP_Roles();
	$roles          = $roles->get_names();
	$roles          = array_map( 'translate_user_role', $roles );
	$roles_settings = get_option( 'roles_for_privacy_policy', [] );
	$output        .= '<input type="hidden" name="roles_for_privacy_policy" value="0">';
	$output        .= '<div class="bawmpo"><p><span id="bawmpo-title"><strong>' . $label . '</strong></span><br>';
	foreach ($roles as $value => $role) {
		$disabled = '';
		$checked = checked( isset( $roles_settings[ $value ] ), true, false );
		if ( 'administrator' === $value ) {
			$checked  = checked( true, true, false );
			$disabled = disabled( true, true, false );
		}
		$output .= '<label><input ' . $checked . ' ' . $disabled . 'type="checkbox" name="roles_for_privacy_policy[' . $value . ']" value="1"> ' . $role . '</label><br>';
	}
	$output .= '</p></div><br>';
	$output .= '<script lang="text/javascript">
		jQuery( document ).ready(function($) {
			var $ppp_html = $("table.tools-privacy-policy-page th:first").html();
			$("table.tools-privacy-policy-page th:first").html( $ppp_html + "<br><br>" + $("#bawmpo-title").html() );
			$("#bawmpo-title").remove();
		});
	</script>';
	return $output;
}

add_action( 'pre_update_option_wp_page_for_privacy_policy', 'bawmpo_auto_update_roles_for_privacy_policy_on_wp_page_for_privacy_policy' );
function bawmpo_auto_update_roles_for_privacy_policy_on_wp_page_for_privacy_policy( $value ) {
	if ( is_array( $_POST['roles_for_privacy_policy' ] ) || '0' === $_POST['roles_for_privacy_policy' ] ) {
		if ( '0' === $_POST['roles_for_privacy_policy' ] ) {
			delete_option( 'roles_for_privacy_policy' );
		} else {
			$allowed_roles = new WP_Roles();
			$allowed_roles = $allowed_roles->get_names();
			unset( $allowed_roles['administrator'] );
			$allowed_roles = array_intersect_key( $_POST['roles_for_privacy_policy' ], $allowed_roles );

			update_option( 'roles_for_privacy_policy', $allowed_roles );
		}
	}

	return $value;
}

add_filter( 'display_post_states', 'bawmpo_add_post_state_label', 10, 2 );
function bawmpo_add_post_state_label( $post_states, $post ) {
	if ( (int) get_option( 'wp_page_for_privacy_policy' ) === $post->ID ) {
		$post_states[] = __( 'Privacy Policy Page' );
	}
	return $post_states;
}
