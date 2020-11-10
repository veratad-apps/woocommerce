<?php


if ( ! defined( 'ABSPATH' ) ) {
	echo "This is a plugin. If you need information visit www.veratad.com";
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Veratad_Admin' ) ) :
	  require_once( 'wc-class-veratad-api.php' );
	/**
	 * Settings class
	 *
	 * @since 1.0.0
	 */
	class WC_Veratad_Admin  {

		private $api;

		public function __construct( WC_Veratad_Api $api ) {
			$this->api = $api;
			$this->id    = 'veratad';
			$this->label = __( 'Veratad', 'my-textdomain' );

			add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 30);
			add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id,      array( $this, 'get_sections' ) );

		}

		public function add_settings_page( $pages ) {
			$pages[$this->id] = $this->label;
			return $pages;
		}

		public function add_column_to_order($columns){

    $new_columns = array();

    foreach ( $columns as $column_name => $column_info ) {

        $new_columns[ $column_name ] = $column_info;

        if ( 'order_status' === $column_name ) {
            $new_columns['_veratad_verified'] = __( 'Age Verification', 'my-textdomain' );
        }
    }

    return $new_columns;

		}



		public function add_column_data_to_order($column){

			//print_r($column);
	    if ( '_veratad_verified' === $column ) {
				global $woocommerce, $post;
				$order = new WC_Order($post->ID);
				$action = $order->get_meta('_veratad_verified');
				if(!$action){
					echo "NONE";
				}else{
					echo $action;
				}

	    }

		}





