<?php
 
/**
 * Plugin Name: PostPacPriority Shipping
 * Plugin URI: 
 * Description: Custom Shipping Method for WooCommerce
 * Version: v 1.3
 * Author: Martin Steiner
 * Author URI: http://webstooni.ch
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: postpacpriority
 * Post Tarfie Januar 2018
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function postpacpriority_shipping_method() {
        if ( ! class_exists( 'PostPacPriority_Shipping_Method' ) ) {
            class PostPacPriority_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'postpacpriority'; 
                    $this->method_title       = __( 'PostPac Priority Versand', 'postpacpriority' );  
                    $this->method_description = __( 'Schnelle und zuverlässige Paketzustellung', 'postpacpriority' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                        'CH', // Swiss
                        'LI', // Lichtenstein
                        );
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'PostPac Priority Versand', 'postpacpriority' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'aktivieren', 'postpacpriority' ),
                          'type' => 'checkbox',
                          'description' => __( 'diesen Versand aktivieren.', 'postpacpriority' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Titel', 'postpacpriority' ),
                          'type' => 'text',
                          'description' => __( 'Titel beim Shop', 'postpacpriority' ),
                          'default' => __( 'PostPac Priority Versand', 'postpacpriority' )
                          ),
 
                     'weight' => array(
                        'title' => __( '(kg)', 'postpacpriority' ),
                          'type' => 'number',
                          'description' => __( 'Maximum zul&auml;ssiges Gewicht', 'postpacpriority' ),
                          'default' => 30
                          ),
 
                     );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
              public function calculate_shipping( $package ) {
                    
                    $weight = 0;
                    $cost = 0;
                    $country = $package["destination"]["country"];
 
                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product = $values['data']; 
                        $weight = $weight + $_product->get_weight() * $values['quantity']; 
                    }
 
                    $weight = wc_get_weight( $weight, 'kg' );
 
                    if( $weight <= 2 ) {
 
                        $cost = 9;
 
                   } elseif( $weight <= 10 ) {
 
                        $cost = 10.70;
 
                   } elseif( $weight <= 30 ) {
 
                        $cost = 23;
 
                    } else {
 
                        $cost = 31;
 
                    }
 
                    $countryZones = array(
                        'CH' => 0,
                        'LI' => 0,
                       );
 
                    $zonePrices = array(
                        0 => 12,
                       );
 
                    $zoneFromCountry = $countryZones[ $country ];
                    $priceFromZone = $zonePrices[ $zoneFromCountry ];
 
                    $cost += $priceFromZone;
 
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $cost
                    );
 
                    $this->add_rate( $rate );
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'postpacpriority_shipping_method' );
 
    function add_postpacpriority_shipping_method( $methods ) {
        $methods[] = 'PostPacPriority_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_postpacpriority_shipping_method' );
 
    function postpacpriority_validate_order( $posted )   {
 
        $packages = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'postpacpriority', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
 
                if ( $chosen_methods[ $i ] != "postpacpriority" ) {
                             
                    continue;
                             
                }
 
                $PostPacPriority_Shipping_Method = new PostPacPriority_Shipping_Method();
                $weightLimit = (int) $PostPacPriority_Shipping_Method->settings['weight'];
                $weight = 0;
 
                foreach ( $package['contents'] as $item_id => $values ) 
                { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity']; 
                }
 
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
 
                        $message = sprintf( __( 'Entschuldigung, %d kg &Uuml;bersteigt das Max. Gewicht von %d kg f&uuml;r %s', 'postpacpriority' ), $weight, $weightLimit, $PostPacPriority_Shipping_Method->title );
                             
                        $messageType = "error";
 
                        if( ! wc_has_notice( $message, $messageType ) ) {
                         
                            wc_add_notice( $message, $messageType );
                      
                        }
                }
            }       
        } 
    }
 
    add_action( 'woocommerce_review_order_before_cart_contents', 'postpacpriority_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'postpacpriority_validate_order' , 10 );
}
