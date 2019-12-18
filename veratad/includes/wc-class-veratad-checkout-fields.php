<?php

    if ( ! defined( 'ABSPATH' ) ) {
      echo "This is a plugin. If you need information visit www.veratad.com";
    	exit; // Exit if accessed directly
    }

    if ( ! class_exists( "WC_Veratad_Checkout_Fields" ) ) :
      require_once( 'wc-class-veratad-options.php' );

    class WC_Veratad_Checkout_Fields {

      private $options;

      public function __construct( WC_Veratad_Options $options ) {
  			$this->options = $options;
  		}

      function veratad_add_additional_fields_intro( $checkout ){
        $notice = $this->options->get_veratad_ssn_dob_text();
        echo '<p>' . __(''.$notice.'') . '</p>';
      }

      function veratad_add_dob_billing( $checkout ){
        echo '<div id="veratad_dob">';
      woocommerce_form_field('veratad_billing_dob', array(
        'type' => 'date',
        'class' => array(
          'my-field-class form-row-wide'
        ) ,
        'label' => __('Date of Birth') ,
        'placeholder' => __('MM/DD/YYYY') ,
        'required' => true,
      ) , $checkout->get_value('veratad_billing_dob'));
      echo '</div>';
    }

    function veratad_add_ssn_billing( $checkout ){
echo '<div id="veratad_ssn">';
    woocommerce_form_field('veratad_billing_ssn', array(
      'type' => 'text',
      'class' => array(
        'my-field-class form-row-wide'
      ) ,
      'label' => __('Last 4 SSN') ,
      'maxlength' => 4,
      'required' => true,
    ) , $checkout->get_value('veratad_billing_ssn'));
    echo '</div>';
  }

    function validate_ssn(){
      if ( empty( $_POST['veratad_billing_ssn'] ) ){
        wc_add_notice( 'Please enter your SSN.', 'error' );
        return false;
      }else{
        return true;
      }

    }

    function validate_dob(){
      if ( empty( $_POST['veratad_billing_dob'] ) ){
        wc_add_notice( 'Please enter your Date of Birth.', 'error' );
        return false;
      }else{
        return true; 
      }

    }


  }

endif;
