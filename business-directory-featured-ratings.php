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
    public $item_posts;
    public $_column_headers;
    public $item_posts_index = array();

    function __construct(){
        
    }

    public function _install_or_upgrade(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpbdp_featured_ratings';

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          rating_id mediumint(9),
          UNIQUE KEY id (id),
          UNIQUE (rating_id)
        );";

        if ( $this->test_requirements() == true ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    function test_requirements(){
        if (class_exists('BusinessDirectory_RatingsModule'))
            return true;
        else
            return false;
    }

    function get_columns(){
        return array(
            'ip_address' => __('User/IP', 'wpbdp-featured-ratings'),
            'rating' => __('Rating', 'wpbdp-featured-ratings'),
            'comment' => __('Comment', 'wpbdp-featured-ratings'),
            'listing' => __('Listing', 'wpbdp-featured-ratings'),
            'featured' => __('Featured', 'wpbdp-featured-ratings')
            );
    }

    //update a single item to feature or unfeature it:
    function test_get_action() {
        if ( isset($_REQUEST['action']) && isset($_REQUEST['rating']) ) {
            $action = $_REQUEST['action'];
            $rating_id = $_REQUEST['rating'];
        }
        else
            return "Choose a review to feature or unfeature.";

        if (is_integer(intval($rating_id)) && ($action == "feature" || $action == "unfeature"))
            return $this->perform_action($action, intval($rating_id) );
        return "Error: Poorly formed request. Was not able to update featured reviews.";
    }

    function perform_action($action, $rating_id) {
        
        global $wpdb;
        $tablename = $wpdb->prefix . 'wpbdp_featured_ratings';

        if ($action == 'feature') {
            $query_result = $wpdb->insert( 
                $tablename, 
                array(  
                   'rating_id' => $rating_id 
                ), 
                array(  
                    '%d' 
                ) 
            );
            if (!is_int($query_result))
                return $query_result;
            else
                return "You have featured a review!";
        }
        elseif ($action == 'unfeature') {
            $query_result = $wpdb->delete( $tablename , array( "rating_id" => $rating_id ) , array( '%d' ) );
            
            if (!is_int($query_result))
                return $query_result;
            else
                return "You have unfeatured a review.";
        }
    }

    function get_pagination(){
        if ( isset($_REQUEST['pg']) ) {
            $pagenum = $_REQUEST['pg'];
        }
        else
            return 0;

        if (is_integer(intval($pagenum)) && $pagenum > 0 )
            return $pagenum;
        return 0;
    }
    function pagination_links(){
        $html = "";
        $current_page = $this->get_pagination();
        if ( $current_page > 1 )
            $html .= sprintf( '<a href="?page=%s&pg=">First</a> ',$_REQUEST['page'], 0 );
        if ( $current_page > 0 )
            $html .= sprintf( '<a href="?page=%s&pg=%s">Previous</a> ',$_REQUEST['page'], $current_page - 1 );
        if ( count($this->item_posts_index) >= 30 )
            $html .= sprintf( '<a href="?page=%s&pg=%s">Next</a> ',$_REQUEST['page'], $current_page + 1 );
        return $html;
    }

    public function prepare_items() {
        $pagenum = $this->get_pagination();

        global $wpdb;
        $ratings_table = $wpdb->prefix . 'wpbdp_ratings';
        $featured_ratings_table = $wpdb->prefix . 'wpbdp_featured_ratings';

        //Here's a sample SQL query that does what I want:
        $query = $wpdb->prepare("
        SELECT r.id, r.listing_id, r.rating, r.user_id, r.user_name, r.ip_address, r.comment, r.created_on, r.approved, f.rating_id,
        CASE WHEN f.rating_id is null THEN 0 ELSE 1 END AS Ordering
        FROM $ratings_table r
        LEFT OUTER JOIN $featured_ratings_table f
        ON f.rating_id=r.id
        WHERE approved = %d
        ORDER BY
        Ordering DESC,
        r.created_on DESC
        LIMIT %d, %d;
        ", 1, 30 * $pagenum, 30); 
        
      
        // SELECT * FROM {$wpdb->prefix}wpbdp_ratings WHERE approved = %d ORDER BY id DESC", 1);
        $data = $wpdb->get_results($query);
        $this->items = $data;
        $this->_column_headers = $this->get_columns();

        $this->item_posts = $this->prepare_listings_array();
        $this->item_posts_index = $this->prepare_listings_index();
    }

    //grabs post objects for listings to display name and permalink prettily
    function prepare_listings_array() {

        $data = $this->items;
        $post_ids = array();

        foreach ($data as $rating) {
            $post_ids[]=$rating->listing_id;
        }

        $args = array(
            'post_type' => 'wpbdp_listing',
            'post__in' => $post_ids
        );
        
        $query = new WP_Query( $args );
        return $query;

    }

    function prepare_listings_index() {
        $result = array();
        global $post;
        if ( $this->item_posts->have_posts() ){
            while ($this->item_posts->have_posts()){
                $this->item_posts->the_post();
                $result[get_the_id()] = array(
                        "permalink" => get_permalink(),
                        "title" => get_the_title()
                    );
            }
        }

        // Restore original Post Data
        wp_reset_postdata();
        return $result;
    }

    public function listing_link_html($id, $class = ''){
        if (is_integer(intval($id))){
            $post_array = $this->item_posts_index[$id];
            return "<a href='" . $post_array["permalink"] ."' class='" . $class . "'>" . $post_array["title"] . "</a>";
        }
        return "Invalid listing ID";
    }

    /* Column result functions */

    public function column_ip_address($row) {
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

     public function column_comment($row) {
        $html  = '';

        $html .= '<div class="submitted-on">';
        $html .= sprintf(__('Submitted on <i>%s</i>', 'wpbdp-ratings'), date_i18n(get_option('date_format'), strtotime($row->created_on)));
        $html .= '</div>';

        $html .= '<p>' . substr(esc_attr($row->comment), 0, 100) . '</p>';

        return $html;
    }

    public function column_listing($row) {
        return $this->listing_link_html($row->listing_id, "listing-link");
        // return "DEBUG: Some Listing Link";
     }
    
    public function column_featured($row) {

        if ($row->Ordering == 1)
            $action_link = sprintf('Yes: <a href="?page=%s&action=%s&rating=%d">Unfeature</a>',$_REQUEST['page'],'unfeature',$row->id);
        else
            $action_link = sprintf('No: <a href="%s?page=%s&action=%s&rating=%d">Feature</a>',admin_url( 'admin.php' ), $_REQUEST['page'],'feature',$row->id); 

        return $action_link;
    }

    //admin panel menu and options page functions:

    // Add Menu item
    

    public function featured_ratings_plugin_menu() {
        //add_options_page( 'Featured User Ratings', 'Featured Ratings', 'manage_options', 'wpbdp-featured-ratings', 'render_featured_ratings_admin_page' );
    
        add_menu_page(
            'Featured Ratings', //__('Featured Ratings', 'wpbdp-featured-ratings'),
            'Manage Featured Ratings', //__('Manage Featured Ratings', 'wpbdp-featured-ratings'),
            'activate_plugins',
            'wpbdp-manage-featured-ratings',
            array($this, 'render_featured_ratings_admin_page')
        );    

    }

    public function render_featured_ratings_admin_page() {
        if ( !current_user_can( 'activate_plugins' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        //check for actions and execute them on the database
        $current_action = $this->test_get_action();

        //set class variables:
        $this->prepare_items();

        //render page components:
        echo '<div class="wrap">'. "<p>$current_action</p>";;
        echo $this->featured_ratings_admin_page_top();
        echo $this->featured_ratings_table();
        echo $this->featured_ratings_admin_page_bottom();

        echo '</div>';
    }

    function featured_ratings_admin_page_top() {
        $html = '<div class="featured-rating-page-wrapper"><div id="icon-edit" class="icon32 icon32-posts-post"><br></div>
                <h2>Manage Featured Ratings</h2>'."\n";
        return $html;
    }

    function featured_ratings_table() {
        //$callable_name = '';

        $table = '<table class="wp-list-table widefat fixed pages" cellspacing="0">
        <thead>
        <tr>'. "\n";
        foreach ($this->_column_headers as $column => $value) {
            $table .= "<th scope='col' id='featured-rating-column-$column' class='column column-$column'>$value</th>\n";
        }
        $table .= "</thead>\n<tbody>\n";
       
        //cycle through each item building out the table, calling column_ functions for each cell
        foreach($this->items as $current_rating){
            if ($current_rating->Ordering == 1)
                $tablerow = "<tr class='featured-row'>\n";
            else
                $tablerow = "<tr class='normal-row'>\n";

            foreach ($this->_column_headers as $column => $nicename) {
                $tablerow .= "<td>";
                if (is_callable(array($this, "column_$column"), true, $callable_name))
                    $tablerow .= call_user_func( array($this,$callable_name), $current_rating );
                else
                    $tablerow .= "Error calling $callable_name";
                $tablerow .= "</td>\n";
            }

            $tablerow .= "</tr>\n";
            $table .= $tablerow;
        }

        $table .= '</tbody></table>';


        return $table;
    }

    function featured_ratings_admin_page_bottom() {
        //DEBUGGING: $html = "<p>". serialize($this->item_posts_index) ."</p><p>" . json_encode($this->item_posts) . "</p></div>";
        $html = "";
        $html .= $this->pagination_links();
        return $html;
    }



    /**** FRONT END DATA DISPLAY *****/
    function query_featured_reviews(){
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'wpbdp_ratings';
        $featured_ratings_table = $wpdb->prefix . 'wpbdp_featured_ratings';
        $listings_table = $wpdb->posts;

        //query that does not include ID and post_title to make clickable link to the listing
        // $query = $wpdb->prepare( " SELECT r.rating, r.user_name, r.comment, p.ID, p.post_title
        //     FROM {$wpdb->prefix}wpbdp_ratings r
        //     INNER JOIN $wpdb->posts p ON r.listing_id = p.ID
        //     WHERE r.approved = %d
        //     ORDER BY r.id ", 1 );

        $query = $wpdb->prepare("
            SELECT r.id, r.listing_id, r.rating, r.user_id, r.user_name, r.ip_address, r.comment, r.created_on, r.approved, f.rating_id, l.id, l.post_title, l.post_name
            FROM 
            $ratings_table r
            INNER JOIN 
            $featured_ratings_table f
            ON f.rating_id=r.id
            INNER JOIN
            $listings_table l
            ON l.id = r.listing_id
            WHERE approved = %d
            ORDER BY
            r.created_on DESC
            ", 1);

        $results = $wpdb->get_results($query);

        return $results;
    }

    public function render_featured_reviews(){
        $featured_reviews = $this->query_featured_reviews();
        $html = "<ul class='featured-reviews-list'>\n";

        foreach ($featured_reviews as $review) {
            $link = site_url( "business-directory/". $review->listing_id . "/" . $review->post_name );
            $render = "<li class='featured-review'>\n";
            $render .= "<h4><a href='$link'>$review->post_title</a></h4>\n";
            $render .= "<p>" . sprintf('<span class="wpbdp-ratings-stars" data-readonly="readonly" data-value="%s">%s</span><br />', $review->rating,$review->rating) . "$review->comment</p>\n";
            

            $render .= "<div class='user-name'>$review->user_name</div>";
            $render .= "</li>\n";

            $html .= $render;
        }

        $html .= "</ul>\n";
        return $html;
    }

    function register_css(){
        wp_register_style( 'business-directory-featured-ratings-style', plugins_url( 'business-directory-featured-ratings.css' , __FILE__ ), array(), '0.1' );

        wp_enqueue_style( 'business-directory-featured-ratings-style' );
    }
} //class

$GLOBALS['featured-ratings-admin-page'] = new My_Featured_Ratings_Admin();
register_activation_hook( __FILE__, array($GLOBALS['featured-ratings-admin-page'], '_install_or_upgrade') );

//add menu item
add_action( 'admin_menu', array($GLOBALS['featured-ratings-admin-page'], 'featured_ratings_plugin_menu') );

//register and enqueue stylesheet:
add_action( 'wp_enqueue_scripts', array($GLOBALS['featured-ratings-admin-page'], 'register_css'));
add_action( 'admin_enqueue_scripts', array($GLOBALS['featured-ratings-admin-page'], 'register_css'));



class FeaturedRatingsWidget extends WP_Widget {
  function FeaturedRatingsWidget() {
    parent::WP_Widget( false, $name = 'Featured Ratings' );
  }
 
  function widget( $args, $instance ) {
    extract( $args );
    $title = apply_filters( 'widget_title', $instance['title'] );
    
    echo $before_widget;
    
    if ($title) {
        echo $before_title . $title . $after_title;
    }
    
    echo $GLOBALS['featured-ratings-admin-page']->render_featured_reviews();
    echo $after_widget;
     
  }
 
  function update( $new_instance, $old_instance ) {
    return $new_instance;
  }
 
  function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
        $title = $instance[ 'title' ];
    }
    else {
        $title = __( 'Featured Ratings', 'wpbdp-featured-ratings' );
    }

    $current_block = "<p><label for='" . $this->get_field_id( 'title' ) . "'>" . _e( 'Title:' );
    $current_block .= "<input class='widefat' id='" . $this->get_field_id( 'title' ) . "' name='" . $this->get_field_name( 'title' ) . "' type='text' value='$title' /></label></p>\n";
    $current_block .= "<p><a href='" . admin_url( 'admin.php?page=wpbdp-manage-featured-ratings' ) . "'>Manage Featured Ratings</a></p>";
    echo $current_block;
  }

}//widget class
 

add_action( 'widgets_init', 'FeaturedRatingsWidgetInit' );

function FeaturedRatingsWidgetInit() {
  register_widget( 'FeaturedRatingsWidget' );
}



?>
