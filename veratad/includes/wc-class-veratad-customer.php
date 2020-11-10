<?php

if ( ! defined( 'ABSPATH' ) ) {
  echo "This is a plugin. If you need information visit www.veratad.com";
  exit; // Exit if accessed directly
}

    if ( ! class_exists( 'WC_Veratad_Customer' ) ) :

      class WC_Veratad_Customer {

        private $helpers;

        public function __construct( WC_Veratad_Helpers $helpers ) {
    			$this->helpers = $helpers;
    		}

        public function detect_customer_changes(){
          //detect customer name change

          add_filter( 'insert_user_meta', function( $meta, $user, $update ) {
            if( true !== $update ) {
              return $meta;
            }else{

              $old_meta = get_user_meta( $user->ID );

              $old_fn = strtolower($old_meta[ 'first_name' ][0]);
              $new_fn = strtolower($meta[ 'first_name' ]);

              $old_ln = strtolower($old_meta[ 'last_name' ][0]);
              $new_ln = strtolower($meta[ 'last_name' ]);

              $old_name = $old_fn . $old_ln;
              $new_name = $new_fn . $new_ln;

              $customer_id = get_current_user_id();

              if( $old_name !== $new_name ) {
                update_user_meta( $customer_id, '_veratad_verified', "");
                $timestamp = current_time( "Y-m-d h:i:s");
                add_user_meta( $customer_id, '_veratad_changed_by', "$timestamp / SYSTEM / NONE" );
              }

              return $meta;

            }


          }, 10, 3 );
        }

        public function av_success(){
          $customer_id = get_current_user_id();
          $customer_action = get_user_meta( $customer_id, '_veratad_verified', true);
          if($customer_action == "PASS"){
            return true;
          }else{
            return false;
          }
        }

        public function update_av_status(){
          $customer_id = get_current_user_id();
          $order = wc_get_customer_last_order( $customer_id );
          $action = $order->get_meta('_veratad_verified');
          
          update_user_meta( $customer_id, '_veratad_verified', $action);
          $this->helpers->changed_by_api_user($customer_id, $action, 'AGEMATCH');
        }

      }

      endif;
