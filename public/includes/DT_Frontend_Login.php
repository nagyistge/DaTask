<?php

/**
 * Frontend Login system
 *
 * @package   DaTask
 * @author    Mte90 <mte90net@gmail.com>
 * @license   GPL-2.0+
 * @link      http://mte90.net
 * @copyright 2015 GPL
 */
class DT_Frontend_Login {

  protected $options;

  /**
   * Initialize the class with all the hooks
   *
   * @since     1.0.0
   */
  public function __construct() {
    // Initialize the fake page for the login system
    new Fake_Page(
		array(
	  'slug' => 'login',
	  'post_title' => __( 'Login', DT_TEXTDOMAIN ),
	  'post_content' => 'content'
		)
    );
    new Fake_Page(
		array(
	  'slug' => 'logout',
	  'post_title' => __( 'Logout', DT_TEXTDOMAIN ),
	  'post_content' => 'content'
		)
    );
    new Fake_Page(
		array(
	  'slug' => 'profile',
	  'post_title' => __( 'Edit Profile', DT_TEXTDOMAIN ),
	  'post_content' => 'content'
		)
    );
    add_action( 'login_init', array( $this, 'frontend_login' ) );
    add_action( 'template_redirect', array( $this, 'frontend_login_redirect' ) );
    add_action( 'lostpassword_post', array( $this, 'frontend_reset_password' ) );
    add_action( 'validate_password_reset', array( $this, 'frontend_validate_password_reset', 10, 2 ) );
    add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
    add_action( 'admin_init', array( $this, 'prevent_access_backend' ) );
    // add_filter( 'registration_errors', array( $this, 'registration_redirect' ), 10, 3 );
    $this->options = get_option( DT_TEXTDOMAIN . '-settings' );

    if ( isset( $this->options[ 'disable_adminbar' ] ) && $this->options[ 'disable_adminbar' ] === 'on' ) {
	add_action( 'after_setup_theme', array( $this, 'remove_admin_bar' ) );
    }
    if ( isset( $this->options[ 'redirect_registration' ] ) && !empty( $this->options[ 'redirect_registration' ] ) ) {
	add_filter( 'registration_redirect', array( $this, 'redirect_page' ) );
    }

    add_filter( 'the_content', array( $this, 'login_page' ) );
    // Switch login to logout for logged users
    add_filter( 'wp_nav_menu_objects', array( $this, 'login_to_logout' ) );
  }

  /**
   * Frontend login
   *
   * @since  1.0.0
   *
   * @return void 
   */
  public function frontend_login() {
    $action = isset( $_REQUEST[ 'action' ] ) ? $_REQUEST[ 'action' ] : 'login';
    if ( isset( $_POST[ 'wp-submit' ] ) ) {
	$action = 'post-data';
    } else if ( isset( $_GET[ 'reauth' ] ) ) {
	$action = 'reauth';
    }
    // Redirect to change password form
    if ( $action === 'rp' || $action === 'resetpass' ) {
	if ( isset( $_GET[ 'key' ] ) && isset( $_GET[ 'login' ] ) ) {
	  $rp_path = wp_unslash( home_url( '/login/' ) );
	  $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
	  $value = sprintf( '%s:%s', wp_unslash( $_GET[ 'login' ] ), wp_unslash( $_GET[ 'key' ] ) );
	  setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
	}

	wp_safe_redirect( home_url( '/login/?action=resetpass' ) );
	exit;
    }
    // Redirect from wrong key when resetting password
    if ( $action === 'lostpassword' && isset( $_GET[ 'error' ] ) && ( $_GET[ 'error' ] === 'expiredkey' || $_GET[ 'error' ] === 'invalidkey' ) ) {
	wp_safe_redirect( home_url( '/login/?action=forgot&failed=wrongkey' ) );
	exit;
    }
    if (
		$action === 'post-data' || // Don't mess with POST requests
		$action === 'reauth' || // Need to reauthorize
		$action === 'logout'  // User is logging out
    ) {
	return null;
    }
    wp_safe_redirect( home_url( '/login/' ) );
    exit;
  }

  /**
   * Frontend redirect when logged/not logged
   *
   * @since    1.0.0
   */
  public function frontend_login_redirect() {
    if ( is_page( 'login' ) && is_user_logged_in() ) {
	$current_user = wp_get_current_user();
	wp_safe_redirect( home_url( '/author/' . $current_user->user_login ) );
	exit();
    } elseif ( is_page( 'logout' ) && is_user_logged_in() ) {
	wp_logout();
	wp_safe_redirect( home_url() );
	exit();
    }
  }

  /**
   *  Prevent access in administration for not admin user
   *
   * @since    1.0.0
   */
  public function prevent_access_backend() {
    if ( current_user_can( 'subscriber' ) && !defined( 'DOING_AJAX' ) ) {
	$current_user = wp_get_current_user();
	wp_safe_redirect( home_url( '/author/' . $current_user->user_login ) );
	exit;
    }
  }

  /**
   *  Redirect on registration
   *
   * @since    1.0.0
   *
   * @param object $errors Error generated from WordPress.
   * @param string $sanitized_user_login The user login sanitized.
   * @param string $user_email The email of the user.
   *
   * @return object $errors
   */
  public function registration_redirect( $errors, $sanitized_user_login, $user_email ) {
    if ( !empty( $errors->errors ) ) {
	if ( isset( $errors->errors[ 'username_exists' ] ) ) {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=username_exists' );
	} elseif ( isset( $errors->errors[ 'email_exists' ] ) ) {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=email_exists' );
	} elseif ( isset( $errors->errors[ 'invalid_username' ] ) ) {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=invalid_username' );
	} elseif ( isset( $errors->errors[ 'invalid_email' ] ) ) {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=invalid_email' );
	} elseif ( isset( $errors->errors[ 'empty_username' ] ) || isset( $errors->errors[ 'empty_email' ] ) ) {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=empty' );
	} else {
	  wp_safe_redirect( home_url( '/login/' ) . '?action=register&failed=generic' );
	}
	exit;
    }
    return $errors;
  }

