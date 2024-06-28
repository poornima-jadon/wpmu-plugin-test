<?php
/**
 * Google Auth Shortcode.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2023, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Endpoint;
use WP_REST_Server;

class Auth extends Endpoint {
	/**
	 * API endpoint for the current endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @var string $endpoint
	 */
	protected $endpoint = 'auth/auth-url';

	/**
	 * Register the routes for handling auth functionality.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function register_routes() {
		// TODO
		// Add a new Route to logout.

		// Route to get auth url.
		register_rest_route(
			$this->get_namespace(),
			$this->get_endpoint(),
			array(
				array(
					'methods' => 'GET',
					'callback' => array( $this, 'get_credentials' ),
					'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
							}
					
				),
			)
		);
			// Route to post auth url.
			register_rest_route(
				$this->get_namespace(),
				$this->get_endpoint(),
				array(
					array(
						'methods' => 'POST',
						'callback' => array( $this, 'wpmu_save_credentials' ),
						'args'    => array(
							'client_id'     => array(
								'required'    => true,
								'description' => __( 'The client ID from Google API project.', 'wpmudev-plugin-test' ),
								'type'        => 'string',
							),
							'client_secret' => array(
								'required'    => true,
								'description' => __( 'The client secret from Google API project.', 'wpmudev-plugin-test' ),
								'type'        => 'string',
							),
						),
						'permission_callback' => function () {
							return current_user_can( 'edit_posts' );
							}
						
					),
				)
			);
	  register_rest_route('wpmudev/v1', '/auth/redirect', array(
		'methods' => 'GET',
		'callback' => array( $this, 'get_google_auth_url' ),
		'permission_callback' => '__return_true'
		));

	
	  // confirm callback
	  register_rest_route('wpmudev/v1', '/auth/confirm', array(
        'methods' => 'GET',
        'callback' => array( $this, 'handle_oauth_callback' ),
		'permission_callback' => '__return_true'
      ));
	 
	}

	/**
	 * Save the client id and secret.
	 *
	 *
	 * @since 1.0.0
	 */
	public function get_credentials() {
        $settings = get_option('wpmudev_plugin_test_settings');
		$creds = array(
			'client_id' =>$settings['client_id'],
			'client_secret' =>$settings['client_secret'],
		);

		
		return new \WP_REST_Response($creds, 200 );

    }

	public function wpmu_save_credentials( \WP_REST_Request $request ) {
        $client_id = $request['client_id'];
        $client_secret = $request['client_secret'];

        // Validate client_id and client_secret
        if ( empty( $client_id ) || empty( $client_secret ) ) {
            return new \WP_REST_Response( array( 'error' => 'Missing client ID or client secret' ), 400 );
        }
		$creds = array(
			'client_id' =>$client_id,
			'client_secret' =>$client_secret,
		);

		update_option('wpmudev_plugin_test_settings',$creds);
		return new \WP_REST_Response( "success", 200 );

    }
	public function get_google_auth_url() {
		$redirect_uri = '/wp-json/wpmudev/v1/auth/confirm'; // Update with your actual callback URL
		$settings = get_option( 'wpmudev_plugin_test_settings' );
		$client_id = $settings['client_id']; 
		$auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(array(
			'response_type' => 'code',
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'scope' => 'email',
		));
		$auth_url = '<a href='.$auth_url.'>Click Continue</a>';
		return rest_ensure_response(array('authUrl' => $auth_url));
	}
	public function handle_oauth_callback(\WP_REST_Request $request) {
		 $code = $request->get_param('code');
		 $settings = get_option( 'wpmudev_plugin_test_settings' );
		 $client_id = $settings['client_id'];
		 $client_secret = $settings['client_secret'];
		 $redirect_uri = '/wp-json/wpmudev/v1/auth/confirm';
		 // Exchange authorization code for access token
		 $token_url = 'https://oauth2.googleapis.com/token';
		 $token_params = array(
			 'code' => $code,
			 'client_id' => $client_id,
			 'client_secret' => $client_secret,
			 'redirect_uri' => $redirect_uri,
			 'grant_type' => 'authorization_code',
		 );
	 
		 $response = wp_remote_post($token_url, array(
			 'body' => $token_params,
		 ));
	 
		 if (is_wp_error($response)) {
			 // Handle error
			 return $response;
		 }
	 
		 $token_data = json_decode(wp_remote_retrieve_body($response), true);
		 $access_token = $token_data['access_token'];
	 
		 // Fetch user's email address from Google
		 $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
		 $user_info_params = array(
			 'access_token' => $access_token,
		 );
	 
		 $user_info_response = wp_remote_get(add_query_arg($user_info_params, $user_info_url));
	 
		 if (is_wp_error($user_info_response)) {
			 // Handle error
			 return $user_info_response;
		 }
	 
		 $user_info = json_decode(wp_remote_retrieve_body($user_info_response), true);
		 $email = $user_info['email']; 
		// Check if user exists
		$user = get_user_by('email', $email);
		
		if ($user) {
			// User exists, log them in
			wp_set_current_user($user->ID);
			wp_set_auth_cookie($user->ID);
			
			// Redirect to admin or home page
			wp_redirect(home_url());
			exit;
		} else {
			// User doesn't exist, create new user with a generated password
			$generated_password = wp_generate_password();
			$user_id = wp_create_user($email, $generated_password, $email);
			
			if (!is_wp_error($user_id)) {
				// Log in the newly created user
				wp_set_current_user($user_id);
				wp_set_auth_cookie($user_id);
				
				// Redirect to admin or home page
				wp_redirect(home_url());
				exit;
			} else {
				// Handle error creating user
				wp_redirect(wp_login_url());
				exit;
			}
		}
	}

}
