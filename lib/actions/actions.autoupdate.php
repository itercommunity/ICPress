<?php

    // Include WordPress
    require('../../../../../wp-load.php');
    define('WP_USE_THEMES', false);

    // Prevent access to users who are not editors
    if ( !current_user_can('edit_others_posts') && !is_admin() ) wp_die( __('Only editors can access this page through the admin panel.'), __('ICPress: Access Denied') );
    
    // Ignore user abort
    ignore_user_abort(true);
    set_time_limit(60*10); // ten minutes
    
    // Access WordPress db
    global $wpdb;
    
    // Include Request Functionality
    require("../request/rss.request.php");
    
    // Include Import and Sync Functions
    require("../import/import.functions.php");
    require("../import/sync.functions.php");
    
    // Get session ready
    if (!session_id()) { session_start(); }
    
    
?><!DOCTYPE html 
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    
    <head profile="http://www.w3.org/2005/11/profile">
        <title> Syncing </title>
        <script type="text/javascript" src="<?php echo ICPRESS_PLUGIN_URL; ?>js/jquery-1.5.2.min.js"></script>
        <script type="text/javascript" src="<?php echo ICPRESS_PLUGIN_URL; ?>js/jquery.livequery.min.js"></script>
    </head>
    
    <body><?php
    
    
    // START WITH ITEMS
    
    if ( isset($_GET['step']) && $_GET['step'] == "items")
    {
        $api_user_id = icp_get_api_user_id();
        
        if (isset($_SESSION['icp_session'][$api_user_id]['key']) && isset($_GET['key']) && $_SESSION['icp_session'][$api_user_id]['key'] == $_GET['key'])
        {
            // Get account
            $_SESSION['icp_session'][$api_user_id]['icp_account'] = icp_get_account($wpdb, $api_user_id);
            
            // Set current sync time
            icp_set_update_time( date('Y-m-d') );
            
            // GET ITEM COUNT AND LOCAL ITEMS
            $_SESSION['icp_session'][$api_user_id]['items']['icp_local_items'] = icp_get_local_items ($wpdb, $api_user_id);
            
            // Set up session item query vars
            $_SESSION['icp_session'][$api_user_id]['items']['icp_items_to_add'] = array();
            $_SESSION['icp_session'][$api_user_id]['items']['icp_items_to_update'] = array();
            $_SESSION['icp_session'][$api_user_id]['items']['query_total_items_to_add'] = 0;
            
            // SYNC ITEMS
            ?><script type="text/javascript">
            
            jQuery(document).ready(function()
            {
                function icp_get_items (icp_plugin_url, api_user_id, icp_key, icp_start)
                {
                    var zpXMLurl = icp_plugin_url + "lib/actions/actions.sync.php?api_user_id=" + api_user_id + "&key=" + icp_key + "&step=items&start=" + icp_start;
                    
                    jQuery.get( zpXMLurl, {}, function(xml)
                    {
                        var $result = jQuery("result", xml);
                        
                        if ($result.attr("success") == "true") // Move on to the next 50
                        {
                            icp_get_items (icp_plugin_url, api_user_id, icp_key, $result.attr("next"));
                        }
                        else if ($result.attr("success") == "next")
                        {
                            window.location = window.location.replace("step=items", "step=collections");
                        }
                        else // Show errors
                        {
                            alert( "Sorry, but there was a problem syncing items: " + jQuery("errors", xml).text() );
                        }
                    });
                }
                
                icp_get_items( <?php echo "'" . ICPRESS_PLUGIN_URL . "', '" . $api_user_id . "', '" . $_SESSION['icp_session'][$api_user_id]['key']; ?>', 0);
                
            });
            
            </script><?php
        }
        
        else /* key fails */ { exit ("key incorrect: " . $_GET['key'] . " vs " .$_SESSION['icp_session'][$api_user_id]['key'] ); }
        
    }
    
    
    // THEN COLLECTIONS
    
    else if ( isset($_GET['step']) && $_GET['step'] == "collections")
    {
        $api_user_id = icp_get_api_user_id();
        
        if (isset($_SESSION['icp_session'][$api_user_id]['key']) && isset($_GET['key']) && $_SESSION['icp_session'][$api_user_id]['key'] == $_GET['key'])
        {
            // GET LOCAL COLLECTIONS
            $_SESSION['icp_session'][$api_user_id]['collections']['icp_local_collections'] = icp_get_local_collections ($wpdb, $api_user_id);
            
            // Set up session item query vars
            $_SESSION['icp_session'][$api_user_id]['collections']['icp_collections_to_update'] = array();
            $_SESSION['icp_session'][$api_user_id]['collections']['icp_collections_to_add'] = array();
            $_SESSION['icp_session'][$api_user_id]['collections']['query_total_collections_to_add'] = 0;
            
            // SYNC COLLECTIONS
            ?><script type="text/javascript">
            
            jQuery(document).ready(function()
            {
                function icp_get_collections (icp_plugin_url, api_user_id, icp_key, icp_start)
                {
                    var zpXMLurl = icp_plugin_url + "lib/actions/actions.sync.php?api_user_id=" + api_user_id + "&key=" + icp_key + "&step=collections&start=" + icp_start;
                    
                    jQuery.get( zpXMLurl, {}, function(xml)
                    {
                        var $result = jQuery("result", xml);
                        
                        if ($result.attr("success") == "true") // Move on to the next 50
                        {
                            icp_get_collections (icp_plugin_url, api_user_id, icp_key, $result.attr("next"));
                        }
                        else if ($result.attr("success") == "next")
                        {
                            window.location = window.location.replace("step=collections", "step=tags");
                        }
                        else // Show errors
                        {
                            alert( "Sorry, but there was a problem syncing collections: " + jQuery("errors", xml).text() );
                        }
                    });
                }
                
                icp_get_collections( <?php echo "'" . ICPRESS_PLUGIN_URL . "', '" . $api_user_id . "', '" . $_SESSION['icp_session'][$api_user_id]['key']; ?>', 0);
                
            });
            
            </script><?php
        }
        else /* key fails */ { exit ("key incorrect "); }
    }
    
    
    // THEN TAGS
    
    else if ( isset($_GET['step']) && $_GET['step'] == "tags")
    {
        $api_user_id = icp_get_api_user_id();
        
        if (isset($_SESSION['icp_session'][$api_user_id]['key']) && isset($_GET['key']) && $_SESSION['icp_session'][$api_user_id]['key'] == $_GET['key'])
        {
            // GET LOCAL TAGS
            $_SESSION['icp_session'][$api_user_id]['tags']['icp_local_tags'] = icp_get_local_tags ($wpdb, $api_user_id);
            
            // Set up session item query vars
            $_SESSION['icp_session'][$api_user_id]['tags']['icp_tags_to_update'] = array();
            $_SESSION['icp_session'][$api_user_id]['tags']['icp_tags_to_add'] = array();
            $_SESSION['icp_session'][$api_user_id]['tags']['query_total_tags_to_add'] = 0;
            
            // SYNC TAGS
            ?><script type="text/javascript">
            
            jQuery(document).ready(function()
            {
                function icp_get_tags (icp_plugin_url, api_user_id, icp_key, icp_start)
                {
                    var zpXMLurl = icp_plugin_url + "lib/actions/actions.sync.php?api_user_id=" + api_user_id + "&key=" + icp_key + "&step=tags&start=" + icp_start;
                    
                    jQuery.get( zpXMLurl, {}, function(xml)
                    {
                        var $result = jQuery("result", xml);
                        
                        if ($result.attr("success") == "true") // Move on to the next 50
                        {
                            icp_get_tags (icp_plugin_url, api_user_id, icp_key, $result.attr("next"));
                        }
                        else if ($result.attr("success") == "next")
                        {
                            // Do nothing
                        }
                        else // Show errors
                        {
                            alert( "Sorry, but there was a problem syncing tags: " + jQuery("errors", xml).text() );
                        }
                    });
                }
                
                icp_get_tags( <?php echo "'" . ICPRESS_PLUGIN_URL . "', '" . $api_user_id . "', '" . $_SESSION['icp_session'][$api_user_id]['key']; ?>', 0);
                
            });
            
            </script><?php
        }
        
        else /* key fails */ { exit ("key incorrect "); }
        
    } ?>

    </body>
</html>