function av_add_meta_boxes()
    {
        add_meta_box( 'av_status', __('Age Verification','woocommerce'), array($this,'av_add_other_fields'), 'shop_order', 'side', 'core' );

    }

		function av_add_other_fields()
		{

			global $post;

			global $current_user;

    	$username = $current_user->user_login;
			$customer_id = get_post_meta( $post->ID, '_customer_user', true ) ? get_post_meta( $post->ID, '_customer_user', true ) : '';
			$meta_field_data = get_post_meta( $post->ID, '_veratad_verified', true ) ? get_post_meta( $post->ID, '_veratad_verified', true ) : '';
			$dob = get_post_meta( $post->ID, '_veratad_dob', true ) ? get_post_meta( $post->ID, '_veratad_dob', true ) : '';
			if(!$dob){
				$dob = "Not Stored";
			}
			$idfront = get_post_meta( $post->ID, '_veratad_id_front', true ) ? get_post_meta( $post->ID, '_veratad_id_front', true ) : '';
			$idback = get_post_meta( $post->ID, '_veratad_id_back', true ) ? get_post_meta( $post->ID, '_veratad_id_back', true ) : '';
			$history = get_post_meta( $post->ID, '_veratad_changed_by') ? get_post_meta( $post->ID, '_veratad_changed_by' ) : '';
			 if(!$idfront){
				 $document_style = "display:none;";
				 $no_document_text_style = "display:inline-block;";
			 }else{
				 $document_style = "display:inline-block;";
				 $no_document_text_style = "display:none;";
			 }
			 if(!$meta_field_data){
				 $meta_field_data = "NONE";
			 }
			 echo '<input type="hidden" name="av_other_meta_field_nonce" value="' . wp_create_nonce() . '">
			 <input type="hidden" name="customer_id" value="' . $customer_id . '">
			 <input type="hidden" name="username" value="' . $username . '">
			 <p>
				<select style="width:250px;" name="av_status">
					 <option value="">Change the status...</option>
					 <option value="PASS">PASS</option>
					 <option value="FAIL">FAIL</option>
				</select>
				</p>
			 <p style="font-weight:700;">Current Status: ' . $meta_field_data . '</p>
			 <p style="font-weight:700;">Date of Birth: ' . $dob . '</p>

				<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">ID Front</p>
				<p>
				<a target="_blank" style="'.$document_style.'" href="https://register.veratad.com/images/ul/'.$idfront.'"><img style="'.$document_style.'" width="250px;" src="https://register.veratad.com/images/ul/'.$idfront.'"</img></a>
				</p>
				<p style="'.$no_document_text_style.'">No Document Uploaded</p>
				<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">ID Back</p>
				<p>
				<a target="_blank" style="'.$document_style.'" href="https://register.veratad.com/images/ul/'.$idback.'"><img style="'.$document_style.'" width="250px;" src="https://register.veratad.com/images/ul/'.$idback.'"</img></a>
				</p>
				<p style="'.$no_document_text_style.'">No Document Uploaded</p>
				<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">History</p>';

				if($history){
					echo '<table>
				<thead>
				<th align="left">User</th>
				<th align="left">Status</th>
				<th align="left">Timestamp</th>
				</thead>
				<tbody>';

				foreach($history as $details){
					$arr = explode("/", $details);
					$user_detail = $arr[1];
					$action_detail = $arr[2];
					$timestamp_detail = $arr[0];
					echo "<tr><td>$user_detail</td> <td>$action_detail</td> <td>$timestamp_detail</td></tr>";
				}

				echo '</tbody>
				</table>';
			}else{
				echo "No status changed by user yet.";
			}
		}


		function av_save_wc_order_other_fields( $post_id ) {

        // We need to verify this with the proper authorization (security stuff).

        // Check if our nonce is set.
        if ( ! isset( $_POST[ 'av_other_meta_field_nonce' ] ) ) {
            return $post_id;
        }
        $nonce = $_REQUEST[ 'av_other_meta_field_nonce' ];

        //Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( 'page' == $_POST[ 'post_type' ] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
        // --- Its safe for us to save the data ! --- //

        // Sanitize user input  and update the meta field in the database.
				$av_status = $_POST[ 'av_status' ];
				$order = wc_get_order( $post_id );
				if($av_status == "PASS"){
					$order->update_status( 'processing' );
				}else{
					$order->update_status( 'not-verified' );
				}

				$username = $_POST[ 'username' ];
				$timestamp = current_time( "Y-m-d h:i:s");
				if($av_status != ''){
					update_post_meta( $post_id, '_veratad_verified', $av_status );
					add_post_meta( $post_id, '_veratad_changed_by', "$timestamp / $username / $av_status" );
				}

				$customer_id = $_POST[ 'customer_id' ];
				if($customer_id != '' || $customer_id != "0"){

					update_user_meta( $customer_id, '_veratad_verified', $_POST[ 'av_status' ]);
					add_user_meta( $customer_id, '_veratad_changed_by', "$timestamp / $username / $av_status");

			}

    }

	public function register_awaiting_verification() {

		register_post_status( 'wc-pending-v', array(
				'label'                     => 'Review ID',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Review ID (%s)', 'Review ID (%s)' )
		) );

    register_post_status( 'wc-not-verified', array(
        'label'                     => 'Not Verified',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Not Verified (%s)', 'Not Verified (%s)' )
    ) );



}


public function add_awaiting_verification_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-not-verified'] = 'Not Verified';
						$new_order_statuses['wc-pending-v'] = 'Review ID';
        }
    }

    return $new_order_statuses;
}

