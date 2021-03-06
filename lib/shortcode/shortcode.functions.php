<?php

    
    
    // GET YEAR
    // Used by: In-Text Shortcode, In-Text Bibliography Shortcode
    function icp_get_year($date, $yesnd = false)
    {
		$date_return = false;
		
		preg_match_all( '/(\d{4})/', $date, $matches );
		
		if (is_null($matches[0][0]))
			if ( $yesnd === true )
				$date_return = "n.d.";
			else
				$date_return = "";
		else
			$date_return = $matches[0][0];
		
		return $date_return;
    }
	
	
	// GET DATE
	// Used by: n/a
	function icp_get_date($date)
	{
		$year = icp_get_year($date); // 4 digits
		
		preg_match_all( '/(\w)/', $date, $matches );
		//var_dump($matches);
		
		$month = date_parse( $matches[0][0] );
		
		//var_dump($date['month']);
		
		//var_dump($month);
	}
    
    
    
    // SUBVAL SORT
    // Used by: Bibliography Shortcode, In-Text Bibliography Shortcode
    function subval_sort($item_arr, $sortby, $order)
    {
		// Format sort order
		if ( strtolower($order) == "desc" ) $order = SORT_DESC; else $order = SORT_ASC;
		
		// Author or date
		if ( $sortby == "author" || $sortby == "date" )
		{
			foreach ($item_arr as $key => $val)
			{
				$author[$key] = $val["author"];
				
				if ( isset( $val["zpdate"] ) )
					$date[$key] = icp_date_format($val["zpdate"]);
				else
					$date[$key] = icp_date_format($val["date"]);
			}
		}
		
		// Title
		else if ( $sortby == "title" )
		{
			foreach ($item_arr as $key => $val)
			{
				$title[$key] = $val["title"];
				$author[$key] = $val["author"];
			}
		}
		
		if ( $sortby == "author" && isset($author) && is_array($author) ) array_multisort( $author, $order, $date, $order, $item_arr );
		else if ( $sortby == "date" && isset($date) && is_array($date) ) array_multisort( $date, $order, $author, $order, $item_arr );
		else if ( $sortby == "title" && isset($title) && is_array($title) ) array_multisort( $title, $order, $author, $order, $item_arr );
		
		return $item_arr;
    }
	
	
	/**
	 * Returns the date in a standard format: yyyy-mm-dd.
	 * 
	 * Can read the following:
	 *  - yyyy/mm/dd, mm/dd/yyyy
	 *  - the dash equivalents of the above
	 *  - mmmm dd, yyyy
	 *  - yyyy mmmm, yyyy mmm (and the reverse)
	 *
	 * Used by:    subval_sort
	 *
	 * @param     string     $date          the date to format
	 * 
	 * @return     string     the formatted date, or the original if formatting fails
	 */
	function icp_date_format ($date)
	{
		// Set up search lists
		$list_month_long = array ( "01" => "January", "02" => "February", "03" => "March", "04" => "April", "05" => "May", "06" => "June", "07" => "July", "08" => "August", "09" => "September", "10" => "October", "11" => "November", "12" => "December" );
		$list_month_short = array ( "01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Aug", "09" => "Sept", "10" => "Oct", "11" => "Nov", "12" => "Dec" );
		
		
		// If it's already formatted with a dash or forward slash
		if ( strpos( $date, "-" ) !== false || strpos( $date, "/" ) !== false )
		{
			$temp = preg_split( "/-|\//", $date );
			
			// If year is last, switch it with first
			if ( strlen( $temp[0] ) != 4 )
			{
				// Just month and year
				if ( count( $temp ) == 2 ) 
					$date_formatted = array(
						"year" => $temp[1],
						"month" => $temp[0],
						"day" => false
					);
				// Assuming mm dd yyyy
				else 
					$date_formatted = array(
						"year" => $temp[2],
						"month" => $temp[0],
						"day" => $temp[1]
					);
			}
			else // Year is first
			{
				$date_formatted = array(
					"year" => $temp[0],
					"month" => $temp[1],
					"day" => $temp[2]
				);
			}
		}
		
		// If it's already formatted in mmmm dd, yyyy form
		else if ( strpos( $date, "," ) )
		{
			$date = trim( str_replace( ", ", ",", $date ) );
			$temp = preg_split( "/,| /", $date );
			
			// Convert month
			$month = array_search( $temp[0], $list_month_long );
			if ( !$month ) $month = array_search( $temp[0], $list_month_short );
			
			$date_formatted = array(
				"year" => $temp[2],
				"month" => $month,
				"day" => $temp[1]
			);
		}
		// Check for full names
		else
		{
			$date = trim( str_replace( "  ", "-", $date ) );
			$temp = explode ( " ", $date );
			
			// If there's at least two parts to the date
			if ( count( $temp) > 0 )
			{
				// Check if name is first
				if ( !is_numeric( $temp[0] ) )
				{
					if ( in_array( $temp[0], $list_month_long ) )
						$date_formatted = array(
							"year" => $temp[1],
							"month" => array_search( $temp[0], $list_month_long ),
							"day" => false
						);
					else if ( in_array( $temp[0], $list_month_short ) )
						$date_formatted = array(
							"year" => $temp[1],
							"month" => array_search( $temp[0], $list_month_short ),
							"day" => false
						);
					else // Not a recognizable month word
						$date_formatted = array(
							"year" => $temp[0], // $temp[1]
							"month" => false,
							"day" => false
						);
				}
				// Otherwise, check if name is last
				else
				{
					if ( count($temp) > 1 )
					{
						if ( in_array( $temp[1], $list_month_long ) )
							$date_formatted = array(
								"year" => $temp[0],
								"month" => array_search( $temp[1], $list_month_long ),
								"day" => false
							);
						else if ( in_array( $temp[1], $list_month_short ) )
							$date_formatted = array(
								"year" => $temp[0],
								"month" => array_search( $temp[1], $list_month_short ),
								"day" => false
							);
						else // Not a recognizable month word
							$date_formatted = array(
								"year" => $temp[0],
								"month" => false,
								"day" => false
							);
					}
					else // Only one part in the array
					{
						$date_formatted = array(
							"year" => $temp[0],
							"month" => false,
							"day" => false
						);
					}
				}
			}
			
			// Otherwise, assume year
			else
			{
				$date_formatted = array(
					"year" => $temp[0],
					"month" => false,
					"day" => false
				);
			}
		}
		
		// Format date in standard form: yyyy-mm-dd
		$date_formatted = implode( "-", array_filter( $date_formatted ) );
		
		//var_dump($date, $date_formatted, "<br /><br />");
		
		if ( !isset($date_formatted) ) $date_formatted = $date;
		
		return $date_formatted;
	}
    
    
    
	/**
	 * Returns HTML-formatted subcollections for parent collection.
	 *
	 * Used by:    shortcode.php
	 *
	 * @param     resource	$wpdb				the WP db resource link
	 * @param     string		$api_user_id		the Zotero API user ID
	 * @param     string		$parent				the parent collection
	 * @param     string		$sortby				what to sort by
	 * @param     string		$order				the order of the sort, e.g. asc, desc
	 * @param     string		$link					whether to add the URL
	 * 
	 * @return     string          the HTML-formatted subcollections
	 */
    function icp_get_subcollections ($wpdb, $api_user_id, $parent, $sortby, $order, $link=false)
    {
		$icp_query = "SELECT ".$wpdb->prefix."icpress_zoteroCollections.* FROM ".$wpdb->prefix."icpress_zoteroCollections";
		$icp_query .= " WHERE api_user_id='".$api_user_id."' AND parent = '".$parent."' ";
		
		// Sort by and sort direction
		if ($sortby)
		{
			if ($sortby == "default") $sortby = "retrieved";
			else if ($sortby == "date" || $sortby == "author") continue;
			
			$icp_query .= " ORDER BY ".$sortby." " . $order;
		}
		
		$icp_results = $wpdb->get_results($icp_query, OBJECT);
		
		$icp_output = "";
		
		//$icp_output = "<li class='icp-NestedCollection'><ul>\n";
		$icp_output .= "<ul class='icp-NestedCollection'>\n";
		
		foreach ($icp_results as $icp_collection)
		{
			$icp_output .= "<li>";
			if ($link == "yes")
			{
				$icp_output .= "<a class='icp-CollectionLink' title='" . $icp_collection->title . "' href='" . $_SERVER["REQUEST_URI"];
				if ( strpos($_SERVER["REQUEST_URI"], "?") === false ) { $icp_output .= "?"; } else { $icp_output .= "&"; }
				$icp_output .= "zpcollection=" . $icp_collection->item_key . "'>";
			}
			$icp_output .= $icp_collection->title;
			if ($link == "yes") { $icp_output .= "</a>"; }
			$icp_output .= "</li>\n";
			
			if ($icp_collection->numCollections > 0)
			$icp_output .= icp_get_subcollections($wpdb, $api_user_id, $icp_collection->item_key, $sortby, $order, $link);
		}
		
		//$icp_output .= "</ul></li>\n";
		$icp_output .= "</ul>\n";
		
		return $icp_output;
    }
    
    
    
?>