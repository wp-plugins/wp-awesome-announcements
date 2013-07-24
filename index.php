<?php
/*
Plugin Name: WP Awesome Announcements
Plugin URI: http://h2cweb.net/wp-awesome-announcements-plugin
Description: Best WordPress Announcements Plugin integrated with Custom Post Type. WP Awesome Announcements based on latest JQuery UI.
Version: 1.0
Author: Liton arefin
Author URI: http://h2cweb.net

Credits: http://wp.tutsplus.com/tutorials/plugins/building-a-simple-announcements-plugin-for-wordpress/
*/

// Define constant for plugin path
define('H2CWEB_ANCMNT', plugin_dir_url( __FILE__ ));

//Create Custom Post Type
function sap_register_announcements() {

	$labels = array(
		'name' => _x( 'Announcements', 'post type general name' ),
		'singular_name' => _x( 'Announcement', 'post type singular name' ),
		'add_new' => _x( 'Add New', 'Announcement' ),
		'add_new_item' => __( 'Add New Announcement' ),
		'edit_item' => __( 'Edit Announcement' ),
		'new_item' => __( 'New Announcement' ),
		'view_item' => __( 'View Announcement' ),
		'search_items' => __( 'Search Announcements' ),
		'not_found' =>  __( 'No Announcements found' ),
		'not_found_in_trash' => __( 'No Announcements found in Trash' ),
		'parent_item_colon' => ''
	);

 	$args = array(
     	'labels' => $labels,
     	'singular_label' => __('Announcement', 'simple-announcements'),
     	'public' => true,
	  	'capability_type' => 'post',
     	'rewrite' => false,
     	'supports' => array('title', 'editor'),
     );
 	register_post_type('announcements', $args);
}
add_action('init', 'sap_register_announcements');

//Create meta box
function sap_add_metabox()
{
	add_meta_box( 'sap_metabox_id', 'Scheduling', 'sap_metabox', 'announcements', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sap_add_metabox' );

//Add fields to meta box
function sap_metabox( $post )
{
	$values = get_post_custom( $post->ID );
	$start_date = isset( $values['sap_start_date'] ) ? esc_attr( $values['sap_start_date'][0] ) : '';
	$end_date = isset( $values['sap_end_date'] ) ? esc_attr( $values['sap_end_date'][0] ) : '';
	wp_nonce_field( 'sap_metabox_nonce', 'metabox_nonce' );
	?>
	<p>
		<label for="start_date">Start date</label>
		<input type="text" name="sap_start_date" id="sap_start_date" value="<?php echo $start_date; ?>" />
	</p>
	<p>
		<label for="end_date">End date</label>
		<input type="text" name="sap_end_date" id="sap_end_date" value="<?php echo $end_date; ?>" />
	</p>
	<?php

}

//Validate & save meta box data
function sap_metabox_save( $post_id )
{
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
    return $post_id;
	
	if( !isset( $_POST['metabox_nonce'] ) || !wp_verify_nonce( $_POST['metabox_nonce'], 'sap_metabox_nonce' ) )
    return $post_id;
	
	if( !current_user_can( 'edit_post' ) )
    return $post_id;

    // Make sure data is set
	if( isset( $_POST['sap_start_date'] ) ) {
        
        $valid = 0;
        $old_value = get_post_meta($post_id, 'sap_start_date', true);
        
        if( $_POST['sap_start_date'] != '' ){

            $date = $_POST['sap_start_date'];
            $date = explode( '-', (string) $date );
            $valid = checkdate($date[1],$date[2],$date[0]);
        }
        
        if($valid)
            update_post_meta( $post_id, 'sap_start_date', $_POST['sap_start_date'] );
        elseif (!$valid && $old_value)
            update_post_meta( $post_id, 'sap_start_date', $old_value );
        else
            update_post_meta( $post_id, 'sap_start_date', '');
    }
		
	if( isset( $_POST['sap_end_date'] ) ) {

        if( $_POST['sap_start_date'] != '' ){

            $old_value = get_post_meta($post_id, 'sap_end_date', true);
            
            $date = $_POST['sap_end_date'];
            $date = explode( '-', (string) $date );
            $valid = checkdate($date[1],$date[2],$date[0]);
        }
        if($valid)
            update_post_meta( $post_id, 'sap_end_date', $_POST['sap_end_date'] );
        elseif (!$valid && $old_value)
            update_post_meta( $post_id, 'sap_end_date', $old_value );
        else
            update_post_meta( $post_id, 'sap_end_date', '');
    }
}
add_action( 'save_post', 'sap_metabox_save' );

// Load scripts and styles
function sap_backend_scripts($hook) {
    global $post;

	if( ( !isset($post) || $post->post_type != 'announcements' ))
	return;
 
	wp_enqueue_style( 'datepicker-style', H2CWEB_ANCMNT . 'css/ui-lightness/jquery-ui.css');	 
	wp_enqueue_script( 'datepicker', H2CWEB_ANCMNT . 'js/jquery-ui.min.js' ); 
    wp_enqueue_script( 'announcements', H2CWEB_ANCMNT . 'js/announcements.js', array( 'jquery' ) );
}
add_action('admin_enqueue_scripts', 'sap_backend_scripts');

function sap_frontend_scripts() {
	wp_enqueue_style( 'announcements-style', H2CWEB_ANCMNT . 'css/announcements.css');	 
    wp_enqueue_script( 'announcements', H2CWEB_ANCMNT . 'js/announcements.js', array( 'jquery' ) );
    wp_enqueue_script( 'cookies', H2CWEB_ANCMNT . 'js/jquery.cookie.js', array( 'jquery' ) );    
    wp_enqueue_script( 'cycle', H2CWEB_ANCMNT . 'js/jquery.cycle.lite.js', array( 'jquery' ) );    
}
add_action('wp_enqueue_scripts', 'sap_frontend_scripts');

//Display announcements
function sap_display_announcement() {

    global $wpdb;
    //Select announcements, which start before and end after current date and those with empty dates
    $sap_ids = $wpdb->get_results("SELECT `m1`.`post_id` FROM ".$wpdb->prefix."postmeta `m1`
                                   JOIN ".$wpdb->prefix."postmeta `m2` ON `m1`.`post_id` = `m2`.`post_id`                                   
                                   WHERE 
                                   (`m1`.`meta_key` = 'sap_start_date' AND (UNIX_TIMESTAMP(`m1`.`meta_value`) < UNIX_TIMESTAMP() OR `m1`.`meta_value` = ''))                                   
                                   AND 
                                   (`m2`.`meta_key` = 'sap_end_date' AND (UNIX_TIMESTAMP(`m2`.`meta_value`) > UNIX_TIMESTAMP() OR `m2`.`meta_value` = ''))",                                   
                                   ARRAY_N);

    if ($sap_ids){
        foreach ($sap_ids as $id){
            $post_id[] = $id[0];            
        }
        $ids = implode(",",$post_id);
        
        $announcements = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts AS `posts` WHERE `posts`.`ID` IN (".$ids.")");
        
    }
    
    //HTML output
    if($announcements) :
        ?>
            <div id="announcements" class="hidden"> 
                <div class="wrapper">
                    <a class="close" href="#" id="close"><?php _e('x', 'simple-announcements'); ?></a>                    
                    <div class="sap_message">
                    <?php
                    foreach ($announcements as $announcement) {
                    ?>                        
                        <?php echo do_shortcode(wpautop(($announcement->post_content))); ?>
                    <?php
                    }
                    ?>
                    </div>
                </div>
            </div>
        <?php
	endif;
}
add_action('wp_footer', 'sap_display_announcement');
