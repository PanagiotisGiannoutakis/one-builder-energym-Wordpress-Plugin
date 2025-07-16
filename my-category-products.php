<?php
/*
Plugin Name: One Builder - Energym
Plugin URI: https://yourwebsite.com/
Description: Ένα plugin που εμφανίζει προϊόντα από συγκεκριμένη κατηγορία WooCommerce μέσω shortcode.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com/
License: GPL2
*/

// Αποτροπή άμεσης πρόσβασης στο αρχείο
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Καταχώρηση του shortcode.
 *
 * Το shortcode θα είναι: [products_by_category category_slug="your-category-slug" limit="4" columns="4"]
 *
 * - category_slug: (απαιτούμενο) Το slug της κατηγορίας από την οποία θέλετε να εμφανίσετε προϊόντα.
 * - limit: (προαιρετικό) Ο μέγιστος αριθμός προϊόντων που θα εμφανιστούν (προεπιλογή: 4).
 * - columns: (προαιρετικό) Ο αριθμός των στηλών για την εμφάνιση των προϊόντων (προεπιλογή: 3).
 */
function my_category_products_shortcode( $atts ) {
    // Ορισμός προεπιλεγμένων τιμών και συγχώνευση με τα χαρακτηριστικά του shortcode
    $atts = shortcode_atts(
        array(
            'category_slug' => '', // Κενό slug ως προεπιλογή, θα πρέπει να το συμπληρώσει ο χρήστης
            'limit'         => 4,
            'columns'       => 3,
        ),
        $atts,
        'products_by_category'
    );

    $category_slug = sanitize_text_field( $atts['category_slug'] );
    $limit         = absint( $atts['limit'] );
    $columns       = absint( $atts['columns'] );

    // Έλεγχος αν υπάρχει το WooCommerce
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>Το WooCommerce δεν είναι ενεργοποιημένο. Αυτό το shortcode απαιτεί το WooCommerce.</p>';
    }

    // Έλεγχος αν έχει δοθεί category_slug
    if ( empty( $category_slug ) ) {
        return '<p>Παρακαλώ καθορίστε ένα "category_slug" για το shortcode [products_by_category].</p>';
    }

    // Ορίσματα για το WP_Query
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ),
        ),
        'orderby'        => 'date', // Προεπιλογή: Ταξινόμηση κατά ημερομηνία
        'order'          => 'DESC', // Προεπιλογή: Φθίνουσα σειρά (πιο πρόσφατα πρώτα)
    );

    $products_query = new WP_Query( $args );

    $output = '';

    if ( $products_query->have_posts() ) {
        $output .= '<div class="my-custom-products-grid my-custom-products-columns-' . esc_attr( $columns ) . '">';

        while ( $products_query->have_posts() ) {
            $products_query->the_post();
            global $product;

            $product_id    = get_the_ID();
            $product_title = get_the_title();
            $product_url   = get_permalink();
            $product_image = has_post_thumbnail( $product_id ) ? get_the_post_thumbnail( $product_id, 'medium' ) : '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" />';

            $output .= '<div class="my-custom-product-item">';

            // Εικόνα - ΤΩΡΑ ΕΧΕΙ LINK
            $output .= '<a href="' . esc_url( $product_url ) . '" title="' . esc_attr( $product_title ) . '">';
            $output .= '<div class="my-custom-product-image">' . $product_image . '</div>';
            $output .= '</a>'; // Κλείσιμο του link για την εικόνα

            // Τίτλος - ΕΧΕΙ ΚΑΙ ΑΥΤΟΣ LINK
            $output .= '<h3 class="my-custom-product-title">';
            $output .= '<a href="' . esc_url( $product_url ) . '" title="' . esc_attr( $product_title ) . '">';
            $output .= esc_html( $product_title );
            $output .= '</a>';
            $output .= '</h3>';

            // Το URL παραμένει αφαιρεμένο όπως ζητήθηκε προηγουμένως

            $output .= '</div>'; // Κλείσιμο του my-custom-product-item
        }
        $output .= '</div>'; // Κλείσιμο του container
    } else {
        $output .= '<p>Δεν βρέθηκαν προϊόντα στην κατηγορία: ' . esc_html( $category_slug ) . '</p>';
    }


    // Επαναφορά των δεδομένων post για να μην επηρεαστούν άλλες queries
    wp_reset_postdata();

    return $output;
}
add_shortcode( 'products_by_category', 'my_category_products_shortcode' );

/**
 * Enqueue plugin styles.
 * Loads the custom CSS file for the shortcode.
 */
function my_category_products_enqueue_styles() {
    // Check if the shortcode is present on the current page/post.
    global $post;
    // Εάν το $post δεν έχει τεθεί ακόμα (π.χ., σε ορισμένες αρχειακές σελίδες),
    // ή αν το shortcode βρίσκεται σε ένα widget, το has_shortcode μπορεί να μην λειτουργήσει πάντα.
    // Για απλούστευση, μπορούμε να το φορτώσουμε πάντα, ή να κάνουμε έναν πιο σύνθετο έλεγχο.
    // Για τώρα, ας υποθέσουμε ότι το shortcode είναι στο post_content.

    // Προσθήκη ενός ελέγχου για να βεβαιωθούμε ότι το $post είναι ένα αντικείμενο
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'products_by_category' ) ) {
        wp_enqueue_style(
            'my-custom-products-style', // Unique handle
            plugins_url( 'css/style.css', __FILE__ ), // URL to your CSS file
            array(), // Dependencies
            '1.0.0', // Version (αλλάξτε αυτό όταν ενημερώνετε το CSS)
            'all' // Media type
        );
    }
}
add_action( 'wp_enqueue_scripts', 'my_category_products_enqueue_styles' );