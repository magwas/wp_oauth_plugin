<?php
	/*
		Plugin Name: Edemo SSO authentication
		Plugin URI: 
		Description: Allows you connect to the Edemo SSO server, and autenticate the users, who acting on your site
		Version: 0.0.1
		Author: Claymanus
		Author URI:
	*/

class eDemoSSO {
 
	const    SSO_DOMAIN = 'sso.edemokraciagep.org';
	const SSO_TOKEN_URI = '81.7.7.18/v1/oauth2/token';
	const  SSO_AUTH_URI = '81.7.7.18/v1/oauth2/auth';
	const  SSO_USER_URI = '81.7.7.18/v1/users/me';
	const     QUERY_VAR = 'sso_callback';
	const     USER_ROLE = 'eDemo_SSO_role';
	const  CALLBACK_URI = 'sso_callback';
	const      USERMETA = 'eDemoSSO_ID';
	const  WP_REDIR_VAR = 'wp_redirect';

	public $callbackURL;
	public $error_message;
	public $auth_message;
	private $appkey;
	private $secret;
	private $sslverify;
	
	function __construct() {

    add_option('eDemoSSO_appkey', '', '', 'yes');
    add_option('eDemoSSO_secret', '', '', 'yes');
    add_option('eDemoSSO_appname', '', '', 'yes');
    add_option('eDemoSSO_sslverify', '', '', 'yes');
    
    $this->callbackURL = get_site_url( "", "", "https" )."/".self::CALLBACK_URI;
    $this->appkey = get_option('eDemoSSO_appkey');
    $this->secret = get_option('eDemoSSO_secret');
    $this->sslverify = get_option('eDemoSSO_sslverify');
        
    add_action( 'generate_rewrite_rules', array( $this, 'add_rewrite_rules' ) );
    add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

    add_filter( 'query_vars', array( $this, 'query_vars' ) );
    add_filter( 'the_content', array( $this, 'the_content_filter' ) );
  
    register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
    register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
    add_action( 'parse_request', array( $this, 'parse_request' ) );
    add_shortcode('SSOsignit', array( $this, 'sign_it' ) );	
    add_action('admin_menu', array( $this, 'addAdminPage' ) );
	}
	
	//
	// Options/admin panel
	//

	// Add page to options menu.
	function addAdminPage() 
	{
	  // Add a new submenu under Options:
		add_options_page('eDemo SSO Options', 'eDemo SSO', 'manage_options', 'edemosso', array( $this, 'displayAdminPage'));
	}

