<?php
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}
/*
Plugin Name: Udemy Price Updater
Version: 0.1
Description: Updates the Prices of Udemy courses by reading prices from a txt file containing JSON data.
Author: Vijay Kumar
Author URI: http://betteru.ca/
Plugin URI: http://www.betteru.in/
*/

/* Version check */
global $wp_version;	
$exit_msg='Course Importer requires WordPress 2.5 or newer. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update!</a>';

if (version_compare($wp_version,"2.5","<"))
{
	exit ($exit_msg);
}

/*
* This hook calls the update_courses function when the plugin is activated.
*/
register_activation_hook( __FILE__, 'update_courses' );

function update_courses () {
	$courseArgs = array(
		'post_type'		   => 'course',	// We want to get all the course post types
		'author'	   	   => '1',		// The author is the user account that created that post
		'post_status'      => 'publish',// We want to get all the published courses
		'posts_per_page' 	   => -1,
		'suppress_filters' => true	
	);
	$postsArray = get_posts( $courseArgs ); 	
	
	/*These are the functions executed by this program:*/
	//updateUdemyCourses();
	updateUdemyCoursesUsingCourseIds();
	//connectToCoursesDatabase();
	//createCoursesAndProducts()
	//connectCourseToProduct($postsArray, $productsArray);
	//updateMethodSelectorMethod();
	
	/*The post variable contains the array that contains the values that will be updated
		$course_post = array(
			'ID'             =>  // Are you updating an existing post?
		 // 'post_content'   => $fullDescription, // The full text of the post.
		 // 'post_name'      => [ <string> ] // The name (slug) for your post
		 // 'post_title'     => $currentCourse->skdx_agld_course_title, // The title of your post.
		 // 'post_status'    => 'draft', // Default 'draft'.
		 // 'post_type'      => 'course', // Default 'post'.
		 // 'post_author'    => 16, // The user ID number of the author. Default is the current user ID.
		 // 'ping_status'    => 'closed', // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
		  //'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
		  //'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
		  //'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
		  //'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
		  //'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
		  //'guid'           => // Skip this and let Wordpress handle it, usually.
		  //'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
		  //'post_excerpt'   => $currentCourse->skdx_agld_short_description, // For all your post excerpt needs.
		  //'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
		  //'post_date_gmt'  => [ Y-m-d H:i:s ] // The time post was made, in GMT.
		  //'comment_status' => 'closed', // Default is the option 'default_comment_status', or 'closed'.
		  //'post_category'  => array(123), // Default empty.
		  //'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
		  //'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
		  //'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
			'course_product'	=> 123,
		);
		// Update the post into the database
		//$post_id = wp_insert_post( $post, $wp_error ); 
		wp_update_post($course_post);*/
}

