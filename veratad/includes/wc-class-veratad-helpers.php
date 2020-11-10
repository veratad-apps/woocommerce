<?php

if ( ! defined( 'ABSPATH' ) ) {
  echo "This is a plugin. If you need information visit www.veratad.com";
  exit; // Exit if accessed directly
}

    if ( ! class_exists( 'WC_Veratad_Helpers' ) ) :

      class WC_Veratad_Helpers {

        private $options;

        public function __construct( WC_Veratad_Options $options  ) {
    			$this->options = $options;
    		}

        public function get_api_specs( $state ){

          error_log("the state in get specs is $state");
          $specs = array(
            'user' => $this->options->get_veratad_user(),
            'pass' => $this->options->get_veratad_pass(),
            'rules' => $this->options->get_veratad_rules( $state ),
            'test_mode' => $this->options->get_veratad_test_mode(),
            'test_key' => $this->options->get_test_key()
          );

          error_log(json_encode($specs));
          return $specs;
        }

        public function get_target( $type ) {
          $target = array();
          $target = array(
              'fn' => $_POST[''.$type.'_first_name'],
              'ln' => $_POST[''.$type.'_last_name'],
              'addr' => $_POST[''.$type.'_address_1'],
              'city' => $_POST[''.$type.'_city'],
              'state' => $_POST[''.$type.'_state'],
              'zip' => $_POST[''.$type.'_postcode'],
              'dob' => $_POST['veratad_billing_dob'],
              'ssn' => $_POST['veratad_billing_ssn'],
              'phone' => $_POST[''.$type.'_phone'],
              'email' => $_POST[''.$type.'_email'],
              'age' => $this->options->get_veratad_default_age_to_check()
          );

          return $target;
        }

        public function store_dob_order($order_id, $dob){

          if($this->options->get_veratad_store_dob()){
            $order = wc_get_order( $order_id );
            $order->update_meta_data( '_veratad_dob', $dob );
            $order->save();
            $customer_id = get_current_user_id();
            if(is_user_logged_in()){
              update_user_meta($customer_id, '_veratad_dob', $dob );
            }
          }

        }

        public function is_verified_veratad() {

          $address_type = $this->options->get_billing_or_shipping();
          $target = $this->get_target($address_type);
          $reference = $target['email'];
          $dob = $target['dob'];

          $state = $target['state'];
          $specs = $this->get_api_specs( $state );


          if (strpos($dob, '-') !== false) {
            $dob_type = "YYYY-MM-DD";
          }else{
            $dob_type = "MMDDYYYY";
          }

          $target['dob_type'] = $dob_type;

          $test_mode = $specs['test_mode'];

          if($test_mode){
            $target['test_key'] = $specs['test_key'];
          }

          $req_array  = array(
            'user'  => $specs['user'],
            'pass' => $specs['pass'],
            'service' => 'AgeMatch5.0',
            'reference' => $reference,
            'rules' => $specs['rules'],
            'target' => $target
          );

          $req =  json_encode(new \ArrayObject($req_array));


          $post = wp_remote_post("https://production.idresponse.com/process/comprehensive/gateway", array(
            'method'      => 'POST',
            'timeout'     => 20,
            'httpversion' => '1.1',
            'headers'     => array(
              'Content-Type' => 'application/json'
            ),
            'body'        => $req
          ));

          $res = json_decode($post['body']);

          if($res->result->action != "PASS"){
            return false;
          }else{
            return true;
          }

        }

        public function changed_by_api($order_id, $av_status, $service){
          $timestamp = current_time( "Y-m-d h:i:s");
          add_post_meta( $order_id, '_veratad_changed_by', "$timestamp / $service / $av_status" );
        }

        public function changed_by_api_user($customer_id, $av_status, $service){
          $timestamp = current_time( "Y-m-d h:i:s");
          add_user_meta( $customer_id, '_veratad_changed_by', "$timestamp / $service / $av_status" );
        }

      }

      endif;
