<?php // (C) Copyright Bobbing Wide 2017

/** 
 * Class: spam_reg_check
 *
 * - Lists all users ( see wp user list )
 * - For each user in the list check if it's spam or not.
 * - Update registration accordingly
 */ 
class spam_reg_check {

	public $users;
	public $is_multisite = false;


	/**
	 * spam_reg_check constructor.
	 */
		public function __construct() {
		
		if ( ob_get_level() ) {
			echo ob_get_clean();
		}
		echo __METHOD__;
		echo time();
		echo PHP_EOL;
		flush();
		$this->is_multisite();
		$this->list_all_users();
		
		$this->summarize();
		//$this->display_users();
		$this->check_each_user();
		
		$this->list_all_users();
		$this->summarize();
	
	}
	
	function is_multisite() {
		$this->is_multisite = is_multisite();
	}

	/**
	 * Lists all the registered users in reverse registration order
	*/
	public function list_all_users() {
	
		$assoc_args['count_total'] = false;	 // May improve performance if pagination is not needed
		$assoc_args['orderby'] = "ID";
		$assoc_args['order'] = "ASC";
		//$assoc_args['number'] = 10;
		//$assoc_args['paged'] = 1;
		$assoc_args['meta_key'] = "spam_reg_check";
		$assoc_args['meta_compare'] = "NOT EXISTS";
		//print_r( $assoc_args );
		$this->users = get_users( $assoc_args );
		//print_r( $this->users );
	
	}

	/**
	 * We need to be able to detect and eliminate spam registrations
	 * 
	 * Let's check values of user_activation_key and status for administrators
	 *
	
	
            [data] => stdClass Object
                (
                    [ID] => 18
                    [user_login] => 3
                    [user_pass] => $P$BIeyoxeC2PpCRl/MVaThfd3SQf7.TR0
                    [user_nicename] => 3
                    [user_email] => three@bobbingwide.com
                    [user_url] => 
                    [user_registered] => 2015-10-08 18:05:16
                    [user_activation_key] => 1444327518:$P$BvilnxTI.cEekyq6uRYd3cF2zea/.51
                    [user_status] => 0
                    [display_name] => T Hree
                )

            [ID] => 18
            [caps] => Array
                (
                    [subscriber] => 1
                )

            [cap_key] => wp_capabilities
            [roles] => Array
                (
                    [0] => subscriber
                )

            [allcaps] => Array
                (
                    [read] => 1
                    [level_0] => 1
                    [subscriber] => 1
                )

            [filter] => 
	 */
	public function display_users() {
		foreach ( $this->users as $user ) {
		
			$this->display_user( $user );
			$this->get_user_meta( $user );
			
		}
	}
	
	/**
	 * Checks each user's spam status
	 *
	 * Updates the "spam_reg_check" meta data each time we perform the check.
	 
	 */
	public function check_each_user() {
		foreach ( $this->users as $user ) {
			if ( $user->data->user_status == "0" ) {
				if ( !$this->get_spam_reg_check( $user ) ) {
					$spam_reg = $this->check_user( $user );
					if ( $spam_reg ) {
						$this->mark_as_spammer( $user );
					} else {
						// Not a spammer.
					}
					$this->set_spam_reg_check( $user );
				}	
			}
		}
	}
	
	/** 
	 * Returns the spam reg check timestamp
	 */
	function get_spam_reg_check( $user ) {
		$spam_reg_check = get_user_meta( $user->ID, "spam_reg_check", true );
		if ( $spam_reg_check ) {
			$spam_reg_check = date( "Y-m-d H:i:s", $spam_reg_check );
		}
		return $spam_reg_check;
	}
	
	function set_spam_reg_check( $user ) {
		update_user_meta( $user->ID, "spam_reg_check", time() );
	}
	
	/**
	 * Display details of a user to enable us to determine if the user is a spammer
	 * 
	 * @param
	 */
	public function display_user( $user ) {
		$output_array = array();
		$output_array[] = $user->data->ID;
		$output_array[] = $user->data->user_email;
		$output_array[] = $user->data->display_name;
		$output_array[] = $user->data->user_url;
		$output_array[] = $user->data->user_activation_key;
		$output_array[] = $user->data->user_registered;
		$output_array[] = $user->data->user_status;
		
		if ( $this->is_multisite ) {
		
			$output_array[] = $user->data->spam;
			$output_array[] = $user->data->deleted;
		}
		$output_array[] = implode( " ", $user->roles );
		$output_array[] = $this->get_spam_reg_check( $user );
		$output = implode( PHP_EOL, $output_array );
		echo $output . PHP_EOL;
	}
	
	public function get_user_meta( $user ) {
		$user_meta = get_user_meta( $user->ID );
		//print_r( $user );
		return $user_meta;
	}
	
