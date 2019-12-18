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


          public function get_veratad_test_mode() {
      			$res = get_option( self::$veratad_test_mode);
            if($res === "yes"){
              return true;
            }
      		}

          public function get_test_key() {
      			return get_option( self::$test_key );
      		}

          public function get_dcams_default_region() {
      			return get_option( self::$dcams_default_region );
      		}


          public function get_second_attempt_dcams_intro() {
      			return get_option( self::$second_attempt_dcams_intro );
      		}

          public function get_second_attempt_av_intro() {
      			return get_option( self::$second_attempt_av_intro );
      		}

          public function get_second_attempt_av_success() {
      			return get_option( self::$second_attempt_av_success );
      		}

          public function get_second_attempt_av_failure() {
            return get_option( self::$second_attempt_av_failure);
          }

          public function get_av_failure_text_acceptance() {
      			return get_option( self::$av_failure_text_acceptance );
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
      			return get_option( self::$dcams_rules );
      		}

          public function get_veratad_rules() {
      			return get_option( self::$rules );
      		}

          public function get_veratad_ssn_second_attempt_on() {
      			$res = get_option( self::$veratad_ssn_second_attempt_on);
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_scan_dcams() {
      			$res = get_option( self::$veratad_scan_dcams);
            if($res === "yes"){
              return "true";
            }else{
              return "false";
            }
      		}

          public function get_veratad_store_dob() {
      			$res = get_option( self::$veratad_store_dob);
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_shipping_verification() {
      			$res =  get_option( self::$veratad_shipping_verification );
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_customer_verification() {
      			$res = get_option( self::$veratad_customer_verification );
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_default_age_to_check() {
      			return get_option( self::$default_age_to_check );
      		}

          public function get_veratad_ssn_dob_text() {
      			return get_option( self::$dob_ssn_text);
      		}

          public function get_veratad_av_failure_text() {
      			return get_option( self::$av_failure_text);
      		}

          public function get_veratad_dob_collect() {
      			$res = get_option( self::$collect_dob);
            if($res === "yes"){
              return true;
            }
      		}

          public function get_veratad_ssn_collect() {
            $res = get_option( self::$collect_ssn);
            if($res === "yes"){
              return true;
            }
          }

          public function get_veratad_order_acceptance() {
            $res = get_option( self::$order_acceptance);
            if($res === "yes"){
              return true;
            }
          }
      }

      endif;