	// Display the admin page.
	function displayAdminPage() {
		
		if (isset($_POST['edemosso_update'])) {
//			check_admin_referer();    // EZT MAJD MEG KELLENE NÉZNI !!!!!

			// Update SSO application 
			$this->sslverify = isset($_POST['EdemoSSO_sslverify']);
			$this->appkey    = $_POST['EdemoSSO_appkey'];
      $this->secret    = $_POST['EdemoSSO_secret'];
      $this->appname   = $_POST['EdemoSSO_appname'];
			update_option( 'eDemoSSO_appkey'   , $this->appkey   );
			update_option( 'eDemoSSO_secret'   , $this->secret   );
			update_option( 'eDemoSSO_appname'  , $this->appname  );
			update_option( 'eDemoSSO_sslverify', $this->sslverify);

			// echo message updated
			echo "<div class='updated fade'><p>Options updated.</p></div>";
		}		
		?>
		<div class="wrap">

			<h2><?= __( 'eDemo SSO Authentication Options' ) ?></h2>
			<form method="post">
				<fieldset class='options'>
					<table class="editform" cellspacing="2" cellpadding="5" width="100%">
						<tr>
							<th width="30%" valign="top" style="padding-top: 10px;">
								<label for="EdemoSSO_appname"><?= __( 'Application name:' ) ?></label>
							</th>
							<td>
								<input type='text' size='16' maxlength='30' name='EdemoSSO_appname' id='EdemoSSO_appname' value='<?= get_option('eDemoSSO_appname'); ?>' />
								<?= __( 'Used for registering the application' ) ?>
							</td>
						</tr>
						<tr>
							<th width="30%" valign="top" style="padding-top: 10px;">
								<label for="EdemoSSO_appkey"><?= __( 'Application key:' ) ?></label>
							</th>
							<td>
								<input type='text' size='40' maxlength='40' name='EdemoSSO_appkey' id='EdemoSSO_appkey' value='<?= $this->appkey; ?>' />
								<?= __( 'Application key.' ) ?>
							</td>
						</tr>
						<tr>
							<th width="30%" valign="top" style="padding-top: 10px;">
								<label for="EdemoSSO_secret"><?= __( 'Application secret:' ) ?></label>
							</th>
							<td>
								<input type='text' size='40' maxlength='40' name='EdemoSSO_secret' id='EdemoSSO_secret' value='<?= $this->secret; ?>' />
								<?= __( 'Application secret.' ) ?>
							</td>
						</tr>
						<tr>
							<th width="30%" valign="top" style="padding-top: 10px;">
								<label for="EdemoSSO_sslverify"><?= __( 'Allow verify ssl certificates:' ) ?></label>
							</th>
							<td>
								<input type='checkbox' name='EdemoSSO_sslverify' id='EdemoSSO_sslverify' <?= (($this->sslverify)?'checked':''); ?> />
								<?= __( "If this set, the ssl certificates will be verified during the communication with sso server. Uncheck is recommended if your site has no cert, or the issuer isn't validated." ) ?>
							</td>
						</tr>
						<tr>
							<th>
								<label for="eDemoSSO_callbackURI"><?= __( 'eDemo_SSO callback URL:' ) ?></label>
							</th>
							<td>
								<?= $this->callbackURL ?>
							</td>
						</tr>
						<tr>
							<td colspan="2">
							<p class="submit"><input type='submit' name='edemosso_update' value='<?= __( 'Update Options' ) ?>' /></p>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</div>
		<?php
	}
	
	//
	// Actual functionality
	//


	function sign_it()
	{
		
	}

	//
	// Hooks
	//


	function add_rewrite_rules() {
		global $wp_rewrite;
		$rules = array( self::CALLBACK_URI.'(.+?)$' => 'index.php$matches[1]&'.self::CALLBACK_URI.'=true',
                    self::CALLBACK_URI.'$'      => 'index.php?'.self::CALLBACK_URI.'=true&'  );
	$wp_rewrite->rules = $rules + (array)$wp_rewrite->rules;
	}

	function plugin_activation() {

		// Adding new user role "eDemo_SSO_role" only with "read" capability 
	  
		add_role( self::USER_ROLE, 'eDemo_SSO user', array( 'read' => true, 'level_0' => true ) );

		// Adding new rewrite rules     
    
		global $wp_rewrite;
		$wp_rewrite->flush_rules(); // force call to generate_rewrite_rules()
	}
	
	function plugin_deactivation() {
	
		// Removing SSO rewrite rules  
		remove_action( 'generate_rewrite_rules', array( $this, 'rewrite_rules' ) );
		global $wp_rewrite;
		$wp_rewrite->flush_rules(); // force call to generate_rewrite_rules()
	}

	function  pre_get_posts($query) {
		$query->is_home = FALSE;
	}

  function parse_request( &$wp )
  {
    if ( array_key_exists( self::QUERY_VAR, $wp->query_vars ) ) {
        
        $this->auth_message=$this->callback_process();
        if (isset($_GET[self::WP_REDIR_VAR])) wp_redirect($_GET[self::WP_REDIR_VAR.'']);
        else wp_redirect('/');
        exit();
    }
    return;
  }	
  
  // auth process massages putting before displaying the title
  function the_content_filter($content) {
    echo "<div class='updated fade'><p>Hiba:".$this->error_message."</p></div>";
    return $content;
  }

	function   query_vars($public_query_vars) { 
		array_push( $public_query_vars, self::QUERY_VAR );
		return $public_query_vars;
	}
  
