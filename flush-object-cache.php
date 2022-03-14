<?php

add_action( 'admin_bar_menu', 'flush_objectcache_adminbar', 100 );
function flush_objectcache_adminbar() {
    global $wp_admin_bar;
    if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
        return false;
    }
    if ( ! is_admin() ) {
        return false;
    }
    /* if ( get_site_option( 'flush-opcache-hide-button' ) === '1' ) {
        return false;
    } */
    $base_url   = remove_query_arg( 'settings-updated' );
    $flush_url  = add_query_arg( array( 'flush_objectcache_action' => 'flushobjectcacheall' ), $base_url );
    $nonced_url = wp_nonce_url( $flush_url, 'flush_objectcache_all' );
    if ( ( is_multisite() && is_super_admin() && is_main_site() ) || ( ! is_multisite() && is_admin() ) ) {
        $wp_admin_bar->add_menu(
            array(
                'parent' => '',
                'id'     => 'flush_objectcache_button',
                'title'  => __( 'Flush Object Cache', 'flush-objectcache' ),
                'meta'   => array( 'title' => __( 'Flush Object Cache', 'flush-objectcache' ) ),
                'href'   => $nonced_url,
            )
        );
    }
}

add_action( 'admin_init', 'flush_objectcache' );
function flush_objectcache() {
    if ( ! isset( $_REQUEST['flush_objectcache_action'] ) ) {
        return;
    }
    if ( isset( $_REQUEST['settings-updated'] ) ) {
        return;
    }
    if ( ! is_admin() ) {
        wp_die( esc_html__( 'Sorry, you can\'t flush Object Cache.', 'flush-objectcache' ) );
    }
    $action = sanitize_key( $_REQUEST['flush_objectcache_action'] );
    if ( 'done' === $action ) {
        if ( is_multisite() ) {
            add_action( 'network_admin_notices', 'show_objectcache_notice' );
        } else {
            add_action( 'admin_notices', 'show_objectcache_notice' );
        }
        return;
    }
    check_admin_referer( 'flush_objectcache_all' );
    if ( 'flushobjectcacheall' === $action ) {
        flush_objectcache_reset();
    }
    wp_safe_redirect( esc_url_raw( add_query_arg( array( 'flush_objectcache_action' => 'done' ) ) ) );
    exit;
}

/**
 * Where Objectcache is actually flushed
 */
function flush_objectcache_reset() {
    wp_cache_flush()
    ? add_settings_error(
        'redis-cache',
        'flush',
        __( 'Object cache flushed.', 'redis-cache' ),
        'updated'
    )
    : add_settings_error(
        'redis-cache',
        'flush',
        __( 'Object cache could not be flushed.', 'redis-cache' ),
        'error'
    );
}

/**
 * Display a notice when Object Cache was flushed
 */
function show_objectcache_notice() {
    ?>
<div id="message" class="updated notice is-dismissible">
    <p><?php esc_html_e( 'Object Cache was successfully flushed.', 'flush-objectcache' ); ?></p>
</div>
    <?php
}
