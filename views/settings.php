<div class="wrap">
	<h2><?php  _e("Affliate Cloacker Settings",AFF_CLOACKER_TEXT_DOMAIN);?></h2>
	
	<form method="post" action="options.php">
			<?php 
			wp_nonce_field('update-options');
			$interval = get_option("aff_cloacker_cron_interval");
			?>
			
		<table>
		<tr>
			<td>
				<label for="aff_cloacker_webservice_url"><?php _e("Webservice Url",AFF_CLOACKER_TEXT_DOMAIN);?></label>
			</td>
			<td>
				<input type="text" name="aff_cloacker_webservice_url" id="aff_cloacker_webservice_url" 
				value="<?php echo get_option("aff_cloacker_webservice_url");?>"/>
			</td>
			<td>
				<a href="<?php echo esc_url(get_option("aff_cloacker_webservice_url"),array("http"));?>" target="_blank">
				<?php _e("visit",AFF_CLOACKER_TEXT_DOMAIN);?>
				</a>
			</td>
		</tr>
		
		<tr>
		<td><label for="aff_cloacker_cron_interval"><?php _e("Sync Every",AFF_CLOACKER_TEXT_DOMAIN);?></label></td>
		<td>
			<select name="aff_cloacker_cron_interval" id="aff_cloacker_cron_interval">
				<option <?php selected( $interval, "5 minutes" ); ?>   value="5 minutes" ><?php _e("5 minutes",AFF_CLOACKER_TEXT_DOMAIN);?></option>
				<option <?php selected( $interval, "1 day" ); ?>   value="1 day" <?php selected( $interval, "1 day" ); ?> ><?php _e("1 day",AFF_CLOACKER_TEXT_DOMAIN);?></option>
				<option <?php selected( $interval, "2 days" ); ?>  value="2 days"><?php _e("2 days",AFF_CLOACKER_TEXT_DOMAIN);?></option>
				<option <?php selected( $interval, "1 week" ); ?>  value="1 week"><?php _e("1 week",AFF_CLOACKER_TEXT_DOMAIN);?></option>
				<option <?php selected( $interval, "2 weeks" ); ?> value="2 weeks"><?php _e("2 weeks",AFF_CLOACKER_TEXT_DOMAIN);?></option>
			</select>
		</td>
		</tr>
		<tr>
			<td>
				<input type="checkbox" id="aff_cloacker_use_in_rss" value ="1" name="aff_cloacker_use_in_rss" 
				<?php   checked(get_option("aff_cloacker_use_in_rss"),1); ?>/>
			</td>
			<td>
				<label for="aff_cloacker_use_in_rss"><?php _e("Use In Rss",AFF_CLOACKER_TEXT_DOMAIN);?></label>
			</td>
		</tr>
	
		<tr>
			<td>
				<input type="checkbox" id="aff_cloacker_use_in_widget" value ="1" name="aff_cloacker_use_in_widget" 
				<?php   checked(get_option("aff_cloacker_use_in_widget"),1); ?>/>
				</td>
				<td>
				<label for="aff_cloacker_use_in_widget"><?php _e("Use In Text Widgets",AFF_CLOACKER_TEXT_DOMAIN);?></label>
			</td>
		</tr>
		<tr>
			<td>
				<input type="checkbox" id="aff_cloacker_use_in_content" value ="1" name="aff_cloacker_use_in_content" 
				<?php   checked(get_option("aff_cloacker_use_in_content"),1); ?>/>
				</td>
				<td>
				<label for="aff_cloacker_use_in_content"><?php _e("Use In Content",AFF_CLOACKER_TEXT_DOMAIN);?></label>
			</td>
		</tr>
		<tr>
			<td>
				<input type="checkbox" id="aff_cloacker_use_in_excerpt" value ="1" name="aff_cloacker_use_in_excerpt" 
				<?php   checked(get_option("aff_cloacker_use_in_excerpt"),1); ?>/>
				</td>
				<td>
				<label for="aff_cloacker_use_in_excerpt"><?php _e("Use In Excerpt",AFF_CLOACKER_TEXT_DOMAIN);?></label>
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="aff_cloacker_cron_interval,aff_cloacker_webservice_url,
				aff_cloacker_use_in_rss,aff_cloacker_use_in_content,aff_cloacker_use_in_excerpt,aff_cloacker_use_in_widget" />
				<input type="submit" class='button-primary' value="<?php _e("Save Settings",AFF_CLOACKER_TEXT_DOMAIN);?>"/>
				</form>	
			</td>
		</tr>
		<tr>
		<td>
			<form method="post" action="options-general.php?page=custom-affiliate-links-cloaker%2Faffliate-cloacker-class.php">
				<input type="submit" class='button-primary' 
				name="manual_sync" value="<?php _e("Manual Sync",AFF_CLOACKER_TEXT_DOMAIN);?>"/>
				</form>
				</td>
				<td>
				<form method="post" action="options-general.php?page=custom-affiliate-links-cloaker%2Faffliate-cloacker-class.php">
				<input type="submit" class='button-primary' 
				name="empty_database" value="<?php _e("Empty Database",AFF_CLOACKER_TEXT_DOMAIN);?>"/>
			
			</td>
		</tr>
		</table>
	</form>
	<p>
	<label><b><?php _e("Current Server Time",AFF_CLOACKER_TEXT_DOMAIN);?></b></label>
	<label>
	<?php
	 $timestamp =  time();
	 echo wp_affliate_cloacker::to_date($timestamp);
	?>
	</label>
	</p>
	
	<p>
		<label><b><?php _e("Last Run",AFF_CLOACKER_TEXT_DOMAIN);?></b></label>
			<label>
			<?php
			 $timestamp =  get_option("aff_cloacker_cron_last_run");
			 echo wp_affliate_cloacker::to_date($timestamp);
			?>
		</label>
	</p>
	
	<p>
		<label>
			<b>
				<?php _e("Next Run",AFF_CLOACKER_TEXT_DOMAIN);?>
			</b>
		</label>
		<label>
			<?php
			 $timestamp =  wp_affliate_cloacker::get_gui_next_run();
			 echo wp_affliate_cloacker::to_date($timestamp);
			?>
		</label>
	</p>
	
	<p>
		<label>
			<b>
				<?php
				echo wp_affliate_cloacker::count_affliate_table();
				?>
			</b>
		</label>
		<label><?php _e("Links found",AFF_CLOACKER_TEXT_DOMAIN);?></label>
	</p>
	
	<?php 
	
		if (isset($_POST['manual_sync'])):
		
			wp_affliate_cloacker::do_sync();
		endif;
		
		if (isset($_POST['empty_database'])):
			
			if (wp_affliate_cloacker::truncate_db())
				_e("Database has been emptied",AFF_CLOACKER_TEXT_DOMAIN);
			else
				_e("Could not empty database",AFF_CLOACKER_TEXT_DOMAIN);
		
		endif;			
	?>
</div>