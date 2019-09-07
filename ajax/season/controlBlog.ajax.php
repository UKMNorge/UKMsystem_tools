<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Wordpress\Blog;
require_once('UKM/Arrangement/Arrangement.php');
require_once('UKM/Wordpress/Blog.php');

$blog_id = $_POST['blog_id'];

$blog = get_site( $blog_id );
$blog_info = get_blog_details( $blog_id );
$site_type = get_blog_option( $blog_id, 'site_type');

switch( $site_type ) {
    case 'fylke':
    case 'land':
    case 'kommune':
        $pl_id = get_blog_option( $blog_id, 'pl_id');
        try {
            $arrangment = new Arrangement( $pl_id );
            if( $arrangment->erFellesmonstring() ) {
                $action = 'delete';
            } else {
                $action = 'clean';
            }
        } catch( Exception $e ) {
            $action = 'cleaned';
        }
    break;
    default:
        $action = 'skip';
    break;
}

switch( $action ) {
    case 'skip':
        $color = 'success';
        break;
    case 'delete':
        $color = 'danger';
        # Soft-delete = merk som slettes
        wpmu_delete_blog( $blog_id, false );
        break;
    case 'clean':
        $color = 'warning';
        # Fjern alt som har med mønstringen å gjøre
        # Bevarer nyheter, media og eventuelle sider.
        Blog::fjernArrangementData( $blog_id );
        break;
    case 'cleaned':
    default:
        $color = 'primary';
        break;
}


UKMsystem_tools::addResponseData(
    [
        'action' => $action,
        'color' => $color,
        'blog_name' => $blog_info->blogname,
        'path' => $blog_info->path,
        'site_type' => $site_type,
        'success' => true
    ]
);