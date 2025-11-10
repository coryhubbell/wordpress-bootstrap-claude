<?php
/**
 * WordPress Bootstrap Claude Functions
 * @package WP_Bootstrap_Claude
 */

define( 'WPBC_VERSION', '1.0.0' );

function wpbc_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'wp-bootstrap-claude' ),
    ) );
}
add_action( 'after_setup_theme', 'wpbc_setup' );

function wpbc_scripts() {
    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' );
    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'wpbc_scripts' );
