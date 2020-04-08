<?php

      if ( ! defined( 'ABSPATH' ) ) {
        echo "This is a plugin. If you need information visit www.veratad.com";
        exit; // Exit if accessed directly
      }

      if ( ! class_exists( "WC_Veratad_Products" ) ) :
        require_once( 'wc-class-veratad-options.php' );

      class WC_Veratad_Products {

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

        //hide categories
        function veratad_get_subcategory_terms( $terms, $taxonomies, $args ) {

          $hide = $_SESSION['hide_underage'];
          $list = $this->get_category_array();

          if(in_array( 'no_filter', $list )){
            $hide = "false";
          }

          $new_terms 	= array();
          $hide_category 	= $list; // Ids of the category you don't want to display on the shop page

          // if a product category and on the shop page
        if ( in_array( 'product_cat', $taxonomies ) && !is_admin() && is_shop() && $hide == 'true' && $hide_category ) {
            foreach ( $terms as $key => $term ) {
          if ( ! in_array( $term->term_id, $hide_category ) ) {
            $new_terms[] = $term;
          }
            }
          $terms = $new_terms;
        }

        return $terms;
    }

      function veratad_hide_products_category_shop( $q ) {
        $hide = $_SESSION['hide_underage'];

        $list = $this->get_category_array();
        if(in_array( 'no_filter', $list )){
          $hide = "false";
        }

        if($hide == 'true' && !is_admin() && is_shop()){

          $tax_query = (array) $q->get( 'tax_query' );

          $tax_query[] = array(
                 'taxonomy' => 'product_cat',
                 'field' => 'id',
                 'terms' => $list , // Category slug here
                 'operator' => 'NOT IN'
          );


          $q->set( 'tax_query', $tax_query );
        }
      }

      function add_popup_html(){

        $header = $this->options->get_popup_header_text();
        $reset = $this->options->get_popup_resetting_text();
        $under = $this->options->get_popup_underage_button();
        $over = $this->options->get_popup_overage_button();
        $url = $this->options->get_veratad_underage_url();

        $list = $this->get_category_array();

          $under_button = '<a style="text-decoration:none;" href="'.$url.'">
          <button type="button" class="veratad_popup_age" id="underage" role="button" style="margin-left:10px;">
          '.$under.'
          </button></a>';

    echo '<div id="veratad_popup" class="modal" style="text-align:center; padding-top:20px; padding-bottom:20px;">
            <div style="padding-top:20px; padding-bottom:20px;">
              <div id="header_pop_up" >'.$header.'</div>
              <p id="reset_message" style="display:none; padding-top:40px;">'.$reset.'</p>
            </div>
            <div style="padding-bottom:20px; width:100%;">
              <button type="button" role="button" class="veratad_popup_age" id="overage" style="margin-right:10px;">
              '.$over.'
              </button>
              '.$under_button.'
              </div>
            </div>';

      }



      function add_veratad_popup_ajax() { ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />
      <script type="text/javascript">

        jQuery( document ).ready(function() {

          var visited = localStorage['hide_underage'];

          if (!visited || visited === "true") {
            jQuery("#veratad_popup").modal({
              escapeClose: false,
              clickClose: false,
              showClose: false
            });
          }

           jQuery( ".veratad_popup_age" ).click(function() {
             jQuery(".veratad_popup_age").prop('disabled', true);
             var id = jQuery(this).attr("id");

             jQuery(".veratad_popup_age").remove();

             jQuery("#header_pop_up").hide();
             jQuery("#reset_message").show();

             var data = {
                'action': 'my_ajax_request',
                'post_type': 'POST',
                'name': 'Veratad Popup',
                'age': this.id
              };


             var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
             jQuery.post(ajaxurl, data, function(response) {
               console.log(response);
               if(response === "underage"){
                 localStorage['hide_underage'] = "true";
               }else{
                 localStorage['hide_underage'] = "false";
               }
               location.reload();
             }, 'json');
           });
           });

           </script>

  <?php
  }


  function veratad_handle_ajax_request() {

    $age = $_POST['age'];

    if($age == 'underage'){
      $_SESSION['hide_underage'] = 'true';
    }else{
      $_SESSION['hide_underage'] = 'false';
    }

    echo json_encode($age);
    exit;
  }

  function prevent_access_to_product_page(){
    $hide = $_SESSION['hide_underage'];
    if ( is_product() && $hide == "true" ) {
        global $post;

        $terms = get_the_terms( $post->ID, 'product_cat' );

        foreach ($terms  as $term  ) {
            $product_cat_id = $term->term_id;
            $product_cat_name = $term->name;
            break;
        }

        $list = $this->get_category_array();

        if(in_array( $product_cat_id, $list )){
          global $wp_query;
          $id = $wp_query->get_queried_object()->term_id;
          $wp_query->set_404();
          status_header(404);
        }
        }
      }

    }

    endif;
