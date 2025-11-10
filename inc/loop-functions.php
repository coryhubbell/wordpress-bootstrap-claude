<?php
/**
 * WordPress Loop Functions
 */

function wpbc_custom_query_loop( $args = array() ) {
    $defaults = array(
        'post_type' => 'post',
        'posts_per_page' => 10,
    );
    
    $args = wp_parse_args( $args, $defaults );
    $query = new WP_Query( $args );
    
    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post();
            get_template_part( 'template-parts/content' );
        endwhile;
        wp_reset_postdata();
    endif;
    
    return $query;
}
