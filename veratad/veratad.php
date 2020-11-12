<?php

/**
* Plugin Name: Veratad for WooCommerce
* Plugin URI: https://www.veratad.com
* Description: Age and Identity Verification
* Version: 2.0.1
* Author: The Veratad App Team
* Author URI: https://www.veratad.com
* License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
  echo "This is a plugin. If you need information visit www.veratad.com";
  exit; // Exit if accessed directly
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )
  && ! class_exists( 'WCVeratad' ) ) :

  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-admin.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-api.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-options.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-checkout-fields.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-products.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-customer.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-helpers.php' );
  require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-order.php' );

  class WC_Veratad_App {

    public function run() {


      $options = new WC_Veratad_Options();
      $helpers = new WC_Veratad_Helpers( $options );
      $customer = new WC_Veratad_Customer( $helpers );
      $checkout = new WC_Veratad_Checkout_Fields( $options, $customer );
      $api = new WC_Veratad_Api( $options, $checkout );
      $products = new WC_Veratad_Products( $options );
      $admin = new WC_Veratad_Admin($api);
      $order = new WC_Veratad_Order( $helpers, $options, $checkout );

      //add age verification action to order edit view
      add_action( 'add_meta_boxes', array($admin,'av_add_meta_boxes') );

      //save age verification action on order edit view when changed
      add_action( 'save_post', array($admin,'av_save_wc_order_other_fields'), 10, 1 );

      //set callback for DCAMS
      add_action( 'woocommerce_api_dcams', array( $api, 'dcams_callback' ) );

      //gives the abiltity to search orders by veratad action i.e. PASS, FAIL, PENDING
      add_filter( 'woocommerce_shop_order_search_fields', array($admin,'woocommerce_shop_order_search_veratad') );

      //add to order email
      add_action( 'woocommerce_email_order_details', array($api, 'veratad_email'), 10, 4 );

      //add column to order view
      add_filter( 'manage_edit-shop_order_columns', array($admin, 'add_column_to_order'));

      //add awating verification status
      $admin->register_awaiting_verification();
      add_filter( 'wc_order_statuses', array($admin, 'add_awaiting_verification_to_order_statuses') );
      add_filter( 'bulk_actions-edit-shop_order', array($admin, 'add_awaiting_verification_to_order_statuses_bulk') );

      //add data to order column
      add_action( 'manage_shop_order_posts_custom_column', array($admin, 'add_column_data_to_order') );

      //add column to user view
      add_filter( 'manage_users_columns', array($admin, 'add_column_to_user') );

      // add data to user column
      add_filter( 'manage_users_custom_column', array($admin, 'add_column_data_to_user'),10, 3 );

      //add custom field to user profile view
      add_action( 'show_user_profile', array($admin,'veratad_extra_user_profile_fields') );
      add_action( 'edit_user_profile', array($admin,'veratad_extra_user_profile_fields') );

      //update user fields from profile
      add_action( 'personal_options_update', array($admin,'veratad_save_extra_user_profile_fields') );
      add_action( 'edit_user_profile_update', array($admin,'veratad_save_extra_user_profile_fields') );

      //check if the app is active
      $active = $options->get_veratad_active();

      if($active){

        if($options->get_veratad_second_attempt_on()){

          add_action('wp_head', 'modal_css');

          function modal_css(){

            ?>
            <style>

            .veratad-modal-woo {
              display: none; /* Hidden by default */
              position: fixed; /* Stay in place */
              z-index: 99999; /* Sit on top */
              left: 0;
              top: 0;
              width: 100%; /* Full width */
              height: 100%; /* Full height */
              overflow: auto; /* Enable scroll if needed */
              background-color: rgb(0,0,0); /* Fallback color */
              background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }

            /* Modal Content/Box */
            .veratad-modal-content-woo {
              background-color: #fefefe;
              margin: 5% auto; /* 15% from the top and centered */
              padding: 20px;
              border: 1px solid #888;
              width: 50%; /* Could be more or less, depending on screen size */
            }


            </style>

            <?php
          }

          // add the av message to thank you page when order acceptance is active
          add_action( 'woocommerce_thankyou_order_received_text', array($api, 'veratad_add_message_to_thank_you') );

          //add the JS script for the second attempt agematch and dcams
          //add_action( 'woocommerce_thankyou_order_received_text', array($api, 'add_top_messages') );


        //add the JS script for the second attempt agematch and dcams
        add_action( 'woocommerce_thankyou', array($api, 'add_second_attempt_script') );

        //add JS to checkout
        //add_action( 'woocommerce_after_checkout_form', array($api, 'add_checkout_script') );

        //AgeMatch Second attempt AJAX request
        add_action( 'wp_ajax_veratad_ajax_request', array($api,'veratad_ajax_agematch_second_attempt') );
        add_action( 'wp_ajax_nopriv_veratad_ajax_request', array($api,'veratad_ajax_agematch_second_attempt'));
      }

        //register changes to the account
        $customer->detect_customer_changes();

        //customer checkout flow
        if(is_user_logged_in()){
          add_action( 'woocommerce_thankyou', array($customer, 'update_av_status') );
          if(!$customer->av_success()){
            //validate DOB field on checkout when order acceptance is active
            if($options->get_veratad_dob_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_dob'));
            }

            //validate SSN field on checkout when order acceptance is active
            if($options->get_veratad_ssn_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_ssn'));
            }
            add_action( 'woocommerce_new_order', array($order, 'verify') );
          }else{
            add_action( 'woocommerce_new_order', array($order, 'check_customer_match') );
            add_action( 'woocommerce_new_order', array($order, 'veratad_order_data_save_already_verified') );
          }

        }

        //guest checkout flow
        if(!is_user_logged_in()){
          if(!$options->get_veratad_order_acceptance()){
              add_action( 'woocommerce_checkout_process', array($order, 'verify_and_block') );
              add_action( 'woocommerce_new_order', array($order, 'update_order') );
            }else{
              if($options->get_veratad_shipping_verification()){
              $block = $api->block_order_if_different_name();
            }
            //validate DOB field on checkout when order acceptance is active
            if($options->get_veratad_dob_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_dob'));
            }

            //validate SSN field on checkout when order acceptance is active
            if($options->get_veratad_ssn_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_ssn'));
            }
              add_action( 'woocommerce_new_order', array($order, 'verify') );
            }
        }

        //checkout fields


        //add block order for name discrepancies when order acceptnace is set to "no"
        if($options->get_veratad_shipping_verification()){
          $block = $api->block_order_if_different_name();
          if(!$block){
              add_action( 'woocommerce_new_order', array($order, 'verify') );
          }
        }



        //placement of AV on checkout page
        $placement = $options->get_checkout_fields_placement();



        if($placement == 'modal'){
          add_action('woocommerce_after_checkout_form', array($checkout, 'add_modal_av_form_html'));
          add_action('woocommerce_after_checkout_billing_form', array($checkout, 'add_checkout_modal_edit'));
        }

        //add new checkout fields description text
        if($options->get_veratad_ssn_collect() || $options->get_veratad_dob_collect()){
            add_action($placement, array($checkout, 'veratad_add_additional_fields_intro'), 9 );
            if($placement == "modal"){
              add_action('wp_head', array($checkout, 'add_veratad_checkout_modal_script') );
            }
        }

        // add the DOB field to checkout
        if($options->get_veratad_dob_collect()){
          if($placement != "modal"){
            add_action($placement, array($checkout, 'veratad_add_dob_billing'), 9 );
          }
            add_action('woocommerce_after_checkout_billing_form', array($checkout, 'add_dob_checkout_hidden_field') );
            add_action('woocommerce_after_checkout_form', array($checkout, 'add_dob_script') );
        }

        // add the SSN field to checkout
        if($options->get_veratad_ssn_collect()){
          if($placement != "modal"){
            add_action($placement, array($checkout, 'veratad_add_ssn_billing'), 9 );
          }
          add_action('woocommerce_after_checkout_billing_form', array($checkout, 'add_ssn_checkout_hidden_field') );
          add_action('woocommerce_after_checkout_form', array($checkout, 'add_ssn_script') );
        }



      }

    }
  }

  $wc_veratad_app = new WC_Veratad_App();
  add_action( 'init', array( $wc_veratad_app, 'run' ) );

endif;
