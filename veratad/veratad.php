<?php

/**
* Plugin Name: Veratad for WooCommerce
* Plugin URI: https://www.veratad.com
* Description: Age and Identity Verification
* Version: 1.0.1
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

      // make sure session is started as soon as possible
      if ( session_status() != PHP_SESSION_ACTIVE ) {
        session_start();
      }

      require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-admin.php' );
      require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-api.php' );
      require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-options.php' );
      require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-checkout-fields.php' );
      require_once( dirname( __FILE__ ) . '/includes/wc-class-veratad-products.php' );

      class WC_Veratad_Plugin {

        public function run() {

          $options = new WC_Veratad_Options();
          $checkout = new WC_Veratad_Checkout_Fields( $options );
          $api = new WC_Veratad_Api( $options, $checkout );
          $products = new WC_Veratad_Products( $options );
          $admin = new WC_Veratad_Admin($api);

          //add to order email

          add_action( 'woocommerce_email_order_details', array($api, 'veratad_email'), 10, 4 );

          //add column to order view
          add_filter( 'manage_edit-shop_order_columns', array($admin, 'add_column_to_order'));

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

          $hide = $_SESSION['hide_underage'];
          if($hide != "true"){
            // add the av message to thank you page when order acceptance is active
            add_action( 'woocommerce_thankyou_order_received_text', array($api, 'veratad_add_message_to_thank_you') );
          }

          //add the JS script for the second attempt agematch and dcams
          add_action( 'woocommerce_thankyou_order_received_text', array($api, 'add_second_attempt_script') );

          //add JS to checkout
          add_action( 'woocommerce_after_checkout_form', array($api, 'add_checkout_script') );

          //AgeMatch Second attempt AJAX request
          add_action( 'wp_ajax_veratad_ajax_request', array($api,'veratad_ajax_agematch_second_attempt') );
          add_action( 'wp_ajax_nopriv_veratad_ajax_request', array($api,'veratad_ajax_agematch_second_attempt'));




          if($options->get_veratad_popup_activation()){

          //add popup

          add_action('wp_head', 'modal_css');
          add_action('wp_head', array($products, 'add_veratad_popup_ajax'));

          add_action('wp_footer', array($products,'add_popup_html'));
          add_action( 'wp_ajax_my_ajax_request', array($products,'veratad_handle_ajax_request') );
          add_action( 'wp_ajax_nopriv_my_ajax_request', array($products,'veratad_handle_ajax_request'));

          function modal_css(){

            ?>
            <style>
                        .blocker {
                position: fixed !important;
                z-index: 10 !important;
            }

            </style>

            <?php
          }


          add_filter( 'get_terms', array($products, 'veratad_get_subcategory_terms'), 10, 3 );
          add_action( 'woocommerce_product_query', array($products, 'veratad_hide_products_category_shop') );

          //return 404 for hidden roducts when underage
          add_action('wp', array($products,'prevent_access_to_product_page' ));

          }


          //set callback for DCAMS
          add_action( 'woocommerce_api_dcams', array( $api, 'dcams_callback' ) );

          //gives the abiltity to search orders by veratad action i.e. PASS, FAIL, PENDING
          add_filter( 'woocommerce_shop_order_search_fields', array($admin,'woocommerce_shop_order_search_veratad') );

          //add age verification action to order edit view
          add_action( 'add_meta_boxes', array($admin,'av_add_meta_boxes') );

          //save age verification action on order edit view when changed
          add_action( 'save_post', array($admin,'av_save_wc_order_other_fields'), 10, 1 );

          //order acceptance actions
          if(!$options->get_veratad_order_acceptance()){
            //order not acceptance process
              add_action( 'woocommerce_checkout_process', array($api, 'handle_api_response_not_order_acceptance') );
              add_action( 'woocommerce_new_order', array($api, 'not_order_acceptance_update_order') );
          }else{

            //validate DOB field on checkout when order acceptance is active
            if($options->get_veratad_dob_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_dob'));
            }

            //validate SSN field on checkout when order acceptance is active
            if($options->get_veratad_ssn_collect()){
                add_action('woocommerce_checkout_process', array($checkout, 'validate_ssn'));
            }
            //add block order for name discrepancies when order acceptnace is set to "no"
            if($options->get_veratad_shipping_verification()){
              $block = $api->block_order_if_different_name();
              if(!$block){
                add_action( 'woocommerce_new_order', array($api, 'handle_api_response_order_acceptance') );
              }
            }elseif($options->get_veratad_customer_verification()){
              if(is_user_logged_in()){
                $block = $api->block_order_if_different_name_on_account();
                if(!$block){
                  add_action( 'woocommerce_new_order', array($api, 'handle_api_response_order_acceptance') );
                }
              }
            }else{
              add_action( 'woocommerce_new_order', array($api, 'handle_api_response_order_acceptance') );
            }

          }


        }


      }

      $wc_veratad_plugin = new WC_Veratad_Plugin();
      add_action( 'init', array( $wc_veratad_plugin, 'run' ) );

endif;
