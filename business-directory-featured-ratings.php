<?php
/*
Plugin Name: Business Directory Plugin - Featured Ratings
Version: 0.1
Author: Nate Otto
Description: A free enhancement to the premium Ratings module of the Business Directory Plugin http://www.businessdirectoryplugin.com that allows an admin to manually curate specific user reviews for additional featured display.
Author URI: http://ottopopulate.com
*/




class BusinessDirectory_FeaturedRatingsModule {
    const VERSION = '0.1';
    const REQUIRED_BD_VERSION = '2.2';
    const FEATURED_RATINGS_DB_VERSION = '0.1';
}


class My_Featured_Ratings_Admin {

    public $items;
    public $_column_headers;

    function __construct(){
        
    }

    public function _install_or_upgrade(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpbdp_featured_ratings';

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          rating_id mediumint(9),
          UNIQUE KEY id (id)
        );";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }


    function get_columns(){
        return array(
            'user_ip' => __('User/IP', 'wpbdp-featured-ratings'),
            'rating' => __('Rating', 'wpbdp-featured-ratings'),
            'comment' => __('Comment', 'wpbdp-featured-ratings'),
            'listing' => __('Listing', 'wpbdp-featured-ratings'),
            'featured' => __('Featured', 'wpbdp-featured-ratings')
            );
    }

    public function prepare_items() {
        $data = array();

        /*Here's a sample SQL query that does what I want:
        SELECT r.id, r.listing_id, r.rating, r.user_id, r.user_name, r.ip_address, r.comment, r.created_on, r.approved, f.rating_id,
        CASE WHEN f.rating_id is null THEN 0 ELSE 1 END AS Ordering
        FROM wp_wpbdp_ratings r
        LEFT OUTER JOIN wp_wpbdp_featured_ratings f
        ON f.rating_id=r.id
        ORDER BY
        Ordering DESC,
        r.created_on DESC
        LIMIT 0, 30;

        */
        // $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_ratings WHERE approved = %d ORDER BY id DESC", 1);
        // $this->items = $wpdb->get_results($query);

        $this->_column_headers = $this->get_columns();
        $this->items = $data;
    }



//     public function prepare_items2() {
//       $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

//       //needs to include listing title and permalink compatible with get_permalink()/get_title() if possible
//       global $wpdb;
//       $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpbdp_ratings WHERE approved = %d ORDER BY id DESC", 1);
//       $this->items = $wpdb->get_results($query);
//     }



    /* Column result functions */

    public function column_user_ip($row) {
        $html  = '';

        if ($row->user_id == 0) {
            $html .= '<b>' . esc_attr($row->user_name) . '</b>';
        } else {
            $html .= '<b>' . get_the_author_meta('display_name', $row->user_id) . '</b>';
        }
        $html .= '<br />' . $row->ip_address;

        return $html;
    }

    public function column_rating($row) {
        return sprintf('<span class="wpbdp-ratings-stars" data-readonly="readonly" data-value="%s">%s</span>', $row->rating,$row->rating);
    }

//     public function column_comment($row) {
//         $html  = '';

//         $html .= '<div class="submitted-on">';
//         $html .= sprintf(__('Submitted on <i>%s</i>', 'wpbdp-ratings'), date_i18n(get_option('date_format'), strtotime($row->created_on)));
//         $html .= '</div>';

//         $html .= '<p>' . substr(esc_attr($row->comment), 0, 100) . '</p>';

//         $actions = array();
//         $actions['approve_rating'] = sprintf('<a href="%s">%s</a>',
//           esc_url(add_query_arg(array('action' => 'approve', 'id' => $row->id))),
//           __('Approve', 'wpbdp-ratings'));
//         $actions['delete'] = sprintf('<a href="%s">%s</a>',
//           esc_url(add_query_arg(array('action' => 'delete', 'id' => $row->id))),
//           __('Delete', 'wpbdp-ratings'));        
//         $html .= $this->row_actions($actions);

//         return $html;
//     }

//     public function column_listing($row) {
//             //REFACTOR TO do a join on prepare_columns AVOID 2n additional DB queries per display of this screen
//         return sprintf('<a href="%s">%s</a>', get_permalink($row->listing_id), get_the_title($row->listing_id));
//     }
    
    public function column_featured($row) {
        //refactor to show only one action per listing: "Feature" button on listings that are presently unfeatured and vice versa.
        $featured_status = $row->featured;
        $action = "";
        if ($row->featured == 0){
            $action = sprintf('<a href="?page=%s&action=%s&rating=%s">Feature</a>',$_REQUEST['page'],'feature',$row->listingid);
            $featured_status = "No";
        }
        else{
            $action = sprintf('<a href="?page=%s&action=%s&rating=%s">Unfeature</a>',$_REQUEST['page'],'unfeature',$item['ID']);
            $featured_status = "Yes";
        }
        return sprintf('%1; %2', $featured_status, $action );
    }

} //class

$GLOBALS['featured-ratings-admin-page'] = new My_Featured_Ratings_Admin();
register_activation_hook( __FILE__, array($GLOBALS['featured-ratings-admin-page'], '_install_or_upgrade') );

//NO (this is from example blog post)
// function featured_ratings_admin_menu_items(){
//     add_menu_page( 'Directory Featured Ratings', 'Directory Featured Ratings', 'activate_plugins', 'manage-featured-ratings', 'render_featured_ratings_page' );
// }
// add_action( 'admin_menu', 'featured_ratings_admin_menu_items' );

// //NO (this is from example blog post)
// function render_featured_ratings_admin_page(){
//   $myListTable = new My_Featured_Ratings_Admin();
//   echo '</pre><div class="wrap"><h2>Manage Featured Ratings</h2>'; 
//   $myListTable->prepare_items(); 
//   $myListTable->display(); 
//   echo '</div>'; 
// }


?>
