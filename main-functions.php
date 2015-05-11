<?php 
/*
Plugin Name: Jeba ajax login/register
Plugin URI: http://prowpexpert.com/
Description: This plugin to use ajax login register in your wordpress site.
Author: Md Jahed
Author URI: http://prowpexpert.com/
Version: 1.1.0
*/

/* Adding Latest jQuery from Wordpress */
function jeba_ajax_plugin_wp() {
	wp_enqueue_script('jquery');
}
add_action('init', 'jeba_ajax_plugin_wp');

/*Some Set-up*/
define('jeba_ajax_PLUGIN_WP', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );


function jeba_ajax_auth_init(){	
	wp_register_style( 'ajax-auth-style', jeba_ajax_PLUGIN_WP.'css/ajax-auth-style.css' );
	wp_enqueue_style('ajax-auth-style');
	
	wp_register_script('validate-script', jeba_ajax_PLUGIN_WP.'js/jquery.validate.js', array('jquery') ); 
    wp_enqueue_script('validate-script');

    wp_register_script('ajax-auth-script', jeba_ajax_PLUGIN_WP.'js/ajax-auth-script.js', array('jquery') ); 
    wp_enqueue_script('ajax-auth-script');

    wp_localize_script( 'ajax-auth-script', 'ajax_auth_object', array( 
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'redirecturl' => home_url(),
        'loadingmessage' => __('Sending user info, please wait...')
    ));

    // Enable the user with no privileges to run jeba_ajax_login() in AJAX
    add_action( 'wp_ajax_nopriv_ajaxlogin', 'jeba_ajax_login' );
	// Enable the user with no privileges to run ajax_register() in AJAX
	add_action( 'wp_ajax_nopriv_ajaxregister', 'ajax_register' );
}

// Execute the action only if the user isn't logged in




  add_action('init', 'jeba_ajax_auth_init');
  
  
function jeba_ajax_login(){

    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-login-nonce', 'security' );

    // Nonce is checked, get the POST data and sign user on
  	// Call auth_user_login
	auth_user_login($_POST['username'], $_POST['password'], 'Login'); 
	
    die();
}

function ajax_register(){

    // First check the nonce, if it fails the function will break
    check_ajax_referer( 'ajax-register-nonce', 'security' );
		
    // Nonce is checked, get the POST data and sign user on
    $info = array();
  	$info['user_nicename'] = $info['nickname'] = $info['display_name'] = $info['first_name'] = $info['user_login'] = sanitize_user($_POST['username']) ;
    $info['user_pass'] = sanitize_text_field($_POST['password']);
	$info['user_email'] = sanitize_email( $_POST['email']);
	
	// Register the user
    $user_register = wp_insert_user( $info );
 	if ( is_wp_error($user_register) ){	
		$error  = $user_register->get_error_codes()	;
		
		if(in_array('empty_user_login', $error))
			echo json_encode(array('loggedin'=>false, 'message'=>__($user_register->get_error_message('empty_user_login'))));
		elseif(in_array('existing_user_login',$error))
			echo json_encode(array('loggedin'=>false, 'message'=>__('This username is already registered.')));
		elseif(in_array('existing_user_email',$error))
        echo json_encode(array('loggedin'=>false, 'message'=>__('This email address is already registered.')));
    } else {
	  auth_user_login($info['nickname'], $info['user_pass'], 'Registration');       
    }

    die();
}

function auth_user_login($user_login, $password, $login)
{
	$info = array();
    $info['user_login'] = $user_login;
    $info['user_password'] = $password;
    $info['remember'] = true;
	
	$user_signon = wp_signon( $info, false );
    if ( is_wp_error($user_signon) ){
		echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));
    } else {
		wp_set_current_user($user_signon->ID); 
        echo json_encode(array('loggedin'=>true, 'message'=>__($login.' successful, redirecting...')));
    }
	
	die();
}


function jeba_get_data_ajax_settings() {?>

    
   <form style="margin-top:30px;" id="login" class="ajax-auth" action="login" method="post">
    <h3>New to site? <a id="pop_signup" href="">Create an Account</a></h3>
    <hr />
    <h1>Login</h1>
    <p class="status"></p>  
    <?php wp_nonce_field('ajax-login-nonce', 'security'); ?>  
    <label for="username">Username</label>
    <input id="username" type="text" class="required" name="username">
    <label for="password">Password</label>
    <input id="password" type="password" class="required" name="password">
    <a class="text-link" href="<?php
echo wp_lostpassword_url(); ?>">Lost password?</a>
    <input class="submit_button" type="submit" value="LOGIN">
	<a class="close" href="">(close)</a>    
</form>

<form id="register" class="ajax-auth"  action="register" method="post">
	<h3>Already have an account? <a id="pop_login"  href="">Login</a></h3>
    <hr />
    <h1>Signup</h1>
    <p class="status"></p>
    <?php wp_nonce_field('ajax-register-nonce', 'signonsecurity'); ?>         
    <label for="signonname">Username</label>
    <input id="signonname" type="text" name="signonname" class="required">
    <label for="email">Email</label>
    <input id="email" type="text" class="required email" name="email">
    <label for="signonpassword">Password</label>
    <input id="signonpassword" type="password" class="required" name="signonpassword" >
    <label for="password2">Confirm Password</label>
    <input type="password" id="password2" class="required" name="password2">
    <input class="submit_button" type="submit" value="SIGNUP">
    <a class="close" href="">(close)</a>    
</form>


<?php

}
add_action('wp_footer', 'jeba_get_data_ajax_settings');

add_shortcode('jeba_login', 'jeba_get_data_from_settings');
function jeba_get_data_from_settings() {?>
<?php if (is_user_logged_in()) { ?>
    	<a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a>
<?php } else { get_template_part('ajax', 'auth'); ?>            	
        <a class="login_button" id="show_login" href="">Login</a>
        <a class="login_button" id="show_signup" href="">Signup</a>
<?php } ?>
<?php
}

?>