function updateUdemyCoursesUsingCourseIds() {
	$idsFile = plugin_dir_path(__FILE__) . "udemy_skdx_course_ids.txt";
	$errorLogUdemyFileName = plugin_dir_path(__FILE__) . "devlog_udemyPriceUpdater.txt";	// The filename to which the error log will be written to for testing and debugging.
	$coursesPriceResetCounter = 0;
	$coursesNotResetCounter = 0;
	$emptyString = ""; // This is to remove the _sale_price value for Udemy courses.
	
	$fh = fopen($idsFile, 'r');
	$theData = fread($fh, filesize($idsFile));
	$allSkdxCourseIds = array();
	$allLines = explode("\n", $theData);
	foreach($allLines as $line){
	  $tmp = explode(" ", $line);
	  $allSkdxCourseIds[$tmp[0]] = $tmp[1];
	}
	
	$idsString = print_r($allSkdxCourseIds, true);
	error_log($idsString, 3, $errorLogUdemyFileName);	// Write the contents of the array to the error log for debugging purposes.	
	fclose($fh); // After the file has been read and its contents loaded to an array, close the file.
	
	/* 
		In this case the JSON data describes an array in which each element of the array in an object.
	*/
	$usdToRs = 66.84;	//This value needs to be updated before each time this plugin is run so that the most recent conversion rate can be used for the price.
	
	$udemyFileName = plugin_dir_path(__FILE__) . "udemy_all_course_prices.txt";	// The file from which the JSON data will be obtained.
	$errorLogUdemyFileName = plugin_dir_path(__FILE__) . "devlog_udemyPriceUpdater.txt";	// The filename to which the error log will be written to for testing and debugging.
	
	$udemyPriceFile = fopen($udemyFileName, "r") or die("Unable to open file!");	// If the JSON data file can't be opened then display the error message.
	$fileContents = fread($udemyPriceFile, filesize($udemyFileName));	// Read the contents of the Udemy JSON data file.
	$arrayOfJsonCourses = json_decode($fileContents, true); // Convert an array of objects into an array of arrays.
	$strOfPrices = print_r($arrayOfJsonCourses, true);  // Convert an array to string 
	error_log($strOfPrices, 3, $errorLogUdemyFileName); // See the contents of the string to verify them inside the devlog.
	fclose($udemyPriceFile);
	
	foreach($allSkdxCourseIds as $udemyId => $skdxId) {
		foreach($arrayOfJsonCourses as $jsonSingleCourse) {
			if($udemyId == $jsonSingleCourse['id']) {
				$skdxId = trim($skdxId);
				$jsonCoursePrice = $jsonSingleCourse['price']; //  Course price as obtained from the JSON data.
				$jsonCoursePrice = round(filter_var($jsonCoursePrice, FILTER_SANITIZE_NUMBER_INT) * $usdToRs); // Get the int value from the string and change the price in dollars to rupees.
				error_log("New Course Price that will replace the existing price:" . $jsonCoursePrice . PHP_EOL, 3, $errorLogUdemyFileName); // The new price that will replace the existing price 
				error_log("Udemy Course id:" . $udemyId . " Skillsdox Product Id:" . $skdxId . " Json data's course id:" . $jsonSingleCourse['id'] . PHP_EOL, 3, $errorLogUdemyFileName); // enter match in log file.
				error_log("New Course Price:" . $jsonCoursePrice . PHP_EOL . PHP_EOL, 3, $errorLogUdemyFileName); // The new price that will replace the existing price 
				update_post_meta($skdxId, '_regular_price', $jsonCoursePrice); // post_id, meta_key, new value
				update_post_meta($skdxId, '_price', $jsonCoursePrice); // post_id, meta_key, new value	// Both the _regular_price and the _price are set to the same value in the database.
				update_post_meta($skdxId, '_sale_price', $emptyString); // post_id, meta_key, new value // This is to set the sale price of udemy courses to an empty string.
				$coursesPriceResetCounter++; // Each time we are in this if statement we want to increase the value. 
			} // for if statement: the json data == skdx data
			else {
				//$coursesNotResetCounter++; // Use it for debugging. 
			}
		} // inner foreach
	} // outer foreach
	error_log("Total number of course prices reset are:" . $coursesPriceResetCounter . PHP_EOL, 3, $errorLogUdemyFileName);
	//error_log("Total number of course prices that COULD NOT be reset are:" . $coursesNotResetCounter . PHP_EOL, 3, $errorLogUdemyFileName);		
} // closing brace for function 

