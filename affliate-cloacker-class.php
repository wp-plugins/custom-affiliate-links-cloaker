<?php

if (!class_exists("wp_affliate_cloacker")):
	
	class wp_affliate_cloacker
	{
		const NO_CHANGE_CLASS = "noChange";
		
		static function get_table()
		{
			global $wpdb;
			return $wpdb->prefix."affliate_url_cloacker";
		}
		static function settings_menu()
		{
			$p = add_options_page(__("Affliate Cloacker Admin",AFF_CLOACKER_TEXT_DOMAIN),
								  __("Affliate Cloacker Admin",AFF_CLOACKER_TEXT_DOMAIN), 
								  'administrator', __FILE__, array('wp_affliate_cloacker','settings_page'));
			
			 add_action('admin_print_styles-' . $p, array('wp_affliate_cloacker','print_scripts'));
		}
		static function settings_page()
		{
			require_once("views/settings.php");
		}
		
		static function print_scripts()
		{
		
			wp_enqueue_style('wp_url_affliate',plugins_url("/views/plugin.css",__FILE__));	
		}
		/**
		 * 
		 * get data from webservice using wp_http class
		 * @param unknown_type $print
		 */
		static function get_webservice_data($print = true)
		{
			$sync_url = get_option("aff_cloacker_webservice_url");
			
			$data = wp_remote_get($sync_url);
			$data = $data['body'];
			
			if(!$data or trim($data) == '')
			{
				
				// try alternative method, ie. loopia.se blocks file_get_contents command
				
				$data = wp_affliate_cloacker::alternative_method_get_webservice_data($sync_url);
			}
			
			if (!$data && $print):
			
			echo ("<p>".__("could not fetch webservice url",AFF_CLOACKER_TEXT_DOMAIN)."</p>");
			return false;
			endif;
			
			$data = json_decode($data,true);
			
			if (!is_array($data) && $print):
			
			echo ("<p>".__("could not fetch webservice data",AFF_CLOACKER_TEXT_DOMAIN)."</p>");
			return false;
			endif;
			
			return $data;
		}
		
		static function alternative_method_get_webservice_data($url)
		{
			
			if (preg_match('/^([a-z]+):\/\/([a-z0-9-.]+)(\/.*$)/i',
					$url, $matches)) {
				$protocol = strtolower($matches[1]);
				$host = $matches[2];
				$path = $matches[3];
			} else {
				// Bad url-format
				return FALSE;
			}
			
			if ($protocol == "http") {
				$socket = fsockopen($host, 80);
			} else {
				// Bad protocol
				return FALSE;
			}
			
			if (!$socket) {
				// Error creating socket
				return FALSE;
			}
			
			$request = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";
			$len_written = fwrite($socket, $request);
			
			if ($len_written === FALSE || $len_written != strlen($request)) {
				// Error sending request
				return FALSE;
			}
			
			$response = "";
			while (!feof($socket) &&
					($buf = fread($socket, 4096)) !== FALSE) {
				$response .= $buf;
			}
			
			if ($buf === FALSE) {
				// Error reading response
				return FALSE;
			}
			
			$end_of_header = strpos($response, "\r\n\r\n");
			return substr($response, $end_of_header + 4);
		}
		/*
		 * clear table
		 */
		static function truncate_db()
		{
			
			$table = wp_affliate_cloacker::get_table();
			global $wpdb;
			
			return $wpdb->query("truncate table {$table}");
		}
		static function count_affliate_table()
		{
			
			$table = wp_affliate_cloacker::get_table();
			global $wpdb;
			
			return $wpdb->get_var("select count(*) from {$table}");
			
		}
		static function do_sync($print = true)
		{
			
			require_once(trailingslashit(ABSPATH) ."wp-admin/includes/misc.php");
			
			$links = wp_affliate_cloacker::get_webservice_data();
			
			if (!$links)
			return false;
			
			foreach ($links as $link):
			
				$OriginalUrl = parse_url($link['OriginalUrl']);
				$OriginalUrl = str_replace('www.', '', $OriginalUrl['host']);
				
				$NewUrl = esc_url_raw($link['NewUrl'] , array('http', 'https'));
				
				$Priority = floatval($link['Priority']);
				
				$result = wp_affliate_cloacker::insert_to_table($OriginalUrl,
																$NewUrl, 
																$Priority);
				 
				if ($print):
					
					if ($result)
				 		echo "<p>".__("url '{$OriginalUrl}' inserted to the database",AFF_CLOACKER_TEXT_DOMAIN)."</p>";
				 	else
				 		echo "<p>".__("url '{$OriginalUrl}' <b> could not be </b> inserted to the database",AFF_CLOACKER_TEXT_DOMAIN)."</p>"; 
			 	endif;
			endforeach;
			
			$date = time();
			
			update_option("aff_cloacker_cron_last_run",$date);
		}
		static function to_date($timestamp)
		{
			
			return date(get_option('date_format')." / ".get_option("time_format"),$timestamp);
		}
		
		static function insert_to_table($original,$new,$priority)
		{
			
			global $wpdb;
			$table = wp_affliate_cloacker::get_table();
			
			if (wp_affliate_cloacker::is_duplicate_entry($original, $new, $priority))
			return false;
			
			if (wp_affliate_cloacker::is_empty_details($original, $new, $priority))
			return false;
			
			
			return $wpdb->insert($table,array('OriginalUrl'=>$original,'NewUrl'=>$new,'priority'=>$priority));
			
		}
		static function is_duplicate_entry($original,$new,$priority)
		{
			
			global $wpdb;
			
			$table = wp_affliate_cloacker::get_table();
			
			$q = $wpdb->prepare("select * from {$table} where OriginalUrl= %s and NewUrl = %s and priority = %s",
			$original,$new,$priority);
	
			
			$result = $wpdb->get_row($q,ARRAY_A );
			
			return is_array($result) && $result['id'] > 0;
			
		}
		static function rewrite_redirect()
		{
			
			$url =  get_query_var("url");
		
			if (strlen($url)>0):
				$newurl = wp_affliate_cloacker::get_highest_priority_url($url);
			
			if ($newurl)
				wp_redirect($newurl);
			else 
				_e("could not get url",AFF_CLOACKER_TEXT_DOMAIN);
				exit;
			endif;
		
		}
		static function get_highest_priority_url($url)
		{
			global $wpdb;
	
			$table = wp_affliate_cloacker::get_table();
			
			$row = $wpdb->get_row($wpdb->prepare("select * from {$table} where OriginalUrl = %s order by priority desc",$url));
		
			$newurl = $row->NewUrl;
			
			if (strlen($newurl) >0)
			return $newurl;
			else
			return false;
		}
		static function is_empty_details($original,$new,$priority)
		{
			return (strlen($original)==0 || strlen($new)==0 || floatval($priority) ==0 );
		}
		static function is_no_change_url($url)
		{
			
			$cls = $url->getAttribute("class");
			$cls = explode(" ",$cls);
			
			return in_array(wp_affliate_cloacker::NO_CHANGE_CLASS, $cls);
		}
		static function set_redirect_url($home = null, $url)
		{
			
			if(!$home)
				$home = get_option("home");
				
			$type = gettype($url);
			$short_url = wp_affliate_cloacker::get_short_url($url);
			
			//builds new url
			$newurl = trailingslashit($home)."out/".$short_url;
			
			if($type == 'object') 
				$url->setAttribute("href", $newurl);
			else 
				return $newurl; 
			
		}
		static function get_affliate_links()
		{
			
			global $wpdb;
			
			$table = wp_affliate_cloacker::get_table();
			return $wpdb->get_results("select * from {$table}");
		}
		static function get_original_domains_array()
		{
			
			$affliate_links = wp_affliate_cloacker::get_affliate_links();
			$temp = array();
			foreach ($affliate_links as $link)
			$temp [] = $link->OriginalUrl;
			
			return $temp;
		}
		static function get_short_url($url)
		{
			$type = gettype($url);
			if($type == 'object') 
				$href = $url->getAttribute("href");
			else 
				$href = $url; 
				
			$href = str_replace('https', 'http', $href);
			
			$url = parse_url($href);
			
			if( substr($url['host'], 0, 4) == 'www.')
				$short_url = substr($url['host'], 4);
			else
				$short_url = $url['host'];
			
			
			return $short_url;
		}
		static function is_affliate_url($url)
		{
			
			$short_url = wp_affliate_cloacker::get_short_url($url);
			
			$domains_arr = wp_affliate_cloacker::get_original_domains_array();
					
			return in_array($short_url, $domains_arr);
		}
		static function the_content($content)
		{
	
			require_once(trailingslashit(ABSPATH) ."wp-admin/includes/misc.php");
			
			@mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
			
			//$dom = new DOMDocument();
			$dom = wp_affliate_cloacker::loadNprepare($content);
			//@$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
	
			$home = get_option("home");
			
			if(is_object($dom))
				$urls = $dom->getElementsByTagName("a");
			else 
				return $content;
			
			if ($urls->length > 0):
			
				foreach ($urls as $url):
				
					if (!wp_affliate_cloacker::is_no_change_url($url) &&
						 wp_affliate_cloacker::is_affliate_url($url)):
					
						wp_affliate_cloacker::set_redirect_url($home, $url);
					
					endif;
				endforeach;
			
				$content = $dom->saveHTML(); 
				// end of handling urls 
			endif;
			
			return $content;
			
		}
		static function get_next_run()
		{
			
			$last_run = get_option("aff_cloacker_cron_last_run");
			$interval = get_option("aff_cloacker_cron_interval");
			
			$next_run = strtotime("+ ".$interval,$last_run);
			
			return $next_run;
		}
		/*
		 * get next run to print
		 */
		static function get_gui_next_run()
		{
			$next_run = wp_affliate_cloacker::get_next_run();
			
			 $cron_array = get_option("cron");
			 
			 if (is_array($cron_array)):
			 
			 
			 foreach ($cron_array as $k=>$v):
			
			 if (is_array($v["wp_affliate_cron_event"])){
			 	
			 	return max($k,$next_run);
			 
			 }
			 endforeach;
			 endif;
			 
			 
			 return $next_run;
			 
		}
		/*
		 * cron job function
		 */
		static function do_cron()
		{
				
				$now = time();
				$next_run = wp_affliate_cloacker::get_next_run();
			
				if ($now > $next_run)
					wp_affliate_cloacker::do_sync();
		}
		function my_flush_rules(){
			
			$rules = get_option( 'rewrite_rules' );
			
			if ( ! isset( $rules['(out)/(.*)$'] ) ) {
			
				global $wp_rewrite;
			   	$wp_rewrite->flush_rules();	
			}
		}
		
		
		
		// Adding a new rule
		function my_insert_rewrite_rules( $rules )
		{
			$newrules = array();
			$newrules['(out)/(.*)$'] = 'index.php?url=$matches[2]';
			return $newrules + $rules;
		}
		
		// Adding the id var so that WP recognizes it
		function my_insert_query_vars( $vars )
		{
		    array_push($vars, 'url');
		    return $vars;
		}
		
		function loadNprepare($content) {
		        
			if (!empty($content)) {
		    
				if (empty($encod))
					$encod  = mb_detect_encoding($content);
	
				$headpos = mb_strpos($content,'<head>');
	
				if (FALSE=== $headpos)
					$headpos= mb_strpos($content,'<HEAD>');
				if (FALSE!== $headpos) {
					$headpos+=6;
					$content = mb_substr($content,0,$headpos) . '<meta http-equiv="Content-Type" content="text/html; charset='.$encod.'">' .mb_substr($content,$headpos);
				}
	
				$content=mb_convert_encoding($content, 'HTML-ENTITIES', $encod);
			}
	        
			$dom = new DomDocument;
	        $res = @$dom->loadHTML($content);
	        
	        if (!$res) return FALSE;
	        return $dom;
		}
		
		function the_test($test)
		{
		
			echo 'foo' . $test; die();
		}
	}
endif;