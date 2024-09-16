<?php
/**
* Plugin Name: Example custom variation meta for WooCommerce
* Plugin URI: hhttps://www.kathyisawesome.com/add-custom-meta-fields-to-woocommerce-variations/
* Description: Add thickness and diameter fields to variations.
* Version: 6.22.5
* Author: Kathy Darling
* Author URI: https://kathyisawesome.com/
*
* Text Domain: extra-product-data

* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom fields to product variation settings
 *
 * @param string $loop
 * @param array $variation_data
 * @param WP_Post $variation
 * @return array
 */
function kia_add_variation_options_other_dimensions( $loop, $variation_data, $variation ) {

    $variation_obj = wc_get_product( $variation->ID );

    $unit = get_option( 'woocommerce_dimension_unit' );

    if ( class_exists( 'Automattic\WooCommerce\Utilities\I18nUtil' )  ) {
          $unit = Automattic\WooCommerce\Utilities\I18nUtil::get_dimensions_unit_label( $unit );
    }

    woocommerce_wp_text_input( array(
        'id' => 'diameter[' . $loop . ']',
        'class' => 'short',
        'label'       => sprintf( esc_html__( 'Diameter (%s)', 'extra-variation-data' ), esc_html( $unit ) ),
        'desc_tip'    => 'true',
        'description' => __( 'Some dimension data to display for this variation.', 'extra-variation-data' ),
        'value'       => $variation_obj->get_meta( '_diameter', true ),
        'placeholder' => wc_format_localized_decimal( 0 ),
        'wrapper_class' => 'form-row form-row-first',
    ) );

    woocommerce_wp_text_input( array(
        'id' => 'thickness[' . $loop . ']',
        'class' => 'short',
        'label'       => sprintf( esc_html__( 'Thickness (%s)', 'extra-variation-data' ), esc_html( $unit ) ),
        'desc_tip'    => 'true',
        'description' => __( 'Some dimension data to display for this variation.', 'extra-variation-data' ),
        'value'       => $variation_obj->get_meta( '_thickness', true ),
        'placeholder' => wc_format_localized_decimal( 0 ),
        'wrapper_class' => 'form-row form-row-last',
    ) );

}
add_action( 'woocommerce_product_after_variable_attributes', 'kia_add_variation_options_other_dimensions', 10, 3 );


// Save product variation custom fields values
function kia_save_variation_options_other_dimensions( $variation, $i ) {

    if ( isset( $_POST['diameter'][$i] ) ) {
        $variation->update_meta_data( '_diameter', wc_clean( $_POST['diameter'][$i] ) );
    }

    if ( isset( $_POST['thickness'][$i] ) ) {
        $variation->update_meta_data( '_thickness', wc_clean( $_POST['thickness'][$i] ) );
    }

}
add_action( 'woocommerce_admin_process_variation_object', 'kia_save_variation_options_other_dimensions', 10 ,2 );

/**
 * Add data to json encoded variation form
 *
 * @param  array  $data - This will be the variation's json data.
 * @param  WC_Product_Variable $product
 * @param  WC_Product_Variation $variation
 * @return array
 */
function kia_available_variation( $data, $product, $variation ) {
    $diameter  = $variation->get_meta( '_diameter', true );
    $new_data['diameter_html']  = $diameter ? sprintf( esc_html__( 'Diameter: %s', 'extra-variation-data' ), kia_format_single_dimensions( $diameter ) ) : '';

    $thickness  = $variation->get_meta( '_thickness', true );
    $new_data['thickness_html']  = $thickness ? sprintf( esc_html__( 'Thickness: %s', 'extra-variation-data' ), kia_format_single_dimensions( $thickness ) ) : '';

    return array_merge( $data, $new_data );

}
add_filter( 'woocommerce_available_variation', 'kia_available_variation', 10, 3 );

/**
 * Utility to format dimension for display.
 *
 * @param  string $dimension_string Single dimension.
 * @return string
 */
function kia_format_single_dimensions( $dimension_string ) {

	if ( ! empty( $dimension_string ) ) {

        $dimension_label = get_option( 'woocommerce_dimension_unit' );

        if ( class_exists( 'Automattic\WooCommerce\Utilities\I18nUtil' )  ) {
          $dimension_label = Automattic\WooCommerce\Utilities\I18nUtil::get_dimensions_unit_label( $dimension_label );
        }

		$dimension_string = sprintf(
			// translators: 1. A formatted number; 2. A label for a dimensions unit of measure. E.g. 3.14 cm.
			esc_html_x( '%1$s %2$s', 'formatted dimensions', 'extra-variation-data' ),
			$dimension_string,
			$dimension_label
		);
	} else {
		$dimension_string = esc_html__( 'N/A', 'extra-variation-data' );
	}

	return $dimension_string;
}

/**
 * Add a <div> element to hold dimension output.
 * The class name needs to match the find() statement in kia_variable_footer_scripts()
 */
function kia_add_variation_dimension_holder() {
    echo '<div class="woocommerce-variation woocommerce-variation-dimension-data"></div>';
}
add_action( 'woocommerce_single_variation', 'kia_add_variation_dimension_holder', 15 );

/**
 * Add scripts to variable products.
 */
function kia_on_found_template_for_variable_add_to_cart() {
    add_action( 'wp_print_footer_scripts', 'kia_variable_footer_scripts', 99 );
}
add_action( 'woocommerce_variable_add_to_cart', 'kia_on_found_template_for_variable_add_to_cart', 30 );

/**
 * Print backbone template and inline scripts in the footer.
 */
function kia_variable_footer_scripts() { ?>

    <script type="text/template" id="tmpl-variation-template-dimension-data">
        <div class="woocommerce-variation-diameter">{{{ data.variation.diameter_html }}}</div>
        <div class="woocommerce-variation-thickness">{{{ data.variation.thickness_html }}}</div>
    </script>

    <script type="text/javascript">
        jQuery( document ).ready(function($) {
            $('form.cart')
                .on('found_variation', function( event, variation ) {

                    template     = wp.template( 'variation-template-dimension-data' );

                    $template_html = template( {
                        variation: variation
                    } );

                    $(this).find('.woocommerce-variation-dimension-data').html( $template_html ).slideDown();

                })
                .on( 'reset_data', function( event, variation ) {
                    $(this).find('.woocommerce-variation-dimension-data').slideUp( 200 );
                });
        });
    </script>
<?php
}
@helgatheviking
Comment

Leave a comment
Footer
© 2024 GitHub, Inc.
Footer navigation

    Terms
    Privacy
    Security
    Status
    Docs
    Contact