/*This function reads files inside a folder that contains JSON data. It then parses that data and updates any Udemy courses that have the same title as obtained from the JSON data*/
function updateUdemyCourses() {
	/* 
		In this case the JSON data describes an array in which each element of the array in an object.
	*/
	$usdToRs = 66.84;	//This value needs to be updated before each time this plugin is run so that the most recent conversion rate can be used for the price.
	
	$udemyFileName = plugin_dir_path(__FILE__) . "udemy_all_course_prices.txt";	// The file from which the JSON data will be obtained.
	$errorLogUdemyFileName = plugin_dir_path(__FILE__) . "devlog_udemyPriceUpdater.txt";	// The filename to which the error log will be written to for testing and debugging.
	
	$udemyPriceFile = fopen($udemyFileName, "r") or die("Unable to open file!");	// If the JSON data file can't be opened then display the error message.
	$fileContents = fread($udemyPriceFile, filesize($udemyFileName));	// Read the contents of the Udemy JSON data file.
	$arrayOfJsonCourses = json_decode($fileContents, true); // Convert an array of objects into an array of arrays.
	$strOfPrices = print_r($arrayOfJsonCourses, true);  // Convert an array to string 
	error_log($strOfPrices, 3, $errorLogUdemyFileName); // See the contents of the string to verify them inside the devlog.
	
	$udemyCourseArgs = array(
		'post_type'		   => 'product',	// We want to get all the course post types
		'author'	   	   => '22',		// The author is the user account that created that post. For Udemy the author id is 22. 
		'post_status'      => array('publish', 'pending', 'draft', 'private'),// We want to get these different types of posts.
		'posts_per_page'   => -1,
		'suppress_filters' => true	
	);
	$udemyPostsArray = get_posts( $udemyCourseArgs ); 	
	
	$coursesPriceResetCounter = 0;
	$coursesNotResetCounter = 0;
	
	foreach($arrayOfJsonCourses as $jsonSingleCourse) {
		$jsonCourseTitle = $jsonSingleCourse['title']; // Course title as obtained from the JSON data.
		$jsonCoursePrice = $jsonSingleCourse['price']; //  Course price as obtained from the JSON data.
		$jsonCoursePrice = round(filter_var($jsonCoursePrice, FILTER_SANITIZE_NUMBER_INT) * $usdToRs); // Get the int value from the string and change the price in dollars to rupees.
		error_log("Current Udemy Course ID is:" . $jsonSingleCourse['id'] . PHP_EOL, 3, $errorLogUdemyFileName);
		if(isset($jsonCourseTitle)) { // Run the 2nd foreach loop only if the title has been set. This is because some REST API requests are not returning the course information. So they need to be entered manually.
			foreach($udemyPostsArray as $udemyProduct) {
				$udemyProductId = $udemyProduct->ID; // Save this value to a variavle because that variable will be used again and again.
				$emptyString = ""; // This is to remove the _sale_price value for Udemy courses.
				if(strcmp($udemyProduct->post_title, $jsonCourseTitle) == 0) {
					error_log("This udemy title: " . $jsonCourseTitle . " was MATCHED with this skdx title:" . $udemyProduct->post_title . PHP_EOL, 3, $errorLogUdemyFileName);					
					update_post_meta($udemyProductId, '_regular_price', $jsonCoursePrice); // post_id, meta_key, new value
					update_post_meta($udemyProductId, '_price', $jsonCoursePrice); // post_id, meta_key, new value	// Both the _regular_price and the _price are set to the same value in the database.
					update_post_meta($udemyProductId, '_sale_price', $emptyString); // post_id, meta_key, new value // This is to set the sale price of udemy courses to an empty string.
					$coursesPriceResetCounter++; // Each time we are in this if statement we want to increase the value. 
				} // if(strcmp())
				else {
					/*
						The commented statements in this block can be used for debugging.
					*/
					//error_log("This udemy title: " . $jsonCourseTitle . " Didn't match this skdx title:" . $udemyProduct->post_title . PHP_EOL, 3, $errorLogUdemyFileName);
					//$coursesNotResetCounter++; // Each time a product price is not updated, increment this.
				}
			} // 2nd foreach
		} // if isset()
		else {
			$coursesNotResetCounter++; // Each time a product price is not updated, increment this.
		}
	} // 1st foreach
	error_log("Total number of course prices reset are:" . $coursesPriceResetCounter . PHP_EOL, 3, $errorLogUdemyFileName);
	error_log("Total number of course prices that COULD NOT be reset are:" . $coursesNotResetCounter . PHP_EOL, 3, $errorLogUdemyFileName);	
	//error_log($strOfPrices, 3, $errorLogUdemyFileName);
	//error_log($fileContents, 3, $errorLogUdemyFileName);	// Write the contents of the JSON data file to the error log.
	//error_log("Test String 2", 3, $errorLogUdemyFileName);	// Write a text string at the botom of the error log to check if it can be written to.	
	fclose($udemyPriceFile);	// Close the Udemy JSON data file.
}

