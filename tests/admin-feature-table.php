<?php

/**
 * Featured Ratings admin panel tests:
 */
class ExampleRatingData {
	public $ip_address = '192.168.1.1'; 
	public $user_name = 'steve'; 
	public $user_id = 0; 
	public $rating = 4; 
	public $comment = "What a decent establishment."; 
	public $listing = "a listing object?"; 
	public $featured = 1; 
}


class FeaturedAdminTestsTests extends WP_UnitTestCase {
    // public $plugin_slug = 'business-directory-featured-ratings';

    private $plugin;  
  
    function setUp() {  
          
        parent::setUp();  
        $this->plugin = $GLOBALS['featured-ratings-admin-page'];

    }

    function testPluginInitialization() {  
        $this->assertFalse( null == $this->plugin );  
    }

    //get_columns()
    function testPluginColumnsReturnsArray() {
    	$this->assertTrue( is_array( $this->plugin->get_columns() ), "ensures get_columns() returns an array.");
    }
    function testPluginColumnsReturnsFeaturedArrayKey() {
    	$array = $this->plugin->get_columns();
    	$this->assertTrue( array_key_exists('featured', $array), "ensures 'featured' is a returned column header" );
    }

    //prepare_items()

	function testPrepareItemsSetsItems(){
		//
	}

	//column functions:


	function testColumnIP() {
		$row = new ExampleRatingData;
		$this->assertContains( '192.168', $this->plugin->column_user_ip($row) );
	}

	function testColumnRating() {
		$row = new ExampleRatingData;
		$this->assertContains( 'data-value="4"', $this->plugin->column_rating($row) );
	}

	//featured
		function testColumnFeaturedYesNo() {
			$row = new ExampleRatingData;
			$_REQUEST['page'] = "http://localhost:8888/thebestwp/wp-admin/";
			$this->assertContains( 'Yes', $this->plugin->column_featured($row) );
		}
		function testColumnFeaturedContainsLink() {
			$row = new ExampleRatingData;
			$this->assertContains( '<a href', $this->plugin->column_featured($row) );
		}
		function testColumnFeaturedContainsCorrectAction() {
		$row = new ExampleRatingData;
		$this->assertContains( 'Unfeature', $this->plugin->column_featured($row) );
	}

}
?>