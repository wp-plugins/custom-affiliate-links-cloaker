<?php
/*
Plugin Name:   Custom Affiliate Links Cloaker
Plugin URI: http://wordpress.org/extend/plugins/custom-affiliate-links-cloaker/
Description:  This plugin gathers link information via web service and cloaks affiliate links on page
Version: 1.4.2.1
Author: Ahmad Alinat
Author URI: http://wordpress.org
License: Free
*/

//plugin text domain

ob_start();

define("AFF_CLOACKER_TEXT_DOMAIN","aff_cloacker_text_domain");
load_plugin_textdomain( AFF_CLOACKER_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );


require_once("affliate-cloacker-class.php");

//Wp_Rewrite for domain.com/out/domain.com shortcut
	add_filter( 'rewrite_rules_array', array('wp_affliate_cloacker','my_insert_rewrite_rules' )); 
	add_filter( 'query_vars', array('wp_affliate_cloacker','my_insert_query_vars') );
	add_action( 'wp_loaded', array('wp_affliate_cloacker','my_flush_rules') );

//admin menu
	add_action('admin_menu', array('wp_affliate_cloacker','settings_menu'));

//redirect
	add_action("wp_head",array("wp_affliate_cloacker","rewrite_redirect"));

// sync jobs
	add_action('wp_affliate_cron_event', array('wp_affliate_cloacker','do_cron'));

/**
 * filters
 */
if (get_option("aff_cloacker_use_in_content"))
	add_filter( 'the_content', 	array('wp_affliate_cloacker','the_content') ) ;
	add_filter( 'the_meta', 	array('wp_affliate_cloacker','the_content') ) ;

if (get_option("aff_cloacker_use_in_rss")):
	add_filter( 'the_content_rss', array('wp_affliate_cloacker','the_content') ) ;
	add_filter( 'the_content_feed', array('wp_affliate_cloacker','the_content') ) ;
	add_filter( 'the_excerpt_rss', array('wp_affliate_cloacker','the_content') ) ;
endif;

if (get_option("aff_cloacker_use_in_excerpt"))
	add_filter( 'the_excerpt', array('wp_affliate_cloacker','the_content') ) ;

if (get_option("aff_cloacker_use_in_widget"))
	add_filter( 'widget_text', array('wp_affliate_cloacker','the_content') ) ;


register_activation_hook(__FILE__,'wp_affliate_cloacker_install'); 


register_deactivation_hook( __FILE__, 'wp_affliate_cloacker_remove');


function wp_affliate_cloacker_install()
{
	add_option("aff_cloacker_cron_last_run",time());
	add_option("aff_cloacker_webservice_url","");
	add_option("aff_cloacker_cron_interval","1 day");
	
	//where to use
	add_option("aff_cloacker_use_in_rss","1");
	add_option("aff_cloacker_use_in_content","1");
	add_option("aff_cloacker_use_in_excerpt","1");
	add_option("aff_cloacker_use_in_widget","1");
	
	
	$table = wp_affliate_cloacker::get_table();
	
	$sql = "
		CREATE TABLE IF NOT EXISTS {$table} (
		 id int(11) NOT NULL AUTO_INCREMENT,
		 OriginalUrl varchar(500) COLLATE utf8_unicode_ci NOT NULL,
		 NewUrl varchar(500) COLLATE utf8_unicode_ci NOT NULL,
		 priority decimal(10,2) NOT NULL,
		 PRIMARY KEY (id)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		";
		
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	// add new rule to robots.txt
	// Disallow: /out/
		
	// $filename = 'robots.txt';
	// $robotstxt = file_get_contents($filename);
	// file_put_contents($filename, $robotstxt."\n".'Disallow: /out/');

}
	
	
function wp_affliate_cloacker_remove()
{
	
	delete_option("aff_cloacker_cron_last_run");
	delete_option("aff_cloacker_webservice_url");
	delete_option("aff_cloacker_cron_interval");
	delete_option("aff_cloacker_use_in_rss");
	delete_option("aff_cloacker_use_in_content");
	delete_option("aff_cloacker_use_in_excerpt");
	delete_option("aff_cloacker_use_in_widget");
	
	wp_clear_scheduled_hook('wp_affliate_cron_event');
	
	// remove row from robots.txt
	// Disallow: /out/	
}


function wp_affliate_five_minutes( $param ) 
{
	
	return array( 
				'five_minutes' => array(
										'interval' => 300, // seconds
										'display' => __( 'every 5 minutes' )
										) 
				);
}


add_filter( 'cron_schedules',  'wp_affliate_five_minutes'  );

if ( !wp_next_scheduled('wp_affliate_cron_event') ) {

	wp_schedule_event(time(), 'five_minutes', 'wp_affliate_cron_event' ); 
}