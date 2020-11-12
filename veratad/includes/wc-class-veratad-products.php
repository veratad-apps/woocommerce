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


    }

    endif;