	/**
	 * Tests if the user is marked as a spam user
	 *
	 * Note: spam is only available for WPMS
	 */
	function is_spam_user( $user ) {
		$spammer = false;
		$spammer |= ( "0" !== $user->data->user_status );
		$spammer |= ( false !== strpos( $user->data->user_activation_key, ":spam" ) );
		$spammer |= ( 0 == $this->has_roles( $user ) );
		return $spammer;
	}	
	
	/**
	 * Determine if this is a spam registration
	 * 
	 * Pre-req:
	 * - status = "0"
	 * - has_roles = at least one
	 * 
	 * @param object $user 
	 * @return bool true if the user is a spammer
	 */
	function check_user( $user ) {
		$spammer = $this->is_spam_user( $user );
		if ( !$spammer ) {
			$spammer = $this->manual_check( $user );
		} else {
			// Already determined to be a spammer
		}
		return $spammer;
	}
	
	function manual_check( $user ) {
		echo PHP_EOL;
		$this->display_user( $user );
		
		$this->display_more( $user );
		$spammer = $this->ask( $user );
		return $spammer;
	}
	
	function has_roles( $user ) {
		$has_roles = count( $user->roles );
		return $has_roles;
	}
	
	function get_response() {
		$response = oikb_get_response( "Is this user a spammer?", true );
		$response = trim( $response );
		echo "You said:$response!" . PHP_EOL;
		return $response;
	}
	
	function ask( $user ) {
		$response = $this->get_response();
		if ( $response == "?" ) {
			$response = $this->ask_more( $user );
		}
		return( $response == "y" );
	}
	
	function ask_more( $user ) {
		$this->display_more( $user );
		$response = $this->get_response();
		return $response;
	}
	
	/**
	 * first_name
	 * last_name
	 * description
	 */
	
	function display_more( $user ) {
		$user_meta = $this->get_user_meta( $user );
		$this->display_relevant_user_meta( $user_meta );
	}
	
	function display_relevant_user_meta( $user_meta ) {
		foreach ( $user_meta as $key => $data ) {
			if ( array_key_exists( $key, array_flip( array( "first_name", "last_name", "description", "spam_checked", "city", "country", "dob", "sex" ) ) ) ) {
				if ( count( $data ) ) {
					$flat_data = implode( " ", $data );
				}
				echo PHP_EOL;
				echo $key;
				echo " ";
				echo $flat_data;
				echo PHP_EOL;
			}
		}	
	}
		
	
	
	/**
	 * Marks the user as a spammer
	 * 
	 * 
	 * Field       | Normal value | Spam value	   | Notes
	 * ----------- | ------------ | -----------    | ------
	 * user_status | 0            | 1						   | deprecated field?
	 * spam        | 0            | 1						   | WPMS only?
	 * deleted     | 0            | 0						   | WPMS only?
	 * user_activation_key | ""   | timestamp:spam | see below
	 * 
	 * 
	 * - user_activation_key is normally 
	 *   blank ( "" ) for an activated user,  
	 *   or a passwordMD5 field for one that's not yet confirmed
	 *   or time:passwordMD5 for a password reset
	 * Setting the timestamp part to the current time indicates when we marked the user as spammed.
	 * 
	 * We want to mark spam registrations as something else
	 * We also need to know when they were spam registered or spam checked
	 * We can do this by updating the user_activation_key to password expired format
	 * with the timestamp part set to the spam check date and the password reset part to :spam
	 *
	 */
	public function mark_as_spammer( $user ) {
		global $wpdb;
		$user_activation_key = time() . ':spam';
		$set_fields = array( 'user_status' => '1', 
												 'user_activation_key' => $user_activation_key,
											);
		if ( $this->is_multisite ) {
			$set_fields['spam'] = 1;
		}
		$wpdb->update( $wpdb->users, $set_fields, array( 'ID' => $user->ID ) );
		$user->set_role( "" );
		return;
	}
	
	public function summarize() {
		$spammers = 0;
		$tobechecked = 0;
		$ok = 0;
		
		$total = 0;
		
		foreach ( $this->users as $user ) {
			$spammer = $this->is_spam_user( $user );
			if ( $spammer ) {
				$spammers++;
			} else {
				$spam_reg_check = $this->get_spam_reg_check( $user );
				if ( $spam_reg_check ) {
					$ok++;
				} else {
					$tobechecked++;
				}	
			}
			$total++;
		}
		echo "Spammers: $spammers" . PHP_EOL;
		echo "To be checked: $tobechecked" . PHP_EOL;
		echo "OK: $ok" . PHP_EOL;
		echo "Total: $total" . PHP_EOL;
	}	
			
	
	

	
}