  /**
   * Redirect after login
   *
   * @since    1.0.0
   *
   * @param string $redirect_to URL.
   * @param string $url another url.
   * @param object $user The WP User object.
   */
  public function login_redirect( $redirect_to, $url, $user ) {
    if ( !isset( $user->errors ) ) {
	return $redirect_to;
    }
    wp_safe_redirect( home_url( '/login/' ) . '?action=login&failed=1' );
    exit;
  }

  /**
   * Reset password in the frontend
   *
   * @since    1.0.0
   */
  public function frontend_reset_password() {
    $user_data = '';
    if ( !empty( $_POST[ 'user_login' ] ) ) {
	if ( strpos( $_POST[ 'user_login' ], '@' ) ) {
	  $user_data = get_user_by( 'email', trim( $_POST[ 'user_login' ] ) );
	} else {
	  $user_data = get_user_by( 'login', trim( $_POST[ 'user_login' ] ) );
	}
    }
    if ( empty( $user_data ) ) {
	wp_safe_redirect( home_url( '/login/' ) . '?action=forgot&failed=1' );
	exit;
    }
  }

  /**
   * Validate the password in frontend
   *
   * @since    1.0.0
   *
   * @param object $errors Error object.
   * @param object $user The WP User object.
   */
  public function frontend_validate_password_reset( $errors, $user ) {
    // Passwords don't match
    if ( $errors->get_error_code() ) {
	wp_safe_redirect( home_url( '/login/?action=resetpass&failed=nomatch' ) );
	exit;
    }
    // wp-login already checked if the password is valid, so no further check is needed
    if ( !empty( $_POST[ 'pass1' ] ) ) {
	reset_password( $user, $_POST[ 'pass1' ] );
	wp_safe_redirect( home_url( '/login/?action=resetpass&success=1' ) );
	exit;
    }
    // Redirect to change password form
    wp_safe_redirect( home_url( '/login/?action=resetpass' ) );
    exit;
  }

  /**
   * Load login page
   *
   * @since    1.0.0
   *
   * @param string $content HTML from WordPress.
   * @return string The HTML of the login page
   */
  public function login_page( $content ) {
    // Is a filter, so return the template code!
    if ( is_page( 'login' ) ) {
	if ( !is_user_logged_in() ) {
	  ob_start();
	  wpbp_get_template_part( DT_TEXTDOMAIN, 'log', 'in', true );
	  $template = ob_get_contents();
	  ob_end_clean();
	  return $template;
	}
    } elseif ( is_page( 'profile' ) ) {
	if ( is_user_logged_in() ) {
	  if ( !function_exists( 'get_user_to_edit' ) ) {
	    include_once(ABSPATH . '/wp-admin/includes/user.php');
	    require_once( ABSPATH . 'wp-admin/includes/misc.php' );
	  }
	  if ( !(function_exists( '_wp_get_user_contactmethods' )) ) {
	    require_once(ABSPATH . '/wp-includes/registration.php');
	  }
	  if ( isset( $_POST[ 'user_id' ] ) ) {
	    // Hide header information in case of error
	    error_reporting( 0 );
	    $current_user = wp_get_current_user();
	    $user_id = $current_user->ID;
	    $errors = new WP_Error();
	    do_action( 'personal_options_update', $user_id );
	    if ( !isset( $errors ) || ( isset( $errors ) && is_object( $errors ) && false === $errors->get_error_codes() ) ) {
		$errors = edit_user( $user_id );
	    }
	  }
	  ob_start();
	  wpbp_get_template_part( DT_TEXTDOMAIN, 'user', 'edit', true );
	  $template = ob_get_contents();
	  ob_end_clean();
	  return $template;
	} else {
	  wp_safe_redirect( home_url( '/login/' ) );
	}
    } else {
	return $content;
    }
  }

  /**
   * Hide the admin bar in frontend for not admin user
   *
   * @since    1.0.0
   */
  public function remove_admin_bar() {
    if ( !current_user_can( 'manage_options' ) ) {
	show_admin_bar( false );
    }
  }

  /**
   * Transform Login to Logout for logged users
   *
   * @param  array $items Menu.
   * @return  $items array Menu.
   * @since    1.0.0
   */
  function login_to_logout( $items ) {
    if ( is_user_logged_in() ) {
	foreach ( $items as $page => $value ) {
	  if ( $items[ $page ]->post_name === 'login' ) {
	    $items[ $page ]->post_name = 'logout';
	    $items[ $page ]->post_title = __( 'Logout', DT_TEXTDOMAIN );
	    $items[ $page ]->title = __( 'Logout', DT_TEXTDOMAIN );
	    $items[ $page ]->url = get_site_url() . '/logout';
	  }
	}
    }
    return $items;
  }

  /**
   * Redirect after login
   *
   * @param string $registration_redirect Default link.
   * @return string
   */
  public function redirect_page( $registration_redirect ) {
    return get_page_link( $this->options[ 'redirect_registration' ] );
  }

}

new DT_Frontend_Login();
