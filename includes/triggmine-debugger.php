<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function triggmine_log( $item, $file ) {
    $d = date( "j-M-Y H:i:s e" );
    $home = dirname( __FILE__ );
    if ( is_object( $item ) ) {
        $item = (array)$item;
    }
    if ( is_array( $item ) ) {
        $item = print_r( $item, TRUE );
    }
    error_log( "[$d]: $item \n", 3, WP_CONTENT_DIR . "/$file.log" );
}