public function add_awaiting_verification_to_order_statuses_bulk( $bulk_actions ) {
   $bulk_actions['mark_not-verified'] = 'Change status to not verified';
	 $bulk_actions['mark_pending-v'] = 'Change status to review ID';
   return $bulk_actions;
}



		public function add_column_to_user( $columns ) {
    $columns['_veratad_verified'] = 'Age Verification';
    return $columns;
	}

	public function add_column_data_to_user( $val, $column_name, $user_id ) {

		if ( '_veratad_verified' === $column_name){
			$action = get_user_meta($user_id, '_veratad_verified', true);
			if($action){
				$action = $action;
			}else{
				$action = "NONE";
			}
			return $action;

		}

	}

			function veratad_extra_user_profile_fields( $user ) { ?>
		    <h3><?php _e("Age Verification", "blank"); ?></h3>

				<table class="form-table">
			 <tr>
					 <th><label for="status"><?php _e("Current Status: "); ?></label></th>
					 <td>
						 <?php $status = esc_attr( get_the_author_meta( '_veratad_verified', $user->ID ) ); if($status){echo $status;}else{echo "NONE";} ?>
					 </td>
			 </tr>

				</table>

				<table class="form-table">
			 <tr>
					 <th><label for="dob"><?php _e("Date of Birth: "); ?></label></th>
					 <td>
						 <?php $dob = esc_attr( get_the_author_meta( '_veratad_dob', $user->ID ) ); if($dob){echo $dob;}else{echo "Not Stored";} ?>
					 </td>
			 </tr>

				</table>
				<table class="form-table">
		    <tr>
		        <th><label for="av_status"><?php _e("Status Update");  ?></label></th>
						<td>
							<select style="width:250px;" id="av_status" name="av_status">
								 <option value="">Change the status...</option>
								 <option value="PASS">PASS</option>
								 <option value="FAIL">FAIL</option>
							</select>
						</td>
		    </tr>

				 </table>

				 <table class="form-table" style="width:500px;">
					 <tr>
							<th><label for="history"><?php _e("History"); ?></label></th>
							</tr>
						 <?php
						 //$history = get_the_author_meta( '_veratad_changed_by', $user->ID);
						 $history = get_user_meta($user->ID, '_veratad_changed_by', false);
						 //var_dump($history);
						 //echo $history;
						 if($history){
						 echo "<tr>
						 <th>Changed By</th>
						 <th>Status</th>
						 <th>Timestamp</th></tr>";
						 foreach($history as $details){
		 					$arr = explode("/", $details);
		 					$user_detail = $arr[1];
		 					$action_detail = $arr[2];
		 					$timestamp_detail = $arr[0];
		 					echo "<tr><td>$user_detail</td> <td>$action_detail</td> <td>$timestamp_detail</td></tr>";
		 				}
					}else{
						echo "<p>There is no history yet.</p>";
					}
						echo "</table>";
						  ?>



		<?php
		}

		function veratad_save_extra_user_profile_fields( $user_id ) {
			global $current_user;
    	$username = $current_user->user_login;
			$timestamp = current_time( "Y-m-d h:i:s");

		    if ( !current_user_can( 'edit_user', $user_id ) ) {
		        return false;
		    }
				$av_status = $_POST['av_status'];
				if($av_status){
					update_user_meta( $user_id, '_veratad_verified', $av_status );
				 	add_user_meta( $user_id, '_veratad_changed_by', "$timestamp / $username / $av_status" );
				}

		}


		function woocommerce_shop_order_search_veratad( $search_fields ) {

	  $search_fields[] = '_veratad_verified';

	  return $search_fields;
	}





		/**
		 * Get settings array
		 *
		 * @since 1.0.0
		 * @param string $current_section Optional. Defaults to empty string.
		 * @return array Array of settings
		 */
		public function get_settings( $current_section = '' ) {

			if ( 'agematch' == $current_section ) {

				/**
				 * Filter Plugin Section 2 Settings
				 *
				 * @since 1.0.0
				 * @param array $settings Array of the plugin settings
				 */
				$settings = apply_filters( 'veratad_agematch_settings', array(

					array(
						'name' => __( 'Additional Checkout Fields', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_fields',
					),



					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_dob_on',
						'name'     => __( 'Date of Birth', 'my-textdomain' ),
						'desc'     => __( 'Collect DOB at Checkout', 'my-textdomain' ),
						'default'  => 'yes',
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_ssn_on',
						'name'     => __( 'Last 4 SSN', 'my-textdomain' ),
						'desc'     => __( 'Collect Last 4 SSN at Checkout.', 'my-textdomain' ),
						'default'  => 'yes',
					),

					array(
						'type'     => 'select',
						'id'       => 'checkout_fields_placement',
						'name'     => __( 'Placement', 'my-textdomain' ),
						'desc_tip' => __( 'Choose where the additional fields should appear on the checkout form.', 'my-textdomain' ),
						'default' => 'woocommerce_before_checkout_form',
						'options'  => array(
                  'woocommerce_before_checkout_form' => __('Before Checkout Form'),
									'woocommerce_checkout_before_customer_details' => __('Before Checkout Customer Details'),
									'woocommerce_before_checkout_billing_form' => __('Before Checkout Billing Form'),
									'woocommerce_after_checkout_billing_form' => __('After Checkout Billing Form'),
									'woocommerce_before_order_notes' => __('Before Order Notes'),
									'woocommerce_after_order_notes' => __('After Order Notes'),
									'woocommerce_review_order_before_submit' => __('Review Order Before Submit'),
									'modal' => __('Modal Dialog'),
							)
    				),

						array(
							'type'     => 'text',
							'id'       => 'modal_click_text',
							'name'     => __( 'Modal - Click Text', 'my-textdomain' ),
							'default' => 'Edit Age Verification Fields'
						),

						array(
							'type'     => 'text',
							'id'       => 'checkout_background_color',
							'name'     => __( 'Checkout Form Background Color', 'my-textdomain' ),
							'default' => '#F8F8F8'
						),

						array(
							'type'     => 'textarea',
							'id'       => 'dob_ssn_title_text',
							'name'     => __( 'Checkout Form Title', 'my-textdomain' ),
							'desc_tip' => __( 'This is the title that will be displayed to the user before the SSN and DOB fields', 'my-textdomain' ),
							'default' => 'Age Verification'
						),

						array(
							'type'     => 'textarea',
							'id'       => 'dob_ssn_alert_text',
							'name'     => __( 'Checkout Form Intro', 'my-textdomain' ),
							'desc_tip' => __( 'This is the text that will be displayed to the user before the SSN and DOB fields', 'my-textdomain' ),
							'default' => 'Our site uses a third party for age verification. Please enter your DOB and SSN accurately.'
						),



					array(
						'type' => 'sectionend',
						'id'   => 'veratad_group1_options'
					),



					array(
						'name' => __( 'Second Attempt', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'second_attempt',
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_second_attempt_on',
						'name'     => __( 'Activate', 'my-textdomain' ),
						'desc'     => __( 'Give the user a second attempt upon initial fail', 'my-textdomain' ),
						'default'  => 'yes',
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_ssn_second_attempt_on',
						'name'     => __( 'Last 4 SSN Collect', 'my-textdomain' ),
						'desc'     => __( 'Collect Last 4 SSN on Second Attempt.', 'my-textdomain' ),
						'default'  => 'no',
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_ssn_second_attempt_required',
						'name'     => __( 'Last 4 SSN Required', 'my-textdomain' ),
						'desc'     => __( 'Require Last 4 SSN on Second Attempt.', 'my-textdomain' ),
						'default'  => 'no',
					),

					array(
						'type'     => 'textarea',
						'id'       => 'av_failure_text',
						'name'     => __( 'Failure - Block Orders', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user if they fail the AgeMatch attempt when block orders is active.', 'my-textdomain' ),
						'default' => 'Something went wrong. We were not able to verify your age. Please check your details and try again.'
					),

				

					array(
						'type'     => 'textarea',
						'id'       => 'av_failure_text_acceptance',
						'name'     => __( 'Failure Title - Order Acceptance', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user if they fail the AgeMatch attempt when order acceptance is active.', 'my-textdomain' ),
						'default' => 'Something went wrong. We were not able to verify your age.'
					),



					array(
						'type'     => 'textarea',
						'id'       => 'second_attempt_av_intro',
						'name'     => __( 'Intro Text', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user to explain the second age verification attempt', 'my-textdomain' ),
						'default' => 'We were unable to verify your age. Please check the details below and try again. Make sure you have entered your legal name and accurate Date of Birth and/or Last 4 SSN.'
					),

					array(
						'type'     => 'textarea',
						'id'       => 'second_attempt_dcams_intro',
						'name'     => __( 'DCAMS Prompt', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user if they fail AgeMatch on the second try and need to upload their ID.', 'my-textdomain' ),
						'default' => 'We were still unable to verify your age. Please click below to upload your photo ID.'
					),

					array(
						'type'     => 'textarea',
						'id'       => 'second_attempt_av_success',
						'name'     => __( 'Success - Additional Attempt', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user if they pass the second AgeMatch or DCAMS attempt.', 'my-textdomain' ),
						'default' => 'Success! You have been verified. Your order is on the way.'
					),

					array(
						'type'     => 'textarea',
						'id'       => 'second_attempt_av_failure',
						'name'     => __( 'Failure - Additional Attempt', 'my-textdomain' ),
						'desc_tip' => __( 'This is the text that will be displayed to the user if they fail the second AgeMatch or DCAMS attempt.', 'my-textdomain' ),
						'default' => 'We are still unable to verify your age. We are reviewing you document and will get back to you shortly.'
					),


					array(
						'type' => 'sectionend',
						'id'   => 'veratad_rule_entry_section'
					),

				) );

			} elseif ('general' == $current_section ||  !$current_section) {


				$settings = apply_filters( 'veratad_general_settings', array(

					array(
						'name' => __( 'Activate', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_active'
					),
					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_active',
						'name'     => __( 'Activate', 'my-textdomain' ),
						'desc'     => __( 'Turn on verification', 'my-textdomain' ),
						'default' => 'no'
					),

					array(

                'type'        => 'taxonomy',
                'id'          => 'prefix-taxonomy_02n5yb7lw898',
                'name'        => esc_html__( 'Taxonomy', 'online-generator' ),
                'taxonomy'    => 'post_tag',
                'field_type'  => 'checkbox_list',
                'std'         => ['Test'],
                'placeholder' => esc_html__( 'Test', 'online-generator' ),

					),
					array(
						'type' => 'sectionend',
						'id'   => 'veratad_active_end'
					),

					array(
						'name' => __( 'Credentials', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_credentials'
					),
					array(
						'type'     => 'text',
						'id'       => 'veratad_user',
						'name'     => __( 'Username', 'my-textdomain' ),
						'required' => true,
					),
					array(
						'type'     => 'password',
						'id'       => 'veratad_pass',
						'name'     => __( 'Password', 'my-textdomain' )
					),
					array(
						'type'     => 'text',
						'id'       => 'veratad_dcams_site_name',
						'name'     => __( 'DCAMS Site Name', 'my-textdomain' ),
						'desc_tip'     => __( 'If a valid site name is entered then document verification will be triggered upon AgeMatch failure.', 'my-textdomain' )
					),
					array(
						'type' => 'sectionend',
						'id'   => 'veratad_credentials_end'
					),
					array(
						'name' => __( 'Settings', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_settings'
					),



					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_international_exclude',
						'name'     => __( 'International Orders', 'my-textdomain' ),
						'desc'     => __( 'Exclude orders from verification if they are outside of the US', 'my-textdomain' ),
						'default' => 'no'
					),

					array(
						'type'     => 'select',
						'id'       => 'billing_or_shipping',
						'name'     => __( 'Verification Address', 'my-textdomain' ),
						'default' => 'billing',
						'options'  => array(
									'billing' => __('Billing'),
									'shipping' => __('Shipping'),
						),
						'desc_tip' => __( 'Select if you want the verification process to be performed on the billing or shipping individual.', 'my-textdomain' ),
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_order_acceptance',
						'name'     => __( 'Order Acceptance', 'my-textdomain' ),
						'desc'     => __( 'Allow order to be placed on failed age verification.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked then an order will be allowed to go through, but will be marked with the verification status. When enabled the user will get a second attempt at data and a document upload opportunity.', 'my-textdomain' ),
						'default' => 'yes'
					),
					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_shipping_verification',
						'name'     => __( 'Shipping Discrepancies', 'my-textdomain' ),
						'desc'     => __( 'Block orders when there is a different shipping name.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked customers will not be able to checkout if there is a different name on the shipping address compared to the billing address.', 'my-textdomain' ),
						'default' => 'yes'
					),
					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_customer_verification',
						'name'     => __( 'Customer Discrepancies', 'my-textdomain' ),
						'desc'     => __( 'Block orders when there is a different name on the account.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked customers will not be able to checkout if there is a different name on the shipping address or billing address compared to the verified account name.', 'my-textdomain' ),
						'default' => 'yes'
					),
					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_store_dob',
						'name'     => __( 'Date of Birth Storage', 'my-textdomain' ),
						'desc'     => __( 'Store the customer Date of Birth used for verification.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked the customer Date of Birth will be stored with their order and/or customer account.', 'my-textdomain' ),
						'default' => 'yes'
					),
					array(
						'type'     => 'text',
						'id'       => 'veratad_default_age_to_check',
						'name'     => __( 'Age To Check', 'my-textdomain' ),
						'default'  => '21+',
						'desc_tip' => __( 'Make sure that you enter the "+" sign after the age value.', 'my-textdomain' ),
					),
					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_test_mode',
						'name'     => __( 'Test Mode', 'my-textdomain' ),
						'desc'     => __( 'Turn test mode on.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked test mode will be active and AgeMatch queries will go to the test system. Please select which test case you want to use below.', 'my-textdomain' )
					),

					array(
						'type'     => 'select',
						'id'       => 'test_key',
						'name'     => __( 'Test Key', 'my-textdomain' ),
						'default' => 'general_identity',
						'options'  => array(
                  'general_identity' => __('General'),
                  'deceased2' => __('Deceased'),
									'pos_minor' => __('Possible Minor'),
									'age_not_verified' => __('Age Not Verified'),
    				),
						'desc_tip' => __( 'Select a test_key to use. Check out https://api.veratad.com for all targets associated with these keys.', 'my-textdomain' ),
					),

					array(
						'type' => 'sectionend',
						'id'   => 'veratad_credentials_end'
					),

				) );

			} elseif('dcams' == $current_section){

				$settings = apply_filters( 'veratad_dcams', array(

					array(
						'name' => __( 'Settings', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_dcams_settings',
					),


					array(
						'type'     => 'select',
						'id'       => 'dcams_default_region',
						'name'     => __( 'Default Region', 'my-textdomain' ),
						'default' => 'United States',
						'options'  => array(
                  'United States' => __('United States'),
                  'Canada' => __('Canada'),
									'Asia' => __('Asia'),
									'Australia' => __('Australia'),
									'Africa' => __('Africa'),
									'Europe' => __('Europe'),
									'Oceania' => __('Oceania'),
									'South America' => __('South America'),
    				),
						'desc_tip' => __( 'Select the default region for document collection.', 'my-textdomain' ),
					),

					array(
						'type'     => 'checkbox',
						'id'       => 'veratad_scan_dcams',
						'name'     => __( 'Scan Activation', 'my-textdomain' ),
						'desc'     => __( 'Scan the ID document.', 'my-textdomain' ),
						'desc_tip' => __( 'If checked a document scan will be attempted before storage. If not checked then the document will just be stored.', 'my-textdomain' )
					),

					array(
						'type' => 'sectionend',
						'id'   => 'veratad_group2_options'
					)

				) );
			}elseif('dcams_rules' == $current_section){





				$settings = apply_filters( 'dcams_rules', array(

					array(
						'name' => __( 'Rules By State', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_rules',
					),
					array(
						'type'     => 'select',
						'id'       => 'dcams_rule_set',
						'name'     => __( 'Default Rule Set', 'my-textdomain' ),
						'default' => 'DCAMS5_0_RuleSet_NAME_DOB',
						'options'  => array(
                  'DCAMS5_0_RuleSet_NAME_DOB' => __('Name and DOB Match'),
                  'DCAMS5_0_RuleSet_NAME' => __('Name Match'),
									'DCAMS5_0_RuleSet_NAME_ADDR' => __('Name and Address Match'),
									'DCAMS5_0_RuleSet_NAME_STATE' => __('Name and State Match'),
									'DCAMS5_0_RuleSet_NAME_STATE_DOB' => __('Name, State and DOB Match'),
									'DCAMS5_0_RuleSet_NAME_ADDR_DOB' => __('Name, Address and DOB Match'),
									'' => __('No Additional Match Rules'),
    				),
						'desc_tip' => __( 'This is the rule set used by default unless there is a state soecific rule selected.', 'my-textdomain' ),
					),


				) );

				global $woocommerce;
$countries_obj = new WC_Countries();
$states = $countries_obj->get_states('US');

$dcams_rules = array();

foreach ($states as $state_code => $state){
	$dcams_rules = array(
	'type'     => 'select',
	'default' => '',
	'options'  => array(
				'' => __('Select a state specific rule'),
				'DCAMS5_0_RuleSet_NAME' => __('Name Match'),
				'DCAMS5_0_RuleSet_NAME_DOB' => __('Name and DOB Match'),
				'DCAMS5_0_RuleSet_NAME_ADDR' => __('Name and Address Match'),
				'DCAMS5_0_RuleSet_NAME_STATE' => __('Name and State Match'),
				'DCAMS5_0_RuleSet_NAME_STATE_DOB' => __('Name, State and DOB Match'),
				'DCAMS5_0_RuleSet_NAME_ADDR_DOB' => __('Name, Address and DOB Match'),
	)
);
	$dcams_rules['name'] = $state;
	$dcams_rules['id'] = "dcams_state_rules_for_" . $state_code;
	array_push($settings, $dcams_rules);
}

$end = array(
	'type' => 'sectionend',
	'id'   => 'veratad_rules_end'
);
array_push($settings, $end);


}elseif('agematch_rules' == $current_section){





				$settings = apply_filters( 'agematch_rules', array(

					array(
						'name' => __( 'Rules By State', 'my-textdomain' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'veratad_rules',
					),
					array(
						'type'     => 'select',
						'id'       => 'veratad_rules',
						'name'     => __( 'Default Ruleset', 'my-textdomain' ),
						'desc_tip' => __( 'Choose a rule set to require elements from the order to match data found in the sources.', 'my-textdomain' ),
						'default' => 'AgeMatch5_0_RuleSet_YOB',
						'options'  => array(
									'AgeMatch5_0_RuleSet_YOB' => __('Name and Year of Birth Match'),
									'AgeMatch5_0_RuleSet_SSN' => __('Name and SSN Match'),
									'AgeMatch5_0_RuleSet_YOB_SSN' => __('Name, Year of Birth and SSN Match'),
									'AgeMatch5_0_RuleSet_DOB' => __('Name and Date of Birth Match'),
									'AgeMatch5_0_RuleSet_DOB_SSN' => __('Name, Date of Birth and SSN Match'),
									'AgeMatch5_0_RuleSet_ADDR' => __('Name and Address Match'),
									'AgeMatch5_0_RuleSet_ADDR_YOB' => __('Name, Address and Year of Birth Match'),
									'AgeMatch5_0_RuleSet_ADDR_YOB_SSN' => __('Name, Address, Year of Birth and SSN Match'),
									'AgeMatch5_0_RuleSet_ADDR_DOB_SSN_PHONE' => __('Name, Address, Date of Birth, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_ADDR_YOB_SSN_PHONE' => __('Name, Address, Year of Birth, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_ADDR_DOB_PHONE' => __('Name, Address, Date of Birth and Phone Match'),
									'AgeMatch5_0_RuleSet_ADDR_YOB_PHONE' => __('Name, Address, Year of Birth and Phone Match'),
									'AgeMatch5_0_RuleSet_ADDR_SSN_PHONE' => __('Name, Address, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_ADDR_PHONE' => __('Name, Address and Phone Match'),
									'AgeMatch5_0_RuleSet_YOB_SSN_PHONE' => __('Name, Year of Birth, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_DOB_SSN_PHONE' => __('Name, Date of Birth, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_YOB_PHONE' => __('Name, Year of Birth and Phone Match'),
									'AgeMatch5_0_RuleSet_DOB_PHONE' => __('Name, Date of Birth and Phone Match'),
									'AgeMatch5_0_RuleSet_SSN_PHONE' => __('Name, SSN and Phone Match'),
									'AgeMatch5_0_RuleSet_PHONE' => __('Name and Phone Match'),
									'' => __('No Additional Match Rules'),
						),
					),


				) );

				global $woocommerce;
$countries_obj = new WC_Countries();
$states = $countries_obj->get_states('US');

$agematch_rules = array();

foreach ($states as $state_code => $state){
	$agematch_rules = array(
	'type'     => 'select',
	'default' => '',
	'options'  => array(
				'AgeMatch5_0_RuleSet_YOB' => __('Name and Year of Birth Match'),
				'AgeMatch5_0_RuleSet_SSN' => __('Name and SSN Match'),
				'AgeMatch5_0_RuleSet_YOB_SSN' => __('Name, Year of Birth and SSN Match'),
				'AgeMatch5_0_RuleSet_DOB' => __('Name and Date of Birth Match'),
				'AgeMatch5_0_RuleSet_DOB_SSN' => __('Name, Date of Birth and SSN Match'),
				'AgeMatch5_0_RuleSet_ADDR' => __('Name and Address Match'),
				'AgeMatch5_0_RuleSet_ADDR_YOB' => __('Name, Address and Year of Birth Match'),
				'AgeMatch5_0_RuleSet_ADDR_YOB_SSN' => __('Name, Address, Year of Birth and SSN Match'),
				'AgeMatch5_0_RuleSet_ADDR_DOB_SSN_PHONE' => __('Name, Address, Date of Birth, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_ADDR_YOB_SSN_PHONE' => __('Name, Address, Year of Birth, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_ADDR_DOB_PHONE' => __('Name, Address, Date of Birth and Phone Match'),
				'AgeMatch5_0_RuleSet_ADDR_YOB_PHONE' => __('Name, Address, Year of Birth and Phone Match'),
				'AgeMatch5_0_RuleSet_ADDR_SSN_PHONE' => __('Name, Address, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_ADDR_PHONE' => __('Name, Address and Phone Match'),
				'AgeMatch5_0_RuleSet_YOB_SSN_PHONE' => __('Name, Year of Birth, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_DOB_SSN_PHONE' => __('Name, Date of Birth, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_YOB_PHONE' => __('Name, Year of Birth and Phone Match'),
				'AgeMatch5_0_RuleSet_DOB_PHONE' => __('Name, Date of Birth and Phone Match'),
				'AgeMatch5_0_RuleSet_SSN_PHONE' => __('Name, SSN and Phone Match'),
				'AgeMatch5_0_RuleSet_PHONE' => __('Name and Phone Match'),
				'' => __('Select a state specific match rule'),
	)
);
	$agematch_rules['name'] = $state;
	$agematch_rules['id'] = "agematch_state_rules_for_" . $state_code;
	array_push($settings, $agematch_rules);
}

$end = array(
	'type' => 'sectionend',
	'id'   => 'veratad_rules_end'
);
array_push($settings, $end);


			}


//echo "<pre>";
//print_r($settings);
			/**
			 * Filter veratad Settings
			 *
			 * @since 1.0.0
			 * @param array $settings Array of the plugin settings
			 */

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

		}

		public function get_sections() {
			global $current_section;
			$sections  = array(
				'general' => 'General',
				'agematch' => 'AgeMatch',
				'agematch_rules' => 'AgeMatch Rules',
				'dcams' => 'DCAMS',
				'dcams_rules' => 'DCAMS Rules',
			);

			echo '<ul class="subsubsub">';
			$array_keys = array_keys( $sections );

			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}

			echo '</ul><br class="clear" />';
		}




		public function output() {

			global $current_section;

			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}


		public function save() {

			global $current_section;
			if ($current_section == '' || $current_section == 'general'){

			$user = $_POST['veratad_user'];
			$pass = $_POST['veratad_pass'];
			$age = $_POST['veratad_default_age_to_check'];

			$valid = $this->api->check_api_options($user, $pass, $age);

			if($valid == "success"){
				$settings = $this->get_settings( $current_section );
				WC_Admin_Settings::save_fields( $settings );
			}else{
				WC_Admin_Settings::add_error( $valid );
			}
		}else{
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		}
		}

	}

endif;