  function requestToken( $code ) {
  
    $response = wp_remote_post( 'https://'.self::SSO_TOKEN_URI, array(
                 'method' => 'POST',
                'timeout' => 45,
            'redirection' => 10,
	          'httpversion' => '1.0',
	             'blocking' => true,
	              'headers' => array(),
	                 'body' => array( 'code' => $code,
				                      'grant_type' => 'authorization_code',
				                       'client_id' => $this->appkey,
			                     'client_secret' => $this->secret,
			                      'redirect_uri' => $this->callbackURL ),
	              'cookies' => array(),
	            'sslverify' => $this->sslverify ) );

    if ( is_wp_error( $response )  ) {
      $this->error_message = $response->get_error_message();
      return false;
    }
    else {
      $body = json_decode( $response['body'], true );
      if ( isset( $body['error'] ) ) {
        $this->error_message = "The SSO-server's response: ". $body['error'];
        return false;
      }
      else return $body;
    }
  }
  
  function requestUserData( $access_token ) {
  
    $response = wp_remote_get( 'https://'.self::SSO_USER_URI, array(
                    'timeout' => 45,
                'redirection' => 10,
                'httpversion' => '1.0',
                   'blocking' => true,
                    'headers' => array( 'Authorization' => 'Bearer '.$access_token ),
                    'cookies' => array(),
                  'sslverify' => $this->sslverify ) );
    if ( is_wp_error( $response ) ) {
      $this->error_message = $response->get_error_message();
      return false;
    }
    else return json_decode( $response['body'], true );
  }
  
  function registerUser($user_data){
    // updating if user  alredy exist with this email 
     if ( $ssoUser=get_users(array('search' => $user_data['email'])) ){
        $user=$ssoUser[0]->data;
//        echo 'found by email: <pre>';
//        print_r ($user);
//        echo '</pre>';
        update_user_meta( $user->ID, self::USERMETA, $user_data['userid'] );
//        echo '<p>user updated </p>';
     }
     // registering new user
     else {
        $display_name=explode('@',$user_data['email']);
        $user_id = wp_insert_user( array( 'user_login' => $user_data['userid'],
                                          'user_email' => $user_data['email'],
                                          'display_name' => $display_name[0],
                                          'role' => self::USER_ROLE ));

//On success
        if( !is_wp_error($user_id) ) {
        update_user_meta( $user_id, self::USERMETA, $user_data['userid'] );
//        echo "User created : ". $user_id;
        }
        else $this->error_message=$user_id->get_error_message();     
     }
     //loggin in
  }
  
  function signinUser($user) {
    wp_set_current_user( $user->ID, $user->user_login );
    wp_set_auth_cookie( $user->ID );
    do_action( 'wp_login', $user->user_login );
  }
  
  function callback_process() {

    if (isset($_GET['code'])) {
      if ( $token = $this->requestToken( $_GET['code'] ) ) {
//        echo "The SSO-server's response to the token request <pre>";
//        print_r ($token);
//        echo '</pre>
//        <p>Trying to get user data</p>';
        if ( $user_data = $this->requestUserData( $token['access_token'] ) ) {
//         echo '<p>User data:</p>';
//          echo '<pre>';
//          print_r ( $user_data );
//          echo '</pre>';
//          echo '<p>Searching for user according SSO-ID: ';
          if ( $ssoUser=get_users(array('meta_key' => self::USERMETA, 'meta_value' => $user_data['userid'])) ) {
           $user=$ssoUser[0]->data;
//           echo "Succes </p><pre>";
//           print_r ($ssoUser);
//           echo "</pre>";
          }
          else {
//            echo '<p>Registering new user: ';
            $user=$this->registerUser($user_data) ;
          }
          if ($user) {
            $this->signinUser($user);
           $this->error_message= __('You are signed in');
          }
        }
      }
    }
    else $this->error_message = "Invalid page request - missing code";
    return $this->error_message;
  }

  
} // end of class declaration
	
$eDemoSSO = new eDemoSSO();
?>