<?php

if ( ! defined( 'ABSPATH' ) ) {
  echo "This is a plugin. If you need information visit www.veratad.com";
  exit; // Exit if accessed directly
}

    if ( ! class_exists( 'WC_Veratad_Order' ) ) :

      class WC_Veratad_Order {

        private $helpers;
        private $options;
        private $checkout;

        public function __construct( WC_Veratad_Helpers $helpers, WC_Veratad_Options $options, WC_Veratad_Checkout_Fields $checkout ) {
    			$this->helpers = $helpers;
          $this->options = $options;
          $this->checkout = $checkout;
    		}



        public function verify( $order_id ){

          $on = $this->checkout->age_verification_on( $checkout );

          if($on){

          $order = wc_get_order( $order_id );
          $country = ($this->options->get_billing_or_shipping() == "billing" ? $order->get_billing_country() : $order->get_shipping_country());
          $international_exclude = $this->options->get_veratad_international_exclude();
          if(($country == "US" && $international_exclude) || !$international_exclude){
          $dob = $_POST['veratad_billing_dob'];
          $this->helpers->store_dob_order($order_id, $dob);
          $is_verified = $this->helpers->is_verified_veratad();
          if($is_verified){
            $this->veratad_order_data_save_pass();

          //if customer update details on PASS
          if(is_user_logged_in()){
            $address_type = $this->options->get_billing_or_shipping();
            if($address_type == "billing"){
              $fn = $order->get_billing_first_name();
              $ln = $order->get_billing_last_name();
            }else{
              $fn = $order->get_shipping_first_name();
              $ln = $order->get_shipping_last_name();
            }
            $customer_id = get_current_user_id();
            update_user_meta( $customer_id, 'first_name', $fn);
            update_user_meta( $customer_id, 'last_name', $ln);
          }

          }else{
            $this->veratad_order_data_save_fail();
          }
        }
      }

      }

        public function verify_and_block(){

          $country = ($this->options->get_billing_or_shipping() == "billing" ? $_POST['billing_country'] : $_POST['shipping_country']);

          $international_exclude = $this->options->get_veratad_international_exclude();

          if(($country == "US" && $international_exclude) || !$international_exclude){

          $is_verified = $this->helpers->is_verified_veratad();

          if(!$is_verified){
            wc_add_notice($this->options->get_veratad_av_failure_text(), 'error');
          }

        }


      }

      public function update_order($order_id){

        $target = $this->helpers->get_target('billing');

        $dob = $target['dob'];

        $order = wc_get_order( $order_id );

        if($this->options->get_veratad_store_dob()){
          $order->update_meta_data( '_veratad_dob', $dob );
          $order->save();
        }

        $billing_country = $order->get_billing_country();
        $international_exclude = $this->options->get_veratad_international_exclude();
        if(($billing_country == "US" && $international_exclude) || !$international_exclude){
          $this->veratad_order_data_save_pass();
        }

    }

        public function check_customer_match( $order_id ){

          $order = wc_get_order( $order_id );

          $customer_id = get_current_user_id();

          $customer_fn = strtolower(get_user_meta( $customer_id, 'first_name', true));
          $customer_ln = strtolower(get_user_meta( $customer_id, 'last_name', true));
          $customer_name = $customer_fn . $customer_ln;

          $customer_billing_addr = strtolower(get_user_meta( $customer_id, 'billing_address_1', true));
          $customer_shipping_addr = strtolower(get_user_meta( $customer_id, 'shipping_address_1', true));


          $billing_fn = strtolower($order->get_billing_first_name());
          $billing_ln = strtolower($order->get_billing_last_name());
          $billing_name = $billing_fn . $billing_ln;
          $billing_addr = strtolower($order->get_billing_address_1());

          $shipping_fn = strtolower($order->get_shipping_first_name());
          $shipping_ln = strtolower($order->get_shipping_last_name());
          $shipping_name = $shipping_fn . $shipping_ln;
          $shipping_addr = strtolower($order->get_shipping_address_1());

          if(($customer_name == $billing_name) && ($billing_name == $shipping_name) && ($customer_billing_addr == $billing_addr) && ($customer_shipping_addr == $shipping_addr)){
            $match = true;
          }else{
            $match = false;
          }

          if(!$match){
            $this->verify( $order_id );
          }


        }



        public function veratad_order_data_save_pass() {
          add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
            $order = wc_get_order( $order_id );
            $order->update_meta_data( '_veratad_verified', 'PASS' );
            $order->save();
            $this->helpers->changed_by_api($order_id, 'PASS', 'AGEMATCH');
        } , 10, 2);
        }

        public function veratad_order_data_save_already_verified() {
          add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
            $order = wc_get_order( $order_id );
            $order->update_meta_data( '_veratad_verified', 'PASS' );
            $order->save();
            $this->helpers->changed_by_api($order_id, 'PASS', 'ALREADY VERIFIED');
        } , 10, 2);
        }

        public function veratad_order_data_save_fail() {
          add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
            $order = wc_get_order( $order_id );
            $order->update_meta_data( '_veratad_verified', 'FAIL' );
            $order->save();
            $this->helpers->changed_by_api($order_id, 'FAIL', 'AGEMATCH');
        } , 10, 2);
        }

      }

      endif;
