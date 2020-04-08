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

      function get_category_array(){

        $cat_array = $this->options->get_veratad_categories();

        $return = array();
        foreach($cat_array as $key => $value){
          $return[] = $value;
        }

        return $return;

      }

      function age_verification_on( $checkout ){

        global $woocommerce;

        $age_verification_products = $this->get_category_array();

        $cat_ids = array();
        foreach( WC()->cart->get_cart() as $cart_item ){
          $product_id = $cart_item['product_id'];
          $term_list = wp_get_post_terms($product_id,'product_cat',array('fields'=>'ids'));
          $cat_ids[] = $term_list;

        }

        foreach($cat_ids as $ids){
          foreach($age_verification_products as $products){
            if(in_array("$products", $ids)){
              $age_verification_on = true;
              break;
            }
          }
        }

        $gifts_in_cart = WC()->session->get( 'group_order_data' );

        if($gifts_in_cart || $age_verification_on){
          $_SESSION['hide_underage'] = 'false';
          return true;
        }else{
          $_SESSION['hide_underage'] = 'true';
          return false;
        }
      }

      function veratad_add_additional_fields_intro( $checkout ){
        $notice = $this->options->get_veratad_ssn_dob_text();
        $intro = $this->options->get_dob_ssn_title_text();
        $background_color = $this->options->get_checkout_background_color();
        if($this->age_verification_on( $checkout )){
          echo '<h1 style="background-color:'.$background_color.'; padding:20px; margin:0;">' . __(''.$intro.'') . '</h1>';
          echo '<p style="background-color:'.$background_color.'; padding:20px; margin:0;">' . __(''.$notice.'') . '</p>';
        }
      }

      function veratad_add_dob_billing( $checkout ){
        if($this->age_verification_on( $checkout )){
        $placement = $this->options->get_checkout_fields_placement();
        if($placement == 'woocommerce_checkout_before_customer_details' || $placement == 'woocommerce_review_order_before_submit'){
          $checkout = WC()->checkout;
        }
        if(!$this->options->get_veratad_ssn_collect()){
          $margin = "margin-bottom:20px;";
        }else{
          $margin = "";
        }

        $background_color = $this->options->get_checkout_background_color();

        echo '<div id="veratad_dob" style="background-color:'.$background_color.'; padding:20px; '.$margin.'">';
      woocommerce_form_field('veratad_billing_dob_set', array(
        'type' => 'date',
        'class' => array(
          'my-field-class form-row-wide'
        ) ,
        'label' => __('Date of Birth') ,
        'placeholder' => __('MM/DD/YYYY') ,
        'required' => true,
      ) , $checkout->get_value('veratad_billing_dob_set'));
      echo '</div>';
    }
  }

    function veratad_add_ssn_billing( $checkout ){
      if($this->age_verification_on( $checkout )){
      $placement = $this->options->get_checkout_fields_placement();
      if($placement == 'woocommerce_checkout_before_customer_details' || $placement == 'woocommerce_review_order_before_submit'){
        $checkout = WC()->checkout;
      }

      $background_color = $this->options->get_checkout_background_color();

      $ssn_field_name = 'veratad_billing_ssn_set';
      echo '<div id="veratad_ssn" style="background-color:'.$background_color.'; padding:20px; margin-top:0px; margin-bottom:20px;">';
    woocommerce_form_field($ssn_field_name, array(
      'type' => 'text',
      'class' => array(
        'my-field-class form-row-wide'
      ) ,
      'label' => __('Last 4 SSN') ,
      'maxlength' => 4,
      'required' => true,
    ) , $checkout->get_value($ssn_field_name));
    echo '</div>';
  }
  }

    function add_dob_checkout_hidden_field( $checkout ) {
      if($this->age_verification_on( $checkout )){
      echo '<div id="veratad_dob_hidden_checkout_field">
              <input type="hidden" class="input-hidden" name="veratad_billing_dob" id="veratad_billing_dob" value="">
      </div>';
    }
    }

    function add_dob_script( $checkout ){
      if($this->age_verification_on( $checkout )){
      ?>
      <script>
      jQuery( document ).ready(function() {
        jQuery(document).on('click', '#place_order', function(e){
          var dob = jQuery("#veratad_billing_dob_set").val();
          var dob_send = jQuery("#veratad_billing_dob").val(dob);
        });
      });
      </script>
      <?php
    }
    }



    function add_ssn_checkout_hidden_field( $checkout ) {
      if($this->age_verification_on( $checkout )){
      echo '<div id="veratad_ssn_hidden_checkout_field">
              <input type="hidden" class="input-hidden" name="veratad_billing_ssn" id="veratad_billing_ssn" value="">
      </div>';
    }
    }

    function add_ssn_script( $checkout ){
      if($this->age_verification_on( $checkout )){
      ?>
      <script>
      jQuery( document ).ready(function() {
        jQuery(document).on('click', '#place_order', function(e){
          var ssn = jQuery("#veratad_billing_ssn_set").val();

          var ssn_send = jQuery("#veratad_billing_ssn").val(ssn);
        });
      });
      </script>
      <?php
    }
    }

    function add_modal_av_form_html(){
      if($this->age_verification_on( $checkout )){
      $title = $this->options->get_dob_ssn_title_text();
      $intro = $this->options->get_veratad_ssn_dob_text();

      if($this->options->get_veratad_ssn_collect() && $this->options->get_veratad_dob_collect()){
        $fields = '<form id="modal-form-veratad"><p style="padding-left:20px; padding-right:20px; padding-top:20px;" class="form-row my-field-class form-row-wide validate-required" id="veratad_billing_dob_field" data-priority=""><label for="veratad_dob" class="">Date of Birth&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="date" class="input-text" name="veratad_billing_dob_set" id="veratad_billing_dob_set" placeholder=""  value=""  /></span></p><p style="padding-left:20px; padding-right:20px; padding-top:20px;" class="form-row my-field-class form-row-wide validate-required" id="veratad_billing_dob_field" data-priority=""><label for="veratad_ssn" class="">Last 4 SSN<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="text" class="input-text" name="veratad_billing_ssn_set" id="veratad_billing_ssn_set" placeholder="Last 4 SSN"  value=""  /></span></p><div style="padding-top:20px; padding-left:20px;"><button role="button"  type="button"  class="button" id="close_modal_button">Done</button></div></form>';
      }elseif(!$this->options->get_veratad_ssn_collect() && $this->options->get_veratad_dob_collect()){
        $fields = '<form id="modal-form-veratad"><p style="padding-left:20px; padding-right:20px; padding-top:20px;" class="form-row my-field-class form-row-wide validate-required" id="veratad_billing_dob_field" data-priority=""><label for="veratad_dob" class="">Date of Birth&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="date" class="input-text" name="veratad_billing_dob_set" id="veratad_billing_dob_set" placeholder="MM/DD/YYYY"  value=""  /></span></p><div style="padding-top:20px; padding-left:20px;"><button  type="button" role="button"   class="button" id="close_modal_button">Done</button></div></form>';
      }elseif($this->options->get_veratad_ssn_collect() && !$this->options->get_veratad_dob_collect()){
        $fields = '<form id="modal-form-veratad"><p style="padding-left:20px; padding-right:20px; padding-top:20px;" class="form-row my-field-class form-row-wide validate-required" id="veratad_billing_dob_field" data-priority=""><label for="veratad_ssn" class="">Last 4 SSN<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="text" class="input-text" name="veratad_billing_ssn_set" id="veratad_billing_ssn_set" placeholder="Last 4 SSN"  value=""  /></span></p><div style="padding-top:20px; padding-left:20px;"><button role="button"  type="button" class="button" id="close_modal_button">Done</button></div></form>';
      }


  echo '<div id="veratad_modal_av_form" class="modal" style="padding-top:20px; padding-bottom:20px;">
        <div style="padding-left:20px; padding-right:20px;">
        <h1>'.$title.'</h1>
        </div>
        <div style="padding-left:20px; padding-right:20px; padding-top:20px;">'.$intro.'</div>
        '.$fields.'
      </div>';
    }
    }


    function add_veratad_checkout_modal_script( $checkout ){
      if($this->age_verification_on( $checkout )){
      echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />';
      echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/additional-methods.min.js"></script>
        <style>
                    .blocker {
            position: fixed !important;
            z-index: 10 !important;
        }

        </style>';

      ?>
      <script>

      jQuery( document ).ready(function() {


        var modal = jQuery("#veratad_modal_av_form").modal({
          escapeClose: false,
          clickClose: false,
          showClose: false
        });

        console.log(modal);

        jQuery('a[data-modal]').click(function(event) {
          jQuery(this).modal({
            escapeClose: false,
            clickClose: false,
            showClose: false
          });
          return false;
        });

           <?php
           if($this->options->get_veratad_ssn_collect()){
             $value = "true";
             //echo $value;
           }else{
             $value = "false";
           }

           if($this->options->get_veratad_dob_collect()){
             $value_dob = "true";
             //echo $value;
           }else{
             $value_dob = "false";
           }

            ?>

jQuery('#modal-form-veratad').validate({
    rules: {
      veratad_billing_ssn_set: {
          required: <?php echo $value;?>,
          minlength:4,
          maxlength:4
        },
        veratad_billing_dob_set: {
            required: <?php echo $value_dob;?>
          }
    }
});

    jQuery( "#close_modal_button" ).click(function() {
      var valid_form = true;
      valid_form = jQuery("#modal-form-veratad").valid();

      if(valid_form){
        jQuery.modal.close();
    }
      });

    });

</script>
<?php
}
    }



    function validate_ssn(){
      if($this->age_verification_on( $checkout )){
      if ( empty( $_POST['veratad_billing_ssn'] ) ){
        wc_add_notice( 'Please enter your SSN.', 'error' );
        return false;
      }else{
        return true;
      }
    }

    }

    function validate_dob(){
      if($this->age_verification_on( $checkout )){
      if ( empty( $_POST['veratad_billing_dob'] ) ){
        wc_add_notice( 'Please enter your Date of Birth.', 'error' );
        return false;
      }else{
        return true;
      }
    }

    }

    function add_checkout_modal_edit(){
      if($this->age_verification_on( $checkout )){

      $text = $this->options->get_modal_click_text();
      echo '<p><a href="#veratad_modal_av_form" style="text-decoration:none;" data-modal>'. $text .'</a></p>';
    }
}

  }

endif;