function connectToCoursesDatabase() {
	/* // mysql_connect is deprecated so mysqli_connect has to be used
	$dbh=mysql_connect ("173.239.142.82:3306", "vjks", "JV8^?FVc,/a})f//") or die ('I cannot connect to the database because: ' . mysql_error());
	error_log(mysql_error, 3, plugin_dir_path( __FILE__ ). "devlog.txt");
	
	mysql_select_db ("mydb_1stFeb");
	*/
	$link = mysqli_connect("127.0.0.1", "sandboxsos", "KiraThPnvB5nqzYCovNt", "wp_sandboxsos");

	if (!$link) {
		$errorMessage = "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		$errorNumber = "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		echo "Error: Unable to connect to MySQL." . PHP_EOL;
		echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		error_log($errorMessage . $errorNumber, 3, plugin_dir_path( __FILE__ ). "devlog_connectdb.txt");
		exit;
	}
	$connectionMessage = "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
	$hostInfo = "Host information: " . mysqli_get_host_info($link) . PHP_EOL;
	echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
	echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;
	error_log($connectionMessage . $hostInfo, 3, plugin_dir_path( __FILE__ ). "devlog_connectdb.txt");
	
	mysqli_close($link);
}
/* This function is used to link the course with the product */
function connectCourseToProduct($postsArray, $productsArray) {		
		$testStr = "Hello World Test 2\n";
		//error_log($testStr, 3, plugin_dir_path( __FILE__ ). "devlog.txt");
		
		$testPosts = print_r($postsArray, true);
		//error_log($testPosts, 3, plugin_dir_path( __FILE__ ). "devlog.txt");
		
		//$testProducts = print_r($productsArray, true);
		//error_log($testProducts, 3, plugin_dir_path( __FILE__ ). "devlog.txt");
		//$test = print_r(if($postsArray));
		//error_log($test, 3, plugin_dir_path( __FILE__ ). "devlog.txt");
		if($postsArray) {
			foreach($postsArray as $coursePost) {
				//setup_postdata($coursePost);
				$courseId = $coursePost->ID;	// For the current post, get the ID.
				//error_log($courseId . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
				$courseTitle = $coursePost->post_title;	// Assign the title of the current post to the courseTitle variable
				error_log("\nThe course title is: " . $courseTitle . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
				reset( $productsArray ); // Reset the array before running it again
				foreach($productsArray as $productPost) {	// For each product post inside the products_array
					//setup_postdata( $productPost );
					$productTitle = $productPost->post_title;	// Get the post_title for the existing product
					$productId = $productPost->ID;
					//error_log($productTitle . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
					if(strcmp($courseTitle, $productTitle) == 0) {
						error_log("A TITLE MATCH WAS MADE: " . "Course Title is:" . $courseTitle . " " . "Product Title is:" . $productTitle . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
						$existingCourseProductValue = get_post_meta($courseId, 'course_product', true);
						error_log("The course id value is:" . $courseId . " Existing course_product value is:" . $existingCourseProductValue . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
						if(is_null($existingCourseProductValue)) {
							update_post_meta($courseId, 'course_product', $productId);
							$newCourseProductValue = get_post_meta($courseId, 'course_product', true);
							error_log("course_product is now set to product id. Existing course_product is: " . $newCourseProductValue . " Product id is: " . $productId . "\n\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
							break;
						}
						else if($existingCourseProductValue == $productId) {
							error_log("course_product is already set to the product id value. course_product is: " . $existingCourseProductValue . " product id is: " . $productId . "\n\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
							break;
						}
						else {
							update_post_meta($courseId, 'course_product', $productId);
							$newCourseProductValue = get_post_meta($courseId, 'course_product', true);
							error_log("The course probably did not have a course_product key. course_product is now set to product id. Existing course_product is: " . $newCourseProductValue . " Product id is: " . $productId . "\n\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
							break;
						}
					}
					else {
						error_log("The Course title: " . $courseTitle . " did not match the product title: " . $productTitle . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
					}
				}
			}
		}
}
/* This function is used to update the method selector method */
function updateMethodSelectorMethod() {
	$udemyProductCounter = 0;
	$bsevProductCounter = 0;
	$acadgildProductCounter = 0;
	$edxProductCounter = 0;
	// For Udemy Products
	$udemyProductArgs = array(
		'post_type'			=>  'product',
		'author'			=>	'22', 
		'post_status'		=>  'publish',
		'posts_per_page' 	   => -1, 
		'suppress_filters'	=> true
	);
	$udemyProductsArray = get_posts( $udemyProductArgs );
	
	foreach($udemyProductsArray as $productPost) {	// For each product post inside the products_array
		$productId = $productPost->ID;
		update_post_meta($productId, 'method_selector_method', 'default');
		error_log("The product title: " . $productPost->post_title . " has been updated. Current count is:" . ++$udemyProductCounter . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
	}
	unset($udemyProductsArray); // We have used this variable. Now the memory associated with it should be released;
/*	// For Acadgild Products
	$acadgildProductArgs = array(
		'post_type'			=>  'product',
		'author'			=>	'238', 
		'post_status'		=>  'publish',
		'posts_per_page' 	   => -1, 
		'suppress_filters'	=> true		
	);
	$acadgildProductsArray = get_posts( $acadgildProductArgs );
	
	foreach($acadgildProductsArray as $productPost) {	// For each product post inside the products_array
		$productId = $productPost->ID;
		update_post_meta($productId, 'method_selector_method', 'default');
		error_log("The product title: " . $productPost->post_title . " has been updated. Current count is:" . ++$acadgildProductCounter . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
	}
	unset($acadgildProductsArray); // We have used this variable. Now the memory associated with it should be released;	
	// 	For edX Products
	$edxProductArgs = array(
		'post_type'			=>  'product',
		'author'			=>	'', 
		'post_status'		=>  'publish',
		'posts_per_page' 	   => -1, 
		'suppress_filters'	=> true		
	);
	$edxProductsArray = get_posts( $edxProductArgs );
	
	foreach($edxProductsArray as $productPost) {	// For each product post inside the products_array
		$productId = $productPost->ID;
		update_post_meta($productId, 'method_selector_method', 'default');
		error_log("The product title: " . $productPost->post_title . " has been updated. Current count is:" . ++$edxProductCounter . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
	}
	unset($edxProductsArray); // We have used this variable. Now the memory associated with it should be released;
	// For BSEV Products
	$bsevProductArgs = array(
		'post_type'			=>  'product',
		'author'			=>	'', 
		'post_status'		=>  'publish',
		'posts_per_page' 	   => -1, 
		'suppress_filters'	=> true		
	);
	$bsevProductsArray = get_posts( $bsevProductArgs );
	
	foreach($bsevProductsArray as $productPost) {	// For each product post inside the products_array
		$productId = $productPost->ID;
		update_post_meta($productId, 'method_selector_method', 'default');
		error_log("The product title: " . $productPost->post_title . " has been updated. Current count is:" . ++$bsevProductCounter . "\n", 3, plugin_dir_path( __FILE__ ). "devlog.txt");
	}
	unset($bsevProductsArray); // We have used this variable. Now the memory associated with it should be released;
	*/
}
/* This function concatenates the values that go into the post description field. */
function concatDescriptionFields($currentCourse) {	
	$fullDescription = "";	
	$fullDescription .= $currentCourse->skdx_agld_url . "\n";	
	$fullDescription .= $currentCourse->skdx_agld_full_description . "\n";
	$fullDescription .= "<img src=\"" . $currentCourse->skdx_agld_image_url . "\"/>\n\n";
	$fullDescription .= $currentCourse->skdx_agld_requirements . "\n\n";
	$fullDescription .= $currentCourse->skdx_agld_module_lesson . "\n\n";
	$fullDescription .= $currentCourse->skdx_agld_module_lesson_description . "\n\n";

	$fullDescription .=  $currentCourse->skdx_agld_price . "\n\n";
	return $fullDescription;
}
/*
function getLessons($currentCoursesLessons) {
	$allLessons = "";
	
	foreach($currentCoursesLessons->skdx_lesson as $currentLesson) {
		$allLessons .= $currentLesson->skdx_lesson . "\n";
	}
	
	return $allLessons;
}*/
?>