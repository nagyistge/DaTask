<?php

/**
 * DT_Frontend_Profile
 * Frontend Profile page
 *
 * @package   DaTask
 * @author    Mte90 <mte90net@gmail.com>
 * @license   GPL-2.0+
 * @link      http://mte90.net
 * @copyright 2015 GPL
 */

class DT_Frontend_Profile {

	/**
	 * Initialize the class with all the hooks
	 *
	 * @since     1.0.0
	 */
	public function __construct() {
		$plugin = DaTask::get_instance();
		// Create Fake Page for members profile
		new Fake_Page(
			array(
		    'slug' => 'member',
		    'post_title' => __( 'Your profile', $plugin->get_plugin_slug() ),
		    'post_content' => 'content'
			)
		);
		add_filter( 'query_vars', array( $this, 'add_member_permalink' ) );
		add_filter( 'init', array( $this, 'rewrite_rule' ) );
		add_action( 'template_redirect', array( $this, 'userprofile_template' ) );
		add_filter( 'wp_title', array( $this, 'member_wp_title' ), 10, 3 );
		add_filter( 'the_title', array( $this, 'member_title' ), 10, 2 );
	}
	
	/**
	 * Add the rewrite permalink for member
	 *
	 * @since    1.0.0
	 * 
	 * @param string $vars The permalinks.
	 * 
	 * @return array $vars The permalinks.
	 */
	public function add_member_permalink( $vars ) {
		$vars[] = 'member';
		return $vars;
	}

	/**
	 * Add the rewrite permalink for member
	 *
	 * @since    1.0.0
	 */
	public function rewrite_rule() {
		add_rewrite_tag( '%member%', '([^&]+)' );
		add_rewrite_rule(
			'^member/([^/]*)/?', 'index.php?member=$matches[1]', 'top'
		);
	}

	/**
	 * Include the template for the profile page
	 *
	 * @since    1.0.0
	 */
	public function userprofile_template() {
		global $wp_query;
		if ( array_key_exists( 'member', $wp_query->query_vars ) ) {
			if ( get_user_of_profile() !== NULL ) {
				dt_get_template_part( 'user', 'profile', true );
				exit;
			} else {
				$wp_query->set_404();
			}
		} elseif ( (isset( $wp_query->query[ 'name' ] ) && $wp_query->query[ 'name' ] === 'member') || (isset( $wp_query->query[ 'pagename' ] ) && $wp_query->query[ 'pagename' ] === 'member') ) {
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				wp_redirect( home_url( '/member/' . $current_user->user_login ) );
				exit;
			} else {
				wp_redirect( home_url( '/login/' ) );
			}
		}
	}

	/**
	 * Add the head title for the member page
	 *
	 * @since    1.0.0
	 * 
	 * @param string $title       Title of the page.
	 * @param string $sep         Separator for the title.
	 * @param string $seplocation Another separator.
	 * 
	 * @return string $title Title of the page
	 */
	public function member_wp_title( $title, $sep, $seplocation ) {
		$plugin = DaTask::get_instance();
		global $wp_query;
		if ( array_key_exists( 'member', $wp_query->query_vars ) ) {
			if ( get_user_of_profile() !== NULL ) {
				$page = sprintf( __( "%s's Profile", $plugin->get_plugin_slug() ), get_user_of_profile() );

				return $page . ' ' . $sep . $title;
			}
		} elseif ( (isset( $wp_query->query[ 'name' ] ) && $wp_query->query[ 'name' ] === 'member') || (isset( $wp_query->query[ 'pagename' ] ) && $wp_query->query[ 'pagename' ] === 'member') ) {
			return __( 'Your profile', $plugin->get_plugin_slug() ) . ' ' . $sep;
		} else {
			return $title;
		}
	}

	/**
	 * Add the title for the member page
	 *
	 * @since    1.0.0
	 * 
	 * @param string  $title Title of the page.
	 * @param integer $id    ID of the page.
	 * 
	 * @return string $title Title of the page
	 */
	public function member_title( $title, $id ) {
		$plugin = DaTask::get_instance();
		global $wp_query;
		if ( (isset( $wp_query->query[ 'name' ] ) && $wp_query->query[ 'name' ] === 'member') || (isset( $wp_query->query[ 'pagename' ] ) && $wp_query->query[ 'pagename' ] === 'member') ) {
			return __( 'Your profile', $plugin->get_plugin_slug() );
		} else {
			return $title;
		}
	}
}

new DT_Frontend_Profile();
