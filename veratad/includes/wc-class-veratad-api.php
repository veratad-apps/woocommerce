<?php

    if ( ! defined( 'ABSPATH' ) ) {
      echo "This is a plugin. If you need information visit www.veratad.com";
    	exit; // Exit if accessed directly
    }

    if ( ! class_exists( "WC_Veratad_Api" ) ) :
      require_once( 'wc-class-veratad-options.php' );
      require_once( 'wc-class-veratad-checkout-fields.php' );

    class WC_Veratad_Api {

      private $options;
      private $checkout;

      public function __construct( WC_Veratad_Options $options, WC_Veratad_Checkout_Fields $checkout  ) {
  			$this->options = $options;
        $this->checkout = $checkout;
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
          'dob' => $_POST['veratad_'.$type.'_dob'],
          'ssn' => $_POST['veratad_'.$type.'_ssn'],
          'phone' => $_POST[''.$type.'_phone'],
          'email' => $_POST[''.$type.'_email'],
          'age' => $this->options->get_veratad_default_age_to_check()
      );

      return $target;
    }

    public function get_api_specs(){
      $specs = array(
        'user' => $this->options->get_veratad_user(),
        'pass' => $this->options->get_veratad_pass(),
        'rules' => $this->options->get_veratad_rules(),
        'test_mode' => $this->options->get_veratad_test_mode(),
        'test_key' => $this->options->get_test_key()
      );
      return $specs;
    }

    public function check_api_options($user, $pass, $age) {

      $req_array  = array(
        'user'  => $user,
        'pass' => $pass,
        'service' => 'AgeMatch5.0',
        'reference' => 'credentials check',
        'rules' => '',
        'target' => array(
          'fn' => 'Barbara',
          'ln' => 'Miller',
          'addr' => '123 Main St',
          'city' => 'stratford',
          'state' => 'CT',
          'zip' => '06614',
          'age' => $age,
          'test_key' => 'general_identity'
        )
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

      if($res->result->action){
        return "success";
      }else{
        $message = $res->error->message;
        $code = $res->error->code;
        $detail = $res->error->detail;
        $return_message = "$code $message $detail";
        return $return_message;
      }

    }




    public function is_verified_veratad($order_id) {

      $order = wc_get_order( $order_id );
      $order->update_meta_data( '_agematch_eligible', 'true' );
      $order->save();

      $specs = $this->get_api_specs();

      $target = $this->get_target('billing');
      $reference = $target['email'];

      $dob = $target['dob'];


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

      if($this->options->get_veratad_store_dob()){
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_dob', $dob );
        $order->save();
        $customer_id = get_current_user_id();
        if(is_user_logged_in()){
          update_user_meta($customer_id, '_veratad_dob', $dob );
        }

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

    public function is_verified_veratad_block_order() {

      $specs = $this->get_api_specs();

      $target = $this->get_target('billing');
      $reference = $target['email'];

      $dob = $target['dob'];


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

    public function get_customer_av_status(){
      $customer_id = get_current_user_id();
      $customer_action = get_user_meta( $customer_id, '_veratad_verified', true);
      if($customer_action == "PASS"){
        return true;
      }else{
        return false;
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

    public function veratad_order_data_save_already_verified() {
      add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_verified', 'PASS' );
        $order->save();
        $this->changed_by_api($order_id, 'PASS', 'VERIFIED ACCOUNT');
    } , 10, 2);
    }

    public function veratad_order_data_save_pass() {
      add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_verified', 'PASS' );
        $order->save();
        $this->changed_by_api($order_id, 'PASS', 'AGEMATCH');
    } , 10, 2);
    }

    public function veratad_order_data_save_fail() {
      add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_verified', 'FAIL' );
        $order->save();
        $this->changed_by_api($order_id, 'FAIL', 'AGEMATCH');
    } , 10, 2);
    }

    public function veratad_order_data_save_pending() {
      add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_verified', 'PENDING' );
        $order->save();
        $this->changed_by_api($order_id, 'PENDING', 'DCAMSPLUS');
    } , 10, 2);
    }

    public function handle_api_response_order_acceptance($order_id){

      $hide = $_SESSION['hide_underage'];

      if($hide == "false" || !$hide){

      $customer_id = get_current_user_id();

      if(is_user_logged_in()){
        $customer_verified = $this->get_customer_av_status();
        if($customer_verified){
          $this->veratad_order_data_save_already_verified();
        }else{
          //$result = $this->is_verified_veratad($order_id);
          if($this->is_verified_veratad($order_id)){
            update_user_meta( $customer_id, '_veratad_verified', "PASS");
            $this->veratad_order_data_save_pass();
            $this->changed_by_api_user($customer_id, 'PASS', 'AGEMATCH');
          }else{
            update_user_meta( $customer_id, '_veratad_verified', "FAIL");
            $this->veratad_order_data_save_fail();
            $this->changed_by_api_user($customer_id, 'FAIL', 'AGEMATCH');
          }
        }
      }else{
        if(!$this->is_verified_veratad($order_id)){
          $this->veratad_add_message_to_thank_you();
          $this->veratad_order_data_save_fail();
          update_user_meta( $customer_id, '_veratad_verified', "FAIL");
          $this->changed_by_api_user($customer_id, 'FAIL', 'AGEMATCH');
        }else{
          $this->veratad_order_data_save_pass();
          update_user_meta( $customer_id, '_veratad_verified', "PASS");
          $this->changed_by_api_user($customer_id, 'PASS', 'AGEMATCH');
        }
      }
    }
  }



    public function block(){
      if($this->options->get_veratad_shipping_verification()){
        $shipping_block = $this->block_order_if_different_name();
      }else{
        $shipping_block = false;
      }

      if($this->options->get_veratad_customer_verification()){
        $customer_block = $this->block_order_if_different_name_on_account();
      }else{
        $customer_block = false;
      }

      if(!$customer_block && !$shipping_block){
        $block = false;
      }else{
        $block = true;
      }
      return $block;
    }

    public function additional_fields_valid(){

      if($this->options->get_veratad_dob_collect()){
        add_action('woocommerce_checkout_process', array($checkout, 'validate_dob'));
        $valid_dob = $this->checkout->validate_dob();
      }else{
        $valid_dob = true;
      }

      if($this->options->get_veratad_ssn_collect()){
        add_action('woocommerce_checkout_process', array($checkout, 'validate_ssn'));
        $valid_ssn = $this->checkout->validate_ssn();
      }else{
        $valid_ssn = true;
      }

      if($valid_dob && $valid_ssn){
        return true;
      }else{
        return false;
      }

    }

    public function not_order_acceptance_update_order($order_id){

      $hide = $_SESSION['hide_underage'];
      $_SESSION['veratad_attempt'] = null;

      if($hide == "false" || !$hide){

      $customer_id = get_current_user_id();

      $target = $this->get_target('billing');

      $dob = $target['dob'];

      if($this->options->get_veratad_store_dob()){
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_veratad_dob', $dob );
        $order->save();
        $customer_id = get_current_user_id();
        if(is_user_logged_in()){
          update_user_meta($customer_id, '_veratad_dob', $dob );
        }

      }

      if(is_user_logged_in()){
        $customer_verified = $this->get_customer_av_status();
        if($customer_verified){
          $this->veratad_order_data_save_already_verified();
        }else{
          update_user_meta( $customer_id, '_veratad_verified', "PASS");
          $this->veratad_order_data_save_pass();
        }
      }else{
          $this->veratad_order_data_save_pass();
      }
    }
    }


    public function handle_api_response_not_order_acceptance(){

      $hide = $_SESSION['hide_underage'];

      if($hide == "false" || !$hide){
        if(!$_SESSION['veratad_attempt']){
          $attempt = 0;
        }else{
          $attempt = $_SESSION['veratad_attempt'];
        }
        $_SESSION['veratad_attempt'] = $attempt + 1;

        $session_attempt = $_SESSION['veratad_attempt'];

        if($session_attempt >= 3){
          wc_add_notice($this->options->get_av_attempts_text(), 'error');
        }else{

      $block = $this->block();
      $valid_fields = $this->additional_fields_valid();

      if(!$block && $valid_fields){
      $customer_id = get_current_user_id();

      if(is_user_logged_in()){
        $customer_verified = $this->get_customer_av_status();
        if(!$customer_verified){
          if(!$this->is_verified_veratad_block_order()){
            wc_add_notice($this->options->get_veratad_av_failure_text(), 'error');
              }
            }
          }else{
            if(!$this->is_verified_veratad_block_order()){
              wc_add_notice($this->options->get_veratad_av_failure_text(), 'error');
            }
          }
        }
      }
      }
    }

    public function block_order_if_different_name(){
      $billing = $this->get_target('billing');
      $shipping = $this->get_target('shipping');
      $billing_fn = $billing['fn'];
      $billing_ln = $billing['ln'];
      $shipping_fn = $shipping['fn'];
      $shipping_ln = $shipping['ln'];

      if($shipping_fn == '' || $shipping_ln == '' || $shipping_fn == 'null' || $shipping_ln == 'null'){
        return false;
      }else{

      $billing_name = strtolower($billing_fn . $billing_ln);
      $shipping_name = strtolower($shipping_fn . $shipping_ln);

      if($billing_name != $shipping_name){
        wc_add_notice('You can not ship to a different person.', 'error');
        return true;
      }else{
        return false;
      }
    }
    }

    public function block_order_if_different_name_on_account(){

      $customer_id = get_current_user_id();

      $customer_fn = get_user_meta( $customer_id, 'first_name', true);
      $customer_ln = get_user_meta( $customer_id, 'last_name', true);
      $customer_action = get_user_meta( $customer_id, '_veratad_verified', true);

      if($customer_action != 'PASS'){
        return false;
      }else{

      $billing = $this->get_target('billing');
      $billing_fn = $billing['fn'];
      $billing_ln = $billing['ln'];


      $billing_name = strtolower($billing_fn . $billing_ln);
      $customer_name = strtolower($customer_fn . $customer_ln);

      if($billing_name != $customer_name){
        wc_add_notice('You can not order with a different name then your verified account.', 'error');
        return true;
      }else{
        return false;
      }
      }

    }

    public function email($to, $subject, $body){


      $config = array();
      $config['api_key'] = "key-22f53625ea0fadbfb6d75ff90ebdd3f8";
      $config['api_url'] = "https://api.mailgun.net/v3/verataddev.com/messages";
      $message = array();
      $message['from'] = "Veratad System Message <no-reply@veratad.com>";
      $message['to'] = "$to";
      $message['subject'] = "$subject";
      $message['html'] = "$body";

      $chmail = curl_init();
      curl_setopt($chmail, CURLOPT_URL, $config['api_url']);
      curl_setopt($chmail, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($chmail, CURLOPT_USERPWD, "api:{$config['api_key']}");
      curl_setopt($chmail, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($chmail, CURLOPT_CONNECTTIMEOUT, 10);
      curl_setopt($chmail, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($chmail, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($chmail, CURLOPT_POST, true);
      curl_setopt($chmail, CURLOPT_POSTFIELDS,$message);

      $resultmail = curl_exec($chmail);
      $json_result_mail = json_decode($resultmail);

      return $json_result_mail;

    }


    public function dcams_callback() {
      $data = file_get_contents("php://input");
      $this->email("tcanfarotta@veratad.com", "DCAMS Callback WOO", $data);

      $array = json_decode($data, true);
      $email = $array['email'];
      $statusid = $array['statusid'];
      $idfront = $array['idfront'];
      $idback = $array['idback'];
      $reference = $array['reference'];
      if($statusid == 2){
        $action = "PASS";
      }elseif($statusid == 3){
        $action = "FAIL";
      }else{
        $action = "PENDING";
      }

      $order_id = $array['reference'];
      $order = wc_get_order( $order_id );
      $order->update_meta_data( '_veratad_verified', $action );
      $order->update_meta_data( '_veratad_id_front', $idfront );
      $order->update_meta_data( '_veratad_id_back', $idback );
      $order->save();
      $this->changed_by_api($order_id, $action, 'DCAMSPLUS');
      $customer_id = get_post_meta($order_id, '_customer_user', true);
      if($customer_id != '' || $customer_id != "0"){
      update_user_meta( $customer_id, '_veratad_verified', $action);
      $this->changed_by_api_user($customer_id, $action, 'DCAMSPLUS');
    }
  }

  public function add_top_messages(){

    if( isset( $_GET['key'] ) && is_wc_endpoint_url( 'order-received' ) ) {
      $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
      $order = wc_get_order($order_id);
      $billing_fn = $order->get_billing_first_name();
      $billing_ln = $order->get_billing_last_name();
      $billing_addr = $order->get_billing_address_1();
      $billing_addr_two = $order->get_billing_address_2();
      $billing_city = $order->get_billing_city();
      $billing_zip = $order->get_billing_postcode();
      $billing_email = $order->get_billing_email();
      $billing_phone = $order->get_billing_phone();
      $customer_id = $order->get_user_id();
      $av_status = get_post_meta( $order_id, '_veratad_verified', true);
      $eligible = get_post_meta( $order_id, '_agematch_eligible', true);
    }

    $initial_fail_text = $this->options->get_av_failure_text_acceptance();
    $second_attempt_av_success = $this->options->get_second_attempt_av_success();
    $second_attempt_av_failure = $this->options->get_second_attempt_av_failure();
    $intro_text = $this->options->get_second_attempt_av_intro();
    $dcams_intro = $this->options->get_second_attempt_dcams_intro();

    if($av_status == "PASS"){
      echo '<div id="pass" class="woocommerce-message" role="alert">'.$second_attempt_av_success .'</div>';
    }elseif($eligible != "true"){
      echo '<div id="fail" class="woocommerce-error" role="alert">'.$second_attempt_av_failure .'</div>';
    }

  }


    public function veratad_add_message_to_thank_you() {

        if( isset( $_GET['key'] ) && is_wc_endpoint_url( 'order-received' ) ) {
          $order_id = wc_get_order_id_by_order_key( $_GET['key'] );
          $order = wc_get_order($order_id);
          $billing_fn = $order->get_billing_first_name();
          $billing_ln = $order->get_billing_last_name();
          $billing_addr = $order->get_billing_address_1();
          $billing_addr_two = $order->get_billing_address_2();
          $billing_city = $order->get_billing_city();
          $billing_zip = $order->get_billing_postcode();
          $billing_email = $order->get_billing_email();
          $billing_phone = $order->get_billing_phone();
          $customer_id = $order->get_user_id();
          $av_status = get_post_meta( $order_id, '_veratad_verified', true);
          $eligible = get_post_meta( $order_id, '_agematch_eligible', true);
        }


        $dcams_site = $this->options->get_dcams_site();

        $ssn_on = $this->options->get_veratad_ssn_second_attempt_on();
        if($ssn_on){
          $ssn_style = "inline-block";
        }else{
          $ssn_style = "none";
        }

        $initial_fail_text = $this->options->get_av_failure_text_acceptance();
        $second_attempt_av_success = $this->options->get_second_attempt_av_success();
        $second_attempt_av_failure = $this->options->get_second_attempt_av_failure();
        $intro_text = $this->options->get_second_attempt_av_intro();
        $dcams_intro = $this->options->get_second_attempt_dcams_intro();

        $try_again_form = '<div id="veratad_modal_av_second_attempt_form" class="veratad-modal-woo" style="padding-top:20px; padding-bottom:20px;">
        <div id="veratad-try-again" class="veratad-modal-content-woo">
        <div id="veratad-try-again-content" style="padding:15px 15px 15px 15px;">
        <p style="font-weight:1000; font-size:35px;">'.$initial_fail_text.' </p>
        <div style="display:none;" id="pass" class="woocommerce-message" role="alert">'.$second_attempt_av_success .'</div>
          <div style="display:none;" id="dcams-fail" class="woocommerce-error" role="alert">'. $second_attempt_av_failure .'</div>
            <div style="display:none;" id="fail" >'.$dcams_intro.'</div>
            <p id="veratad_intro_text">'.$intro_text.'</p>
            <form id="veratad-try-again-form">
            <div class="woocommerce-billing-fields__field-wrapper">
            <p class="form-row form-row-first validate-required" id="fn-field" data-priority="10"><label for="fn" class="">First name&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="text" class="input-text " name="fn" id="billing_first_name" placeholder=""  value="'.$billing_fn.'" autocomplete="given-name" required/></span></p>
            <p class="form-row form-row-last validate-required" id="ln-field" data-priority="20"><label for="ln class="">Last name&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="text" class="input-text " name="ln" id="ln" placeholder=""  value="'.$billing_ln.'" autocomplete="family-name" /></span></p>
            <input type="hidden" class="input-text " name="addr" id="addr" placeholder="House number and street name"  value="'.$billing_addr.'" autocomplete="address-line1" />
            <input type="hidden" class="input-text " name="addr2" id="addr2" placeholder="Apartment, suite, unit etc. (optional)"  value="'.$billing_two.'" autocomplete="address-line2" />
            <input type="hidden" class="input-text " name="billing_city" id="billing_city" placeholder=""  value="'.$billing_city.'" autocomplete="address-level2" />
            <input type="hidden" class="input-text " name="billing_postcode" id="billing_postcode" placeholder=""  value="'.$billing_zip.'" autocomplete="postal-code" />
            <p class="form-row my-field-class form-row-wide validate-required" id="veratad_billing_dob_field" data-priority=""><label for="veratad_dob" class="">Date of Birth&nbsp;<abbr class="required" title="required">*</abbr></label><span class="woocommerce-input-wrapper"><input type="date" class="input-text" name="veratad_dob" id="veratad_dob" placeholder="MM/DD/YYYY"  value=""  /></span></p>
            <p class="form-row my-field-class form-row-wide validate-required" id="ssn-field" data-priority="10"><label for="veratad_ssn" class="" style="display:'.$ssn_style.';">Last 4 SSN<abbr class="required" style="display:'.$ssn_style.';" title="required">*</abbr></label><span class="woocommerce-input-wrapper" ><input type="text" maxLength="4" style="display:'.$ssn_style.';" class="input-text " name="ssn" id="veratad_ssn" placeholder=""  value="" autocomplete="given-name" /></span></p>
            <input type="hidden" id="email" name="email" value="'.$billing_email.'">
            <input type="hidden" id="dcams_site" name="dcams_site" value="'.$dcams_site.'">
            <input type="hidden" id="order_id" name="order_id" value="'.$order_id.'">
            <input type="hidden" id="phone" name="phone" value="'.$billing_phone.'">
            <input type="hidden" id="customer_id" name="customer_id" value="'.$customer_id.'">
            <button type="button" class="button alt" name="veratad_submit_try-again" id="veratad-submit" data-value="Verify">Verify</button>
            <p id="verify_message" style="display:none;">Verifying...</p>
            </div>
            </form>
          </div>
          <div style="text-align: center; width:100%;">
          <button type="button" style="display:none; " class="button alt" name="upload" id="upload" data-value="Upload Your ID">Upload Your ID</button>
          </div>
        </div>
        </div>';

          if($av_status == "PASS"){
            $this->add_top_messages();
          }elseif($eligible != "true"){
            $this->add_top_messages();
          }elseif($av_status != "PASS" && $eligible == "true"){
            echo $try_again_form;
          }

    }

    function add_second_attempt_script() { ?>
<link rel="stylesheet" href="https://verataddev.com/dcams/v2/stable/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/additional-methods.min.js"></script>



    <script type="text/javascript">

      jQuery( document ).ready(function() {

        var modal_second_attempt = document.getElementById("veratad_modal_av_second_attempt_form");
        if(modal_second_attempt){
          modal_second_attempt.style.display = "block";
        }


        <?php

        $ssn_req = $this->options->get_veratad_ssn_second_attempt_on();
        if($ssn_req){
          $ssn = "true";
        }else{
          $ssn = "false";
        }
        echo $ssn;
        ?>

        jQuery('#veratad-try-again-form').validate({
          rules: {
            billing_first_name: {
                required: true
            },
            ln: {
                  required: true
              },
            veratad_dob: {
                required: true
            },
            ssn: {
                required: <?php echo $ssn; ?>
            }
          }
        });

        var dcams_site = jQuery("#dcams_site").val();


         jQuery( "#veratad-submit" ).click(function() {
           var form = jQuery('#veratad-try-again-form');
           var valid_form = true;
           valid_form = jQuery("#veratad-try-again-form").valid();
           if(valid_form){
           var data = {
              'action': 'veratad_ajax_request',
              'post_type': 'POST',
              'name': 'Veratad AgeMatch',
              'fn': jQuery("#billing_first_name").val(),
              'ln': jQuery("#ln").val(),
              'addr': jQuery("#addr").val(),
              'zip': jQuery("#billing_postcode").val(),
              'dob': jQuery("#veratad_dob").val(),
              'ssn': jQuery("#veratad_ssn").val(),
              'phone': jQuery("#phone").val(),
              'email': jQuery("#email").val(),
              'order_id': jQuery("#order_id").val(),
              'customer_id': jQuery("#customer_id").val()
            };

           jQuery("#veratad-submit").hide();
           jQuery("#verify_message").show();

           var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
           jQuery.post(ajaxurl, data, function(response) {
             jQuery("#veratad-try-again-form").hide();
             jQuery("#veratad_intro_text").hide();
             var action = response.result.action;
             if(action == "PASS"){
               jQuery("#pass").show();
               jQuery("#initial-error").hide();
               jQuery.modal.close();
             }else{
               jQuery("#initial-error").hide();
               if(dcams_site != ''){
                 jQuery("#fail").show();
                 jQuery("#upload").show();
               }else{
                 jQuery("#initial-error").hide();
                 jQuery("#dcams-fail").show();
               }

             }

           }, 'json');
         }
         });

         jQuery( "#upload" ).click(function() {

           var modal_second_attempt_dcams = document.getElementById("veratad_modal_av_second_attempt_form");
           modal_second_attempt_dcams.style.display = "none";

           var dob = jQuery("#veratad_dob").val();
           dob = dob.replace("-","");
           dob = dob.replace("-","");
           var order_id = jQuery("#order_id").val();
         var veratadModal;
         jQuery(function(){
           veratadModal = new veratad.modal({
             site: "<?php echo $this->options->get_dcams_site(); ?>", // veratad will provide your site name for identification.
             review: false, // set to true or false. When true it will trigger a Veratad anual review upon scan failure.
             dcams_plus: <?php echo $this->options->get_veratad_scan_dcams(); ?>, //set to true if you want the document to be auto scanned or false if you just want it to be uploaded
             reference: order_id,
             region: "<?php echo $this->options->get_dcams_default_region(); ?>",//set the default region if empty United States will be used, Values = United States, Canada, Asia, Australia, Africa, Europe, Oceania, South America
             region_select: true, //set to true if you want to allow the user to change their region or false to only allow documents from the default region value. If dcams_plus is fault the user will not have to select their region it is only for scanning.
             rules: "<?php echo $this->options->get_dcams_rules(); ?>", //check out api.veratad.com for more rule sets
             age: "<?php echo $this->options->get_veratad_default_age_to_check(); ?>", //place the age you want to check here
             fn: jQuery("#billing_first_name").val(), //customer first name
             ln: jQuery("#ln").val(), //customer last name
             addr: jQuery("#addr").val(), //customer address
             zip: jQuery("#postcode").val(), //customer zip code
             dob: dob, //required - Customer Date of birth. YYYYMMDD format only.
             email: jQuery("#email").val(), //required - customer email address. This is used for tracking the user.
             onOpen: function() {
             },
             onClose: function() {
               veratadModal.close();
             },
             onSuccess: function() {
               veratadModal.close();
               jQuery("#initial-error").hide();
               jQuery("#pass").show();
               jQuery("#fail").hide();
               jQuery("#upload").hide();
             },
             onFailure: function() {
               //this is just an example. Handle the customer in your own frontend flow.
               jQuery("#initial-error").hide();
               jQuery("#dcams-fail").show();
               jQuery("#fail").hide();
               jQuery("#upload").hide();
               veratadModal.close();
             },
             onError: function() {
               //this is just an example. Handle the customer in your own frontend flow.
               jQuery("#initial-error").show();
               jQuery("#fail").hide();
               jQuery("#upload").hide();
               veratadModal.close();
             },
           });
         veratadModal.open();
       });
       });

      });

    </script>
    <script src="https://verataddev.com/dcams/v2/stable/initialize.js"></script>

<?php
}

function veratad_ajax_agematch_second_attempt() {

  $specs = $this->get_api_specs();

  $test_mode = $specs['test_mode'];

  $date_of_birth = $_POST['dob'];

  if (strpos($date_of_birth, '-') !== false) {
    $dob_type = "YYYY-MM-DD";
  }else{
    $dob_type = "MMDDYYYY";
  }

  $req_array  = array(
    'user'  => $specs['user'],
    'pass' => $specs['pass'],
    'service' => 'AgeMatch5.0',
    'reference' => $_POST['email'],
    'rules' => $specs['rules'],
    'target' => array(
      'fn' => $_POST['fn'],
      'ln' => $_POST['ln'],
      'addr' => $_POST['addr'],
      'zip' => $_POST['zip'],
      'dob' => $_POST['dob'],
      'ssn' => $_POST['ssn'],
      'age' => $this->options->get_veratad_default_age_to_check(),
      'dob_type' => $dob_type,
      'email' => $_POST['email'],
      'phone' => $_POST['phone']
    )
  );


  if($test_mode){
    $req_array['target']['test_key'] = $specs['test_key'];
  }

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
  $action = $res->result->action;
  $order_id = $_POST['order_id'];
  $order = wc_get_order( $order_id );
  $order->update_meta_data( '_agematch_eligible', 'false' );
  $order->update_meta_data( '_veratad_verified', $action );
  $order->set_billing_first_name($_POST['fn']);
  $order->set_billing_last_name($_POST['ln']);
  $order->set_shipping_first_name($_POST['fn']);
  $order->set_shipping_last_name($_POST['ln']);
  $this->changed_by_api($order_id, $action, "AGEMATCH");
  if($this->options->get_veratad_store_dob()){
    $order->update_meta_data( '_veratad_dob', $_POST['dob'] );
  }
  $order->save();
  $customer_id = $_POST['customer_id'];
  if($customer_id != 0){
    update_user_meta( $customer_id, '_veratad_verified', $action);
    update_user_meta( $customer_id, 'first_name', $_POST['fn']);
    update_user_meta( $customer_id, 'last_name', $_POST['ln']);
    update_user_meta( $customer_id, 'billing_first_name', $_POST['fn']);
    update_user_meta( $customer_id, 'billing_last_name', $_POST['ln']);
    update_user_meta( $customer_id, 'shipping_first_name', $_POST['fn']);
    update_user_meta( $customer_id, 'shipping_last_name', $_POST['ln']);
    $this->changed_by_api_user($customer_id, $action, 'AGEMATCH');
    if($this->options->get_veratad_store_dob()){
      update_user_meta($customer_id, '_veratad_dob', $_POST['dob'] );
    }
  }

  echo json_encode($res);
  exit;
}

function veratad_email( $order, $sent_to_admin, $plain_text, $email){

  $order_id = $order->get_id();
  $av_status = get_post_meta( $order_id, '_veratad_verified', true);

  if($av_status == "PASS" || $av_status == "FAIL"){
    $message = "The age and identity verification status is: $av_status";
  }else{
    $message = "There was no verification performed with this order";
  }

  $fail_message = "";
  if($av_status == "PASS"){
    $bg = "#228B22";
  }elseif($av_status == "FAIL"){
    $bg = "#8B0000";
    $fail_message = "Since you allow orders to be accepted the user may be currently going through a second attempt or uploading a document.";
  }else{
    $bg = "#9400D3";
  }

  if ( $sent_to_admin ) {
    echo '<table width="100%" style="margin-bottom:10px; margin-top:10px;">
      <tr width="100%" bgcolor="'.$bg.'" style="padding:10px: margin-bottom:0px;">
        <td width="100%" ><font color="#fff">'.$message.'</font></td>
      </tr>
      <tr width="100%" style="padding:10px: margin-top:0px;">
        <td width="100%" style="padding:10px:">'.$fail_message.'</td>
      </tr>
    </table>';
  }

}

  }

endif;
