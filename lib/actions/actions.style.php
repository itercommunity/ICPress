<?php


    // Include WordPress
    require('../../../../../wp-load.php');
    define('WP_USE_THEMES', false);

    // Access WordPress db
    global $wpdb;
    
    // Include Request Functionality
    require("../request/rss.request.php");
    
    // Include Import and Sync Functions
    require("../import/import.functions.php");
    

    // Set up XML document
    $xml = "";
    
    

    /*
     
        AUTO-UPDATE
        
    */

    if (isset($_GET['update']))
    {
        // Set up error array
        $errors = array("api_user_id_blank"=>array(0,"<strong>User ID</strong> was left blank."),
                        "api_user_id_format"=>array(0,"<strong>User ID</strong> was formatted incorrectly."),
                        "style_blank"=>array(0,"<strong>Style</strong> was not set."),
                        "style_format"=>array(0,"<strong>Style</strong> was not formatted correctly."),
                        "items_blank"=>array(0,"<strong>Items</strong> were not set."),
                        "items_format"=>array(0,"<strong>Items</strong> were not formatted correctly.")
                        );
        
        
        // CHECK API USER ID
        
        if ($_GET['api_user_id'] != "")
            if (preg_match("/^[0-9]+$/", $_GET['api_user_id']) == 1)
                $api_user_id = htmlentities($_GET['api_user_id']);
            else
                $errors['api_user_id_format'][0] = 1;
        else
            $errors['api_user_id_blank'][0] = 1;
        
        // CHECK STYLE
        
        if ($_GET['style'] != "")
            if (preg_match("/^[a-zA-Z0-9_-]+$/", $_GET['style']) == 1)
                $style = htmlentities($_GET['style']);
            else
                $errors['style_format'][0] = 1;
        else
            $errors['style_blank'][0] = 1;
        
        // CHECK ITEMS
        
        if ($_GET['items'] != "")
            if (preg_match("/^[a-zA-Z0-9,]+$/", $_GET['items']) == 1)
                $items = htmlentities($_GET['items']);
            else
                $errors['items_format'][0] = 1;
        else
            $errors['items_blank'][0] = 1;
        
        
        // CHECK ERRORS
        
        $errorCheck = false;
        foreach ($errors as $field => $error) {
            if ($error[0] == 1) {
                $errorCheck = true;
                break;
            }
        }
        
        
        // IMPORT NEW STYLES
        
        if ($errorCheck == false)
        {
            $icp_items_current_style_proceed = false;
            
            // If style's already been changed, leave it
            $query_items = str_replace(",","','", $items);
            $icp_items_current_style = $wpdb->get_results("SELECT style FROM ".$wpdb->prefix."icpress_zoteroItems WHERE item_key IN ('".$query_items."');", OBJECT);
            
            foreach ($icp_items_current_style as $current_style)
                if ($current_style->style != $style)
                    $icp_items_current_style_proceed = true;
            
            if ($icp_items_current_style_proceed)
            {
                $icp_import_contents = new ICPressRequest();
                
                // Get account
                $icp_account = icp_get_account ($wpdb, $api_user_id);
                
                // Figure out whether account needs a key
                //$nokey = icp_get_account_haskey ($icp_account);
                
                $icp_import_url = "https://api.zotero.org/".$icp_account[0]->account_type."/".$icp_account[0]->api_user_id."/items?";
                if (is_null($icp_account[0]->public_key) === false && trim($icp_account[0]->public_key) != "") {
                    $icp_import_url .= "key=".$icp_account[0]->public_key."&";
                }
                $icp_import_url .= "format=atom&content=bib&style=".$style."&itemKey=".$items;
                
                // Read the external data
                $icp_xml = $icp_import_contents->get_request_contents( $icp_import_url, false );
                
                // Make it DOM-traversable 
                $doc_citations = new DOMDocument();
                $doc_citations->loadXML($icp_xml);
                
                $entries = $doc_citations->getElementsByTagName("entry");
                
                foreach ($entries as $entry)
                {
                    $item_key = $entry->getElementsByTagNameNS("http://zotero.org/ns/api", "key")->item(0)->nodeValue;
                    
                    $citation_content = "";
                    $citation_content_temp = new DOMDocument();
                    
                    foreach ($entry->getElementsByTagName("content") as $child)
                    {
                        foreach($child->childNodes as $child_content) {
                            $citation_content_temp->appendChild($citation_content_temp->importNode($child_content, true));
                            $citation_content = $citation_content_temp->saveHTML();
                        }
                    }
                    
                    // Update style
                    $wpdb->update( 
                        $wpdb->prefix.'icpress_zoteroItems', 
                        array( "style" => icp_db_prep($style), "citation" => icp_db_prep($citation_content) ),
                        array( 'item_key' => $item_key, 'api_user_id' => $icp_account[0]->api_user_id ), 
                        array( '%s', '%s' ),
                        array( '%s', '%s' ) 
                    );
                    
                    $xml .= "<item key=\"".$item_key."\">".htmlentities($citation_content)."</item>\n";
                    
                } // entry
                
                unset($icp_import_contents);
                unset($icp_import_url);
                unset($icp_xml);
                unset($doc_citations);
                unset($entries);
            }
            
            unset($icp_items_current_style);
            unset($icp_items_current_style_proceed);
            unset($query_items);
            
            // Display success XML
            $xml .= "<result success=\"true\" api_user_id=\"".$api_user_id."\" />\n";
            
            $wpdb->flush();
            unset($icp_account);
        }
        
        
        // DISPLAY ERRORS
        
        else
        {
            $xml .= "<result success=\"false\" />\n";
            $xml .= "<style>\n";
            $xml .= "<errors>\n";
            foreach ($errors as $field => $error)
                if ($error[0] == 1)
                    $xml .= $error[1]."\n";
            $xml .= "</errors>\n";
            $xml .= "</style>\n";
        }
    }
    
    
    
    /*
     
        DISPLAY XML
        
    */

    header('Content-Type: application/xml; charset=ISO-8859-1');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n";
    echo "<style>\n";
    echo $xml;
    echo "</style>";

?>