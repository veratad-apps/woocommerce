<?php

if ( ! defined( 'ABSPATH' ) ) {
  echo "This is a plugin. If you need information visit www.veratad.com";
  exit; // Exit if accessed directly
}

    if ( ! class_exists( 'WC_Veratad_Options' ) ) :

      class WC_Veratad_Options {

          public static $username = 'veratad_user';
          public static $password = 'veratad_pass';
          public static $rules = 'veratad_rules';
          public static $default_age_to_check = 'veratad_default_age_to_check';
          public static $dob_ssn_text = 'dob_ssn_alert_text';
          public static $collect_dob = 'veratad_dob_on';
          public static $collect_ssn = 'veratad_ssn_on';
          public static $av_failure_text = 'av_failure_text';
          public static $order_acceptance = 'veratad_order_acceptance';
          public static $veratad_shipping_verification = 'veratad_shipping_verification';
          public static $veratad_customer_verification = 'veratad_customer_verification';
          public static $dcams_site = 'veratad_dcams_site_name';
          public static $dcams_rules = 'dcams_rule_set';
          public static $veratad_ssn_second_attempt_on = 'veratad_ssn_second_attempt_on';
          public static $av_failure_text_acceptance = 'av_failure_text_acceptance';
          public static $second_attempt_av_success = 'second_attempt_av_success';
          public static $second_attempt_av_failure = 'second_attempt_av_failure';
          public static $second_attempt_av_intro = 'second_attempt_av_intro';
          public static $second_attempt_dcams_intro = 'second_attempt_dcams_intro';
          public static $veratad_store_dob = 'veratad_store_dob';
          public static $veratad_scan_dcams = 'veratad_scan_dcams';
          public static $dcams_default_region = 'dcams_default_region';
          public static $veratad_test_mode = 'veratad_test_mode';
          public static $test_key = 'test_key';
          public static $checkout_fields_placement = 'checkout_fields_placement';
          public static $dob_ssn_title_text = 'dob_ssn_title_text';
          public static $checkout_background_color = 'checkout_background_color';
          public static $veratad_categories = 'veratad_categories';
          public static $veratad_popup_activation = 'veratad_popup_activation';
          public static $popup_header_text = 'popup_header_text';
          public static $popup_resetting_text = 'popup_resetting_text';
          public static $popup_underage_button = 'popup_underage_button';
          public static $popup_overage_button = 'popup_overage_button';
          public static $veratad_underage_url = 'veratad_underage_url';
          public static $av_attempts_text = 'av_attempts_text';
          public static $modal_click_text = 'modal_click_text';


          public function get_modal_click_text() {
      			return get_option( self::$modal_click_text, 'Edit Age Verification Fields' );
      		}


          public function get_av_attempts_text() {
      			return get_option( self::$av_attempts_text, 'You have failed age verification and have no more attempts left.' );
      		}

          public function get_veratad_underage_url() {
      			return get_option( self::$veratad_underage_url, 'https://google.com' );
      		}

          public function get_popup_underage_button() {
      			return get_option( self::$popup_underage_button, 'No' );
      		}

          public function get_popup_overage_button() {
      			return get_option( self::$popup_overage_button, 'Yes' );
      		}

          public function get_popup_resetting_text() {
      			return get_option( self::$popup_resetting_text, 'Thank you...' );
      		}

          public function get_popup_header_text() {
      			return get_option( self::$popup_header_text, 'Are you 21 or older?' );
      		}

          public function get_veratad_categories() {
      			return get_option( self::$veratad_categories, array("no_filter") );
      		}

          public function get_veratad_popup_activation() {
      			$res = get_option( self::$veratad_popup_activation, 'yes');
            if($res === "yes"){
              return true;
            }
      		}


          public function get_veratad_test_mode() {
      			$res = get_option( self::$veratad_test_mode, "yes");
            if($res === "yes"){
              return true;
            }
      		}

          public function get_test_key() {
      			return get_option( self::$test_key, "general_identity" );
      		}

          public function get_checkout_background_color() {
      			return get_option( self::$checkout_background_color, "#F8F8F8"  );
      		}

          public function get_dob_ssn_title_text() {
      			return get_option( self::$dob_ssn_title_text, 'Age Verification' );
      		}

          public function get_checkout_fields_placement() {
      			return get_option( self::$checkout_fields_placement,'woocommerce_before_checkout_form' );
      		}

          public function get_dcams_default_region() {
      			return get_option( self::$dcams_default_region, 'United States' );
      		}


          public function get_second_attempt_dcams_intro() {
      			return get_option( self::$second_attempt_dcams_intro, 'We were still unable to verify your age. Please click below to upload your photo ID.' );
      		}

          public function get_second_attempt_av_intro() {
      			return get_option( self::$second_attempt_av_intro, 'We were unable to verify your age. Please check the details below and try again. Make sure you have entered your legal name and accurate Date of Birth and/or Last 4 SSN.' );
      		}

          public function get_second_attempt_av_success() {
      			return get_option( self::$second_attempt_av_success, 'Success! You have been verified. Your order is on the way.' );
      		}

          public function get_second_attempt_av_failure() {
            return get_option( self::$second_attempt_av_failure, 'We are still unable to verify your age. We are reviewing you document and will get back to you shortly.');
          }

          public function get_av_failure_text_acceptance() {
      			return get_option( self::$av_failure_text_acceptance, 'Something went wrong. We were not able to verify your age.' );
      		}


          public function get_veratad_user() {
      			return get_option( self::$username );
      		}


          public function get_veratad_pass() {
      			return get_option( self::$password );
      		}

          public function get_dcams_site() {
      			return get_option( self::$dcams_site );
      		}

          public function get_dcams_rules() {
      			return get_option( self::$dcams_rules, '' );
      		}

          public function get_veratad_rules() {
      			return get_option( self::$rules, '' );
      		}

          public function get_veratad_ssn_second_attempt_on() {
      			$res = get_option( self::$veratad_ssn_second_attempt_on, 'yes');
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_scan_dcams() {
      			$res = get_option( self::$veratad_scan_dcams, 'yes');
            if($res === "yes"){
              return "true";
            }else{
              return "false";
            }
      		}

          public function get_veratad_store_dob() {
      			$res = get_option( self::$veratad_store_dob, 'yes');
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_shipping_verification() {
      			$res =  get_option( self::$veratad_shipping_verification, 'yes' );
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_customer_verification() {
      			$res = get_option( self::$veratad_customer_verification, 'yes' );
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_default_age_to_check() {
      			return get_option( self::$default_age_to_check, '21+' );
      		}

          public function get_veratad_ssn_dob_text() {
      			return get_option( self::$dob_ssn_text);
      		}

          public function get_veratad_av_failure_text() {
      			return get_option( self::$av_failure_text, 'Something went wrong. We were not able to verify your age. Please check your details and try again.');
      		}

          public function get_veratad_dob_collect() {
      			$res = get_option( self::$collect_dob, 'yes');
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_ssn_collect() {
            $res = get_option( self::$collect_ssn, 'yes');
            if($res === "yes"){
              return true;
            }
          }

          public function get_veratad_order_acceptance() {
            $res = get_option( self::$order_acceptance, 'yes');
            if($res === "yes"){
              return true;
            }
          }
      }

      endif;
