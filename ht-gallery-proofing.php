<?php
/*
*	Plugin Name: Heroic Gallery Proofing
*	Plugin URI:  http://wordpress.org/plugins/hero-themes-gallery-proofing/
*	Description: An extension to Heroic Gallery Manager that adds protection and proofing to your Heroic Galleries
*	Author: Hero Themes
*	Version: 1.8
*	Author URI: http://www.herothemes.com/
*	Text Domain: ht-gallery-proofing
*/


if( !class_exists( 'HT_Gallery_Proofing' ) ){
	class HT_Gallery_Proofing {
		//Constructor
		function __construct(){
			load_plugin_textdomain('ht-gallery-proofing', false, basename( dirname( __FILE__ ) ) . '/languages' );
			//actions 
			add_action( 'add_meta_boxes', array( $this, 'add_protection_metabox' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_proofing_metabox' ) );
			add_action( 'save_post', array( $this, 'save_gallery_post_proofing' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_ht_gallery_proofing_scripts_and_styles' ) );
			add_action( 'admin_notices', array( $this, 'ht_gallery_manager_required_notice') );
			
			//filters
			//filter to allow for password protection of custom fields
			add_filter('get_post_metadata', array( $this, 'ht_gallery_get_postmeta_filter' ), true, 4);
			add_filter( 'the_content', array( $this, 'ht_gallery_content_filter' ) );
			add_filter( 'update_post_metadata', array( $this, 'check_gallery_state_post_meta_filter' ), 99, 5 );

			//activation hooks
			//none
			

			//set the meta key value
			$this->proofing_protection_option_key = 'proofing_protection_option';
			$this->proofing_gallery_option_key = 'proofing_gallery_option';
			$this->proofing_password_option_key = 'proofing_password_option';
			$this->proofing_user_restriction_option_key = 'proofing_user_restriction_option';
			$this->proofing_user_restriction_value_key = 'proofing_user_restriction_value';
			$this->proofing_time_restriction_option_key = 'proofing_time_restriction_option';
			$this->proofing_time_restriction_start_option_key = 'proofing_time_restriction_start_option';
			$this->proofing_time_restriction_start_value_key = 'proofing_time_restriction_start_value';
			$this->proofing_time_restriction_expire_option_key = 'proofing_time_restriction_expire_option';
			$this->proofing_time_restriction_expire_value_key = 'proofing_time_restriction_expire_value';
			$this->proofing_allow_approvals_option_key = 'proofing_allow_approvals_option';
			$this->proofing_allow_disapprovals_option_key = 'proofing_allow_disapprovals_option';
			$this->proofing_image_comments_option_key = 'proofing_image_comments_option';
			$this->proofing_album_comments_option_key = 'proofing_album_comments_option';
			$this->proofing_album_approval_option_key = 'proofing_album_approval_option';
			$this->proofing_state_key = 'proofing_state';
			$this->proofing_approval_actions_key  = 'proofing_approval_actions';
			$this->proofing_approval_actions_array = array(
											'email_gallery_author' => __('E-mail Gallery Author', 'ht-gallery-proofing'),
											'email_users' => __('E-mail All Gallery Users', 'ht-gallery-proofing'),
											'lock_approvals' => __('Lock Approvals', 'ht-gallery-proofing'),
											'lock_image_comments' => __('Lock Image Comments', 'ht-gallery-proofing'),
											'lock_gallery_comments' => __('Lock Album Comments', 'ht-gallery-proofing'),
											'close_gallery' => __('Close Gallery', 'ht-gallery-proofing')
										);


			$this->proofing_states = array(
					'AWAITING' => __( 'Awaiting approval', 'ht-gallery-proofing' ) , 
					'APPROVED' => __( 'Approved', 'ht-gallery-proofing' ) , 
					'CLOSED' => __( 'Closed', 'ht-gallery-proofing' )  
				);

			//include frontend functions
			include_once('php/ht-gallery-proofing-frontend-functions.php');

			//include voting clases
			include_once('php/ht-gallery-vote-classes.php');

			//include email templates
			include_once('templates/ht-gallery-proofing-email-template.php');
		}


		/* PROTECTION OPTIONS */

		/**
		* Add the metabox for protection options
		* @param WP_Post $post The post object
		*/
		function add_protection_metabox( $post ){
			//add the proofing metabox
			add_meta_box(
					'hero_gallery_protection',
					__( 'Protection Options', 'ht-gallery-proofing' ),
					array( $this, 'protection_metabox_display' ),
					'ht_gallery_post',
					'normal',
					'low'
					);
		}


		/**
		* Render the protection metabox
		* @param WP_Post $post The post object
		*/
		function protection_metabox_display( $post ){
			?>
			<div id="protection-metabox-content">
			<?php
			

			$this->render_protection_option( $post );

			?>
				<div id="protection-options">
					<?php
					$this->render_protection_password_option( $post );
					$this->render_protection_user_restriction( $post );
					$this->render_protection_time_restriction_start( $post );
					$this->render_protection_time_restriction_expire( $post );
					?>
				</div> <!--protection-options-->
			</div> <!--protection-metabox-content-->
			<?php

		}

		/**
		* Render the protection option
		* @param WP_Post $post The post object
		*/
		function render_protection_option( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Protect this Gallery', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_protection_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_protection_option_key, 
						__('Allows you to protect this gallery from the public.', 'ht-gallery-proofing'),
						$current_value );

					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}

		/**
		* Render the password option
		* @param WP_Post $post The post object
		*/
		function render_protection_password_option( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Password Protect', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_password_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_password_option_key, 
						__('Use a password to restrict access to this gallery (note this is stored in plaintext by WordPress)', 'ht-gallery-proofing'),
						$current_value );

					$this->render_protection_password_string( $post );
					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}

		/**
		* Render the actual password string
		* @param WP_Post $post The post object
		*/
		function render_protection_password_string( $post ){
			//this is an inbuilt WordPress post object, so we can use this (and the name)
			?>
			<div id="proofing-password-string">
				<input type="text" id="proofing_post_password" name="post_password" value="<?php echo $post->post_password; ?>">
			</div> <!-- /proofing-password-string -->
				
			<?php
		}

		/**
		* Render the user restriction display
		* @param WP_Post $post The post object
		*/
		function render_protection_user_restriction( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('User Restriction', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_user_restriction_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_user_restriction_option_key, 
						__('Use a User Restriction to restrict access to this gallery.', 'ht-gallery-proofing'),
						$current_value );

					//$this->render_protection_user_box( $post );
					$this->render_selectable_user_list( $post );
					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php

		}

		/**
		* Render the selectable user list
		* @param WP_Post $post The post object
		*/
		function render_selectable_user_list( $post ){
			?>
			<div id="protection-user-list">
				<input type="text" id="protection-user-search" placeholder="<?php _e('Search username, display name or email', 'ht-gallery-proofing'); ?>">
				<div id="protection-user-checkboxes">
					<?php

					$current_value = get_post_meta( $post->ID, $this->proofing_user_restriction_value_key, true );

					$selected_users_array = array();

					if($current_value && $current_value!=''){
						$selected_users_array = explode(',', $current_value);
					}

					$blogusers = get_users('');
				    foreach ($blogusers as $user) {
				    	$user_id = $user->ID;
				    	$post_owner = $user_id == $post->post_author ? true : false;
				    	//disable editing if post owner
				    	$disabled =  ( $post_owner ) ? 'disabled="disabled"' : '' ;
				    	//checked if already checked or post_owner
				    	$checked = in_array( $user_id, $selected_users_array ) || $post_owner ? 'checked="checked"' : '';
				    	//use a data search data-attr
				    	$search_data = esc_html(  $user_id .  $user->user_login .  $user->display_name . $user->first_name . $user->last_name . $user->user_email );
				    	echo '<div class="user-line" id="user-line-' . $user_id . '">';
				        echo '<input type="checkbox" class="protection-users" name="' . $this->proofing_user_restriction_value_key . '[]" value="' . $user_id . '" data-search="' . $search_data . '" ' . $checked . $disabled . '> ' . $user->user_login . ' (' . $user->display_name . ')' . '<br/>';
				        if($post_owner){
				        	//needed because disabled values do not submit
				        	echo '<input type="hidden" name="' . $this->proofing_user_restriction_value_key . '[]" value="' . $user_id . '" > ';
				        }
				    	echo '</div>';
				    }
				    ?>
				</div><!-- /protection-user-checkboxes -->
			</div><!-- /protection-user-list -->


		    <?php
		}

		/**
		* Render the time restriction start option
		* @param WP_Post $post The post object
		*/
		function render_protection_time_restriction_start( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Gallery Opening Date and Time', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_time_restriction_start_option_key, true );

					$current_time_set = get_post_meta( $post->ID, $this->proofing_time_restriction_start_value_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_time_restriction_start_option_key, 
						__('Restrict access to the gallery by time', 'ht-gallery-proofing'),
						$current_value );

					$this->date_time_helper( 'start', $current_time_set );
					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}

		/**
		* Render the time restriction expiry option
		* @param WP_Post $post The post object
		*/
		function render_protection_time_restriction_expire( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Galery Expiry', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_time_restriction_expire_option_key, true );

					$current_time_set = get_post_meta( $post->ID, $this->proofing_time_restriction_expire_value_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_time_restriction_expire_option_key, 
						__('Restrict access to the gallery by time', 'ht-gallery-proofing'),
						$current_value );

					$this->date_time_helper( 'expiry', $current_time_set );
					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}


		/* PROOFING OPTIONS */

		/**
		* Add the metabox for proofing options
		* @param WP_Post $post The post object
		*/
		function add_proofing_metabox( $post ){
			//add the proofing metabox
			add_meta_box(
					'hero_gallery_proofing',
					__( 'Proofing Options', 'ht-gallery-proofing' ),
					array( $this, 'proofing_metabox_display' ),
					'ht_gallery_post',
					'normal',
					'low'
					);
		}

		/**
		* Render the protection metabox
		* @param WP_Post $post The post object
		*/
		function proofing_metabox_display( $post ){
			?>
			<div id="proofing-metabox-content">
			<?php
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'ht_gallery_proofing', 'ht_gallery_proofing_security' );

			$this->render_proofing_option( $post );

			?>
				<div id="proofing-options">
					<?php
					$this->render_proofing_state( $post );
					$this->render_proofing_voting( $post );
					$this->render_proofing_comments( $post );
					$this->render_approval_actions( $post );
					?>
				</div> <!--proofing-options-->
			</div> <!--proofing-metabox-content-->
			<?php

		}

		/**
		* Render the time proofing option
		* @param WP_Post $post The post object
		*/
		function render_proofing_option( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Enable Gallery Proofing', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_gallery_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_gallery_option_key, 
						__('Enables proofing workflow', 'ht-gallery-proofing'),
						$current_value );
					?>
				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}

		/**
		* Render the time proofing option
		* @param WP_Post $post The post object
		*/
		function render_proofing_voting( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Allow approvals', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_allow_approvals_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_allow_approvals_option_key, 
						__('Allow users to approve images', 'ht-gallery-proofing'),
						$current_value );

					$current_value = get_post_meta( $post->ID, $this->proofing_allow_disapprovals_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_allow_disapprovals_option_key, 
						__('Allow users to disapprove images', 'ht-gallery-proofing'),
						$current_value );

					$current_value = get_post_meta( $post->ID, $this->proofing_album_approval_option_key, true );

					$this->checkbox_helper( 
						$this->proofing_album_approval_option_key, 
						__('Allow users to approve album', 'ht-gallery-proofing'),
						$current_value );

					?>

				</div> <!--proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}
		
		/**
		* Render the time proofing comment options
		* @param WP_Post $post The post object
		*/
		function render_proofing_comments( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e('Comments', 'ht-gallery-proofing'); ?></div>
				<div class="proofing-option-values">
					<?php
					$current_value = get_post_meta( $post->ID, $this->proofing_image_comments_option_key, true );
					
					$this->checkbox_helper( 
						$this->proofing_image_comments_option_key, 
						__('Allow users to comment on individual images in gallery', 'ht-gallery-proofing'),
						$current_value );

					$current_value = get_post_meta( $post->ID, $this->proofing_album_comments_option_key, true ) || $post->comment_status=='open';

					$this->checkbox_helper( 
						$this->proofing_album_comments_option_key, 
						__('Allow users to comment on the entire gallery or album', 'ht-gallery-proofing'),
						$current_value );
					
					?>
				</div> <!-- /proofing-option-values-->
			</div><!-- /proofing-option-section -->
			<?php
		}	

		/**
		* Render the proofing state dropdown
		* @param WP_Post $post The post object
		*/
		function render_proofing_state( $post ){
			?>
			<div class="proofing-option-section">
				<?php
				$current_value = get_post_meta( $post->ID, $this->proofing_state_key, true );
				?>
				<div class="proofing-option-title"><?php _e( 'Proofing State', 'ht-gallery-proofing' ); ?></div>
				<div class="proofing-option-values">
					<select id="proofing-state" name="<?php echo $this->proofing_state_key; ?>">
					<?php
					foreach ($this->proofing_states as $state_key => $state_text) {
						$selected  = $current_value == $state_key ? 'selected="selected"' : ''; 
						printf('<option value="%s" %s>%s</option>', $state_key, $selected, $state_text);
					}
					?>
					</select><!--proofing-state-->
					
					<label for="<?php echo $this->proofing_state_key; ?>" class="proofing-checkbox-caption"><?php _e( 'Set a state for this gallery', 'ht-gallery-proofing' );  ?></label>
				</div> <!-- /proofing-option-values-->
				<?php

				?>
			</div><!-- /proofing-option-section -->
			<?php		
		}

		/**
		* Render the list of options for after approval actions
		* @param WP_Post $post The post object
		*/
		function render_approval_actions( $post ){
			?>
			<div class="proofing-option-section">
				<div class="proofing-option-title"><?php _e( 'Approval Actions', 'ht-gallery-proofing' ); ?></div>
				<div class="proofing-option-values">
					<?php
					$set_approval_actions = get_post_meta( $post->ID, $this->proofing_approval_actions_key, true );
					$set_approval_actions_array = explode(',', $set_approval_actions);
					foreach ($this->proofing_approval_actions_array as $key => $value) {
						//email gallery author
						$current_value = in_array($key, $set_approval_actions_array) ? $key : '';
						$this->checkbox_helper( 
							$key, 
							$value,
							$current_value );
					}
					?>
				</div> <!-- /proofing-option-values-->
				<?php

				?>
			</div><!-- /proofing-option-section -->
			<?php
			
		}



		/* HELPERS AND OTHERS */


		/**
		* A helper function to render a standard checkbox
		* @param String $name The name of the input
		* @param String $caption The caption/helper text to be displayed
		* @param Boolean $checked Whether the checkbox should already be checked
		*/
		function checkbox_helper($name, $caption, $checked){
			$checked_output = $checked ? 'checked="checked"' : '';
			?>
			<div class="proofing-checkbox">		
				<input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo $name; ?>" <?php echo $checked_output; ?> >
				<label for="<?php echo $name; ?>" class="proofing-checkbox-caption"><?php echo $caption; ?></label>		
			</div> <!--/proofing-checkbox-->	
			<?php

		}

		/**
		* A wrapper helper function to display a date/time picker
		* @param String $name The name of the input
		* @param DateTime $date The datetime
		*/
		function date_time_helper($name, $date){
			?>
			<div class="gallery-proofing-datetime curtime misc-pub-curtime" id="proofing-restrict-<?php echo $name; ?>">
	        
		    <?php $this->date_print( $name, $date ); ?>

			</div> <!--/gallery-proofing-datetime -->
			
			<?php
		}

		/**
		* A wrapper helper function to render a date/time picker
		* @param String $name The name of the input
		* @param DateTime $date The datetime
		*/
		function date_print($name, $date){
			global $wp_locale;
			if($date == null && $date != ''){
				$date = date( 'Y-m-d H:i:s', time() );
			}
			$date_option = $date;
			$time_adj = current_time('timestamp');	
	        $jj = ($date_option) ? mysql2date( 'd', $date_option, false ) : gmdate( 'd', $time_adj );
	        $mm = ($date_option) ? mysql2date( 'm', $date_option, false ) : gmdate( 'm', $time_adj );
	        $aa = ($date_option) ? mysql2date( 'Y', $date_option, false ) : gmdate( 'Y', $time_adj );
	        $hh = ($date_option) ? mysql2date( 'H', $date_option, false ) : gmdate( 'H', $time_adj );
	        $mn = ($date_option) ? mysql2date( 'i', $date_option, false ) : gmdate( 'i', $time_adj );
	        $ss = ($date_option) ? mysql2date( 's', $date_option, false ) : gmdate( 's', $time_adj );


			$month = '<select id="' . $name . '-mm" name="' . $name . '-mm">\n';
	        for ( $i = 1; $i < 13; $i = $i +1 ) {
	                $monthnum = zeroise($i, 2);
	                $month .= "\t\t\t" . '<option value="' . $monthnum . '"';
	                if ( $i == $mm )
	                        $month .= ' selected="selected"';
	                /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
	                $month .= '>' . sprintf( __( '%1$s-%2$s' ), $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ) . "</option>\n";
	        }
	        $month .= '</select>';

	        $day = '<input type="text"  id="' . $name . '-jj"  name="' . $name . '-jj" value="' . $jj . '" size="2" maxlength="2" autocomplete="off" />';
	        $year = '<input type="text"  id="' . $name . '-aa"  name="' . $name . '-aa" value="' . $aa . '" size="4" maxlength="4" autocomplete="off" />';
	        $hour = '<input type="text"  id="' . $name . '-hh"  name="' . $name . '-hh" value="' . $hh . '" size="2" maxlength="2" autocomplete="off" />';
	        $minute = '<input type="text"  id="' . $name . '-mn"  name="' . $name . '-mn" value="' . $mn . '" size="2" maxlength="2" autocomplete="off" />';

	        echo '<div class="timestamp-wrap">';
	        /* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
	        printf( __( '%1$s %2$s, %3$s @ %4$s : %5$s' ), $month, $day, $year, $hour, $minute );

	        echo '</div><input type="hidden" id="' . $name . '-ss" name="' . $name . '-ss" value="' . $ss . '" />';
		}

		/**
		* A validation function for date/time post data
		* @param String $name The name of the input to validate date of
		* @return Mixed gmt_date on success
		* @throws WP_Error if invalid
		*/
		function validate_date($name){
			$post_data = &$_POST;
		    $aa = $post_data[$name.'-'.'aa'];
		    $mm = $post_data[$name.'-'.'mm'];
		    $jj = $post_data[$name.'-'.'jj'];
		    $hh = $post_data[$name.'-'.'hh'];
		    $mn = $post_data[$name.'-'.'mn'];
		    $ss = $post_data[$name.'-'.'ss'];
		    $aa = ($aa <= 0 ) ? date('Y') : $aa;
		    $mm = ($mm <= 0 ) ? date('n') : $mm;
		    $jj = ($jj > 31 ) ? 31 : $jj;
		    $jj = ($jj <= 0 ) ? date('j') : $jj;
		    $hh = ($hh > 23 ) ? $hh -24 : $hh;
		    $mn = ($mn > 59 ) ? $mn -60 : $mn;
		    $ss = ($ss > 59 ) ? $ss -60 : $ss;
		    $date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
		    $valid_date = wp_checkdate( $mm, $jj, $aa, $date );
		    if ( !$valid_date ) {
		            return new WP_Error( 'invalid_date', __( 'Whoops, the provided date is invalid.' ) );
		    }
		    return get_gmt_from_date( $date );
		}

		/**
		* Hooks onto save post to save custom post type metadata
		* @param String $post_id The post id saving
		*/
		function save_gallery_post_proofing( $post_id ){

			// Check if our nonce is set.
			if ( ! isset( $_POST['ht_gallery_proofing_security'] ) )
				return $post_id;

			$nonce = $_POST['ht_gallery_proofing_security'];

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'ht_gallery_proofing' ) )
				return $post_id;


			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return $post_id;

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) )
					return $post_id;
		
			} else {

				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			/* Permissions checked - we can now save the post  */

			// Sanitize the user input.
			$proofing_protection_option = array_key_exists( $this->proofing_protection_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_protection_option_key] ) : '';
			$proofing_gallery_option = array_key_exists($this->proofing_gallery_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_gallery_option_key] ) : '';
			$proofing_password_option = array_key_exists($this->proofing_password_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_password_option_key] ) : '';
			$proofing_user_restriction_option = array_key_exists($this->proofing_user_restriction_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_user_restriction_option_key] ) : '';
			$proofing_user_restriction_value = array_key_exists($this->proofing_user_restriction_value_key, $_POST) ? $_POST[$this->proofing_user_restriction_value_key]  : '';
			$proofing_time_restriction_option = array_key_exists($this->proofing_time_restriction_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_time_restriction_option_key] ) : '';
			$proofing_time_restriction_start_option = array_key_exists($this->proofing_time_restriction_start_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_time_restriction_start_option_key] ) : '';
			$proofing_time_restriction_expire_option = array_key_exists($this->proofing_time_restriction_expire_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_time_restriction_expire_option_key] ) : '';
			$proofing_allow_approvals_option = array_key_exists($this->proofing_allow_approvals_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_allow_approvals_option_key] ) : '';
			$proofing_allow_disapprovals_option = array_key_exists($this->proofing_allow_disapprovals_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_allow_disapprovals_option_key] ) : '';
			$proofing_state = array_key_exists($this->proofing_state_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_state_key] ) : '';
			$proofing_album_approval_option = array_key_exists($this->proofing_album_approval_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_album_approval_option_key] ) : '';
			$proofing_image_comments_option = array_key_exists($this->proofing_image_comments_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_image_comments_option_key] ) : '';
			$proofing_album_comments_option = array_key_exists($this->proofing_album_comments_option_key, $_POST) ? sanitize_text_field( $_POST[$this->proofing_album_comments_option_key] ) : '';
			//aproval actions
			$approval_actions_array = array();
			foreach ($this->proofing_approval_actions_array  as $key => $value) {
				if( array_key_exists($key, $_POST) && $key==$_POST[$key] ){
					//add to the approval actions
					$approval_actions_array[] = $key;
				}
			}
			//implode array
			$approval_actions = implode(',', $approval_actions_array);



			//update the protection option
			update_post_meta( $post_id, $this->proofing_protection_option_key, $proofing_protection_option);
			//update the proofing option
			update_post_meta( $post_id, $this->proofing_gallery_option_key, $proofing_gallery_option);

			//update the proofing password option
			update_post_meta( $post_id, $this->proofing_password_option_key, $proofing_password_option);
			//update the proofing user restriction option
			update_post_meta( $post_id, $this->proofing_user_restriction_option_key, $proofing_user_restriction_option);
			if(is_array($proofing_user_restriction_value)){

				$proofing_user_restriction_value_list = implode(',', $proofing_user_restriction_value);
				update_post_meta( $post_id, $this->proofing_user_restriction_value_key, $proofing_user_restriction_value_list );
			} else {
				//set as blank just incase of nasties
				update_post_meta( $post_id, $this->proofing_user_restriction_value_key, '');
			}
			//update_post_meta( $post_id, $this->proofing_user_restriction_value_key, $proofing_user_restriction_value);
			//update the proofing time restriction option
			update_post_meta( $post_id, $this->proofing_time_restriction_option_key, $proofing_time_restriction_option);
			update_post_meta( $post_id, $this->proofing_time_restriction_start_option_key, $proofing_time_restriction_start_option);
			update_post_meta( $post_id, $this->proofing_time_restriction_expire_option_key, $proofing_time_restriction_expire_option);

			//update the allow approvals option
			update_post_meta( $post_id, $this->proofing_allow_approvals_option_key, $proofing_allow_approvals_option);
			//update the allow disapprovals option
			update_post_meta( $post_id, $this->proofing_allow_disapprovals_option_key, $proofing_allow_disapprovals_option);
						
			//allow user approval album
			update_post_meta( $post_id, $this->proofing_album_approval_option_key, $proofing_album_approval_option);

			//coments
			update_post_meta( $post_id, $this->proofing_image_comments_option_key, $proofing_image_comments_option);
			update_post_meta( $post_id, $this->proofing_album_comments_option_key, $proofing_album_comments_option);
			//sync album comments with post comments
			if($proofing_album_comments_option){
				//manually set the comment status as open
				$_POST['comment_status'] = 'open';
			} else {
				//$_POST['comment_status'] = '';
			}

			//approval actions
			update_post_meta( $post_id, $this->proofing_approval_actions_key, $approval_actions);

			//aditional password work
			if(isset($_POST['post_password'])){
				//manually set the visibility as password
				$_POST['visibility'] = 'password';
			} else {

			}

			//date
			//start
			$start_date = $this->validate_date('start');
			if(!is_a($start_date, 'WP_Error')){
				update_post_meta( $post_id, $this->proofing_time_restriction_start_value_key, $start_date);
			}
			//expiry
			$expiry_date = $this->validate_date('expiry');
			if(!is_a($expiry_date, 'WP_Error')){
				update_post_meta( $post_id, $this->proofing_time_restriction_expire_value_key, $expiry_date);
			}

			//state 
			update_post_meta( $post_id, $this->proofing_state_key, $proofing_state);
		}

		/**
		* Filter to check the gallery state for a transition of state, run before update post meta
		* @param $null (null) (required) Always null
		* @param $object_id (int) (required) ID of the object metadata is for
		* @param $meta_key (string) (required) Metadata key
		* @param $meta_value (mixed) (required) Metadata value. Must be serializable if non-scalar.
		* @param $prev_value (mixed) (required) The previous metadata value.
		* @return null - required for normal execution
		*/
		function check_gallery_state_post_meta_filter( $check, $object_id, $meta_key, $meta_value, $prev_value ){
			if( get_post_type($object_id) == 'ht_gallery_post' && $meta_key==$this->proofing_state_key){
				//prev_val is the parameter prev_val not the actual previous value, so this needs to be fetched
				$fetched_prev_meta_val = get_post_meta($object_id, $this->proofing_state_key, true);

				//remove this filter to avoid infinite loop
				remove_filter( 'update_post_metadata', array( $this, 'check_gallery_state_post_meta_filter' ), 99 );
				if($fetched_prev_meta_val=='AWAITING' && $meta_value=='APPROVED'){
					$this->perform_approval_actions( $object_id );
				}
				
			} 			
			
			return $check;
		}

		/**
		* Approval actions orchastrator
		* @param String $gallery_id The gallery to perform the approval actions on
		*/
		function perform_approval_actions ( $gallery_id ){
			$approval_actions_string = get_post_meta( $gallery_id, $this->proofing_approval_actions_key, true );
			$approval_actions_array = explode(',', $approval_actions_string);
			//email gallery owner
			if(in_array('email_gallery_author', $approval_actions_array)){
				$this->email_gallery_author( $gallery_id );
			}
			if(in_array('email_users', $approval_actions_array)){
				$this->email_users( $gallery_id );
			}
			if(in_array('lock_approvals', $approval_actions_array)){
				$this->lock_approvals( $gallery_id );
			}
			if(in_array('lock_image_comments', $approval_actions_array)){
				$this->lock_image_comments( $gallery_id );
			}
			if(in_array('lock_gallery_comments', $approval_actions_array)){
				$this->lock_gallery_comments( $gallery_id );
			}
			if(in_array('close_gallery', $approval_actions_array)){
				$this->close_gallery( $gallery_id );
			}
		}

		/**
		* Emails the gallery author
		* @todo Flesh out function
		* @param String $gallery_id The gallery to perform the action
		*/
		function email_gallery_author( $gallery_id ){

			
			//get the gallery author
			$post = get_post($gallery_id);
			if(!is_a($post, 'WP_Post'))
                return false;

            $author = $post->post_author;

            if( empty($author) || $author<1 )
                return false;


            $author_user = get_userdata( $author );

            if(!is_a($author_user, 'WP_User'))
                return false;

            $recipient = new HT_Gallery_Proofing_Recipient($author_user->display_name, $author_user->user_email);

            $recipients = array();

            //push recipients 
            array_push($recipients, $recipient);

            //prepare template
            $email = new HT_Gallery_Proofing_Email_Template($recipients, $gallery_id);

            //send 
            return $email->send();
            
		}

		/**
		* Emails the gallery users
		* @todo Flesh out function
		* @param String $gallery_id The gallery to perform the action
		*/
		function email_users( $gallery_id ){
			$allowed_users_meta_data = get_post_meta( $gallery_id, $this->proofing_user_restriction_value_key, true );
			$allowed_users_array = explode(',', $allowed_users_meta_data);
			if(is_array($allowed_users_array) && count($allowed_users_array)>0){
				//email each user
				$recipients = array();
				foreach ($allowed_users_array as $key => $user) {
					$current_user = get_userdata( $user );

			        if(!is_a($current_user, 'WP_User'))
			            continue;

			        $recipient = new HT_Gallery_Proofing_Recipient($current_user->display_name, $current_user->user_email);

			        //push recipient
			        array_push($recipients, $recipient);
				}

				//prepare template
	            $email = new HT_Gallery_Proofing_Email_Template($recipients, $gallery_id);

	            //send 
	            return $email->send();
			}

		}

		/**
		* Lock Approvals
		* @todo Flesh out function
		* @param String $gallery_id The gallery to perform the action
		*/
		function lock_approvals( $gallery_id ){
			update_post_meta( $gallery_id, $this->proofing_allow_approvals_option_key, '');
			update_post_meta( $gallery_id, $this->proofing_allow_disapprovals_option_key, '');
		}

		/**
		* Lock image comments
		* @todo Flesh out function
		* @param String $gallery_id The gallery to perform the action
		*/
		function lock_image_comments( $gallery_id ){
			update_post_meta( $gallery_id, $this->proofing_image_comments_option_key, '');
		}

		/**
		* Lock gallery comments
		* @todo Flesh out function
		* @param String $gallery_id The gallery to perform the action
		*/
		function lock_gallery_comments( $gallery_id ){
			global $post;
			update_post_meta( $post->ID, $this->proofing_album_comments_option_key, '');
			$post->comment_status = 'closed';
			wp_update_post($post);

		}



		/**
		* Close the gallery
		*/
		function close_gallery( $gallery_id ){
			update_post_meta( $gallery_id, $this->proofing_state_key, 'CLOSED');
		}

		/**
		* Check if an approval action is set for a given gallery
		* @param String $gallery_id The gallery to check
		* @param String $action The action to check
		* @return Boolean true if action is set for gallery, else false
		*/
		function is_approval_action( $gallery_id, $action ){
			$approval_actions_string = get_post_meta( $gallery_id, $this->proofing_approval_actions_key, true );
			$approval_actions_array = explode(',', $approval_actions_string);
			if(in_array($action, $approval_actions_array)){
				return true;
			} else {
				return false;
			}
		}

		/**
		* Check if the gallery is closed
		* @param String $gallery_id The gallery to check
		* @return Boolean true if gallery is open, else false
		*/
		function is_gallery_closed( $gallery_id ){
			$state = get_post_meta($gallery_id, $this->proofing_state_key, true);
			if($state == "CLOSED"){
				return true;
			}
			if($state=="APPROVED" && $this->is_approval_action( $gallery_id, 'close_gallery')){
				return true;
			}
			return false;
		}


		/**
		* Enqueue scripts and styles
		*/
		function enqueue_ht_gallery_proofing_scripts_and_styles(){
			$screen = get_current_screen();

			if( $screen->post_type == 'ht_gallery_post' && $screen->base == 'post' ) {
				wp_enqueue_script( 'ht-gallery-proofing-scripts', plugins_url( 'js/ht-gallery-proofing-scripts.js', __FILE__ ), array( 'jquery' , 'jquery-effects-core', 'jquery-ui-draggable', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable' ), 1.1, true );
				wp_enqueue_style( 'ht-gallery-proofing-style', plugins_url( 'css/ht-gallery-proofing-style.css', __FILE__ ));
				
			} 
		}

		/**
		* Returns false if user restriction is turned on and not a valid user
		* @param String $post_id The gallery to check
		* @return Boolean True if user is authorised to access gallery
		*/
		function user_can_access_gallery( $post_id ){
			$user_access_control = get_post_meta( $post_id, $this->proofing_user_restriction_option_key, true );
			if($user_access_control && $user_access_control!=''){
				//default behaviour if this is enabled is to require users to log in
				if(is_user_logged_in()){
					$current_user = wp_get_current_user();
					$current_user_id = is_a($current_user, 'WP_User') ? $current_user->ID : 0;
					if( $current_user_id==0 ){
						//not logged in or error
						return false;
					} else {
						$allowed_users_meta_data = get_post_meta( $post_id, $this->proofing_user_restriction_value_key, true );
						$allowed_users_array = explode(',', $allowed_users_meta_data);
						if(is_array($allowed_users_array) && count($allowed_users_array)>0){
							if(in_array($current_user_id, $allowed_users_array)){
								//in array, valid user
								return true;
							} else {
								//not a valid user
								return false;
							}

						} else {
							//no users allowed?
							return false;
						}
					}

				} else {
					//user not logged in, return false
					return false;
				}
			} else {
				//no user access control, return true
				return true;
			}
		}

		/**
		* Check current date/time ahead of gallery start time
		* @param String $gallery_id The gallery to check
		* @return Boolean true if gallery is embargo is up
		*/
		function gallery_open( $post_id ){
			$time_restriction_start_option = get_post_meta( $post_id, $this->proofing_time_restriction_start_option_key, true );
			if($time_restriction_start_option && $time_restriction_start_option!=''){
				$time_restriction_start_value = get_post_meta( $post_id, $this->proofing_time_restriction_start_value_key, true );
				if($time_restriction_start_value && $time_restriction_start_value!=''){
					if( strtotime($time_restriction_start_value)<time() ){
						//current time is later than start value, return true
						return true;
					} else {
						//we are not yet past start embargo, return false
						return false;
					}
				} else {
					//if not set return true, maybe manual override?
					return true;
				}
			} else {
				//no control, return true
				return true;
			}
		}

		/**
		* Check current date/time ahead of gallery expiry time
		* @param String $gallery_id The gallery to check
		* @return Boolean true if gallery is expired
		*/
		function gallery_expired( $post_id ){    
			$time_restriction_expire_option = get_post_meta( $post_id, $this->proofing_time_restriction_expire_option_key, true );
			if($time_restriction_expire_option && $time_restriction_expire_option!=''){
				$time_restriction_expire_value = get_post_meta( $post_id, $this->proofing_time_restriction_expire_value_key, true );
				if($time_restriction_expire_value && $time_restriction_expire_value!=''){
					if( strtotime($time_restriction_expire_value)<time() ){
						//current time is later than expire value, return true
						return true;
					} else {
						//we are not yet past expire embargo, return false
						return false;
					}
				} else {
					//if not set return false, maybe manual override?
					return false;
				}
			} else {
				//no control, return false
				return false;
			}
		}


		/**
		* Check the gallery state is closed
		* @param String $gallery_id The gallery to check
		* @return Boolean true if gallery is closed
		*/
		function gallery_closed( $post_id ){    
			$gallery_state = get_post_meta( $post_id, $this->proofing_state_key, true );
			if($gallery_state && $gallery_state!=''){
				if( $gallery_state=="CLOSED" ){
					return true;
				} else {
					return false;
				}
			} else {
				//no control, return false
				return false;
			}
		}

		/**
	    * Test whether post is a proofing gallery
	    * @param (string) $post_id
	    * @return  (boolean) true if proofing gallery
	    */
	    public function is_proofing_gallery($post_id){
	        if(!is_single($post_id)){
				return false;
	        } else {
	        	$proofing_option = get_post_meta($post_id, $this->proofing_gallery_option_key, true);
	        	if(empty($proofing_option)){
			        return false;
			    } else {
			        return true;
			    }
	        }    
	    }

	    /**
	    * Test whether post is a proofing gallery
	    * @param (string) $post_id
	    * @return  (boolean) true if proofing gallery
	    */
	    public function is_protected_gallery($post_id){
	        if(!is_single($post_id)){
				return false;
	        } else {
	        	$protected_option = get_post_meta($post_id, $this->proofing_protection_option_key, true);
	        	if(empty($protected_option)){
			        return false;
			    } else {
			        return true;
			    }
	        }    
	    }

		/**
		* Check protection permissions on gallery
		* @param String $post_id The gallery to check
		* @return Boolean true if gallery is embargo is up
		* @throws WP_Error The proofing error thrown
		*/
		function check_protection_permissions( $post_id ){
			//password protect this meta data
			if( !is_admin() && $this->is_protected_gallery( $post_id ) && post_password_required( $post_id ) ){
				//password not supplied - return blank array
				return new WP_Error('ht_gallery_proofing_bad_password', __('This gallery requires a password', 'ht-gallery-proofing'));
			} if( !is_admin() && $this->is_protected_gallery( $post_id ) && !$this->user_can_access_gallery( $post_id ) ) {
				return new WP_Error('ht_gallery_proofing_bad_user', __('You are not authorized to access this page', 'ht-gallery-proofing'));
			} if( !is_admin() && $this->is_protected_gallery( $post_id ) && !$this->gallery_open( $post_id ) ) {
				return new WP_Error('ht_gallery_proofing_not_open', __('This gallery is not yet open', 'ht-gallery-proofing'));
			} if( !is_admin() && $this->is_protected_gallery( $post_id ) && $this->gallery_expired( $post_id ) ) {
				return new WP_Error('ht_gallery_proofing_expired', __('This gallery has expired', 'ht-gallery-proofing'));
			} if( !is_admin() && $this->is_proofing_gallery( $post_id ) && $this->is_gallery_closed( $post_id ) ) {
				return new WP_Error('ht_gallery_proofing_closed', __('This gallery is closed', 'ht-gallery-proofing'));
			} else {
				//valid - return true
				return true;
			}
		}

		 /**
		 * Custom postmeta filter to protect postmeta
	     * @param string|array $metadata - Always null for post metadata.
	     * @param int $object_id - Post ID for post metadata
	     * @param string $meta_key - metadata key.
	     * @param bool $single - Indicates if processing only a single $metadata value or array of values.
	     * @return Original or Modified $metadata.
	     * @throws Permission Errors - ht_gallery_proofing_bad_password, ht_gallery_proofing_bad_user, ht_gallery_proofing_not_open, ht_gallery_proofing_expired, ht_gallery_proofing_closed
	     */
		function ht_gallery_get_postmeta_filter($metadata, $object_id, $meta_key, $single){
			if( $meta_key == "_ht_gallery_images" ){
				//check permissions
				$check = $this->check_protection_permissions( $object_id );
				if(is_a($check, 'WP_Error')){
					//pass on errors
					return $check;
				} else {
					return $metadata;
				}		
			} else {
				return $metadata;
			}
		}

		/**
		 * Custom content filter to protect content
	     * @param $content The content to filter
	     * @return The filtered content or error message
	     */
		function ht_gallery_content_filter($content){
			global $post;
			if($post && $post->post_type=="ht_gallery_post"){
				$check = $this->check_protection_permissions($post->ID);
				if(is_a($check, 'WP_Error')){
					//print any error details
					if($check->get_error_code()=='ht_gallery_proofing_bad_password'){
						//let wp handle password entry
						return $content;
					}
					return $check->get_error_message();
				} else {
					return $content;
				}
			} else {
				return $content;
			}		
		}

		/**
		* Check for HT Gallery Manager and displays notice if not installed
		*/
		function ht_gallery_manager_required_notice(){
			if(!class_exists('HT_Gallery_Manager')){
				echo '<div class="error"><p>'; 
            	echo sprintf( __('You need the latest version of Heroic Gallery Manager installed and activated for the Heroic Gallery Proofing system to work. <a href="%s">Install it now</a>, and <a href = "%s">ensure it is activated</a>.', 'ht-gallery-proofing'), admin_url('plugin-install.php?tab=search&s=hero+themes+gallery+manager&plugin-search-input=Search+Plugins'), admin_url('plugins.php#hero-themes-gallery-manager') );
            	echo '</p></div>';

			}
		}



	} //end class HT_Gallery_Proofing
}//end class exists test


//run the plugin
if( class_exists( 'HT_Gallery_Proofing' ) ){
	$ht_gallery_proofing_init = new HT_Gallery_Proofing();
}