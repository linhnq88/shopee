<?php
defined("ABSPATH") or die("");
	$dbvar_maxtime		= DUP_PRO_DB::getVariable('wait_timeout');
	$dbvar_maxpacks		= DUP_PRO_DB::getVariable('max_allowed_packet');
	$dbvar_maxtime		= is_null($dbvar_maxtime) ? DUP_PRO_U::__("unknow") : $dbvar_maxtime;
	$dbvar_maxpacks		= is_null($dbvar_maxpacks) ? DUP_PRO_U::__("unknow") : $dbvar_maxpacks;

	$space				= @disk_total_space(DUPLICATOR_PRO_WPROOTPATH);
	$space_free			= @disk_free_space(DUPLICATOR_PRO_WPROOTPATH);
	$perc				= @round((100 / $space) * $space_free, 2);
	$mysqldumpPath		= DUP_PRO_DB::getMySqlDumpPath();
	$mysqlDumpSupport	= ($mysqldumpPath) ? $mysqldumpPath : 'Path Not Found';
	$client_ip_address	= DUP_PRO_Server::getClientIP();
	$error_log_path = ini_get('error_log');
?>

<!-- ==============================
SERVER SETTINGS -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-tachometer"></i>
		<?php DUP_PRO_U::_e("Server Settings") ?>
		<div class="dup-box-arrow"></div>
	</div>
	<div class="dup-box-panel" id="dup-settings-diag-srv-panel" style="<?php echo $ui_css_srv_panel ?>">
		<table class="widefat" cellspacing="0">
			<tr>
				<td class='dpro-settings-diag-header' colspan="2"><?php DUP_PRO_U::_e("General"); ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Duplicator Version"); ?></td>
				<td>
					<?php echo DUPLICATOR_PRO_VERSION ?> - 
					<small><i><a href="update-core.php?dup_pro_clear_updater_cache=1"><?php DUP_PRO_U::_e("Check WordPress Updates"); ?></a></i></small>
				</td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Operating System"); ?></td>
				<td><?php echo PHP_OS ?></td>
			</tr>
			<tr>
				<td><?php _e("Timezone"); ?></td>
				<td><?php echo date_default_timezone_get(); ?> &nbsp; <small><i>This is a <a href='options-general.php'>WordPress setting</a></i></small></td>
			</tr>
			<tr>
				<td><?php _e("Server Time"); ?></td>
				<td><?php echo date("Y-m-d H:i:s"); ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Web Server"); ?></td>
				<td><?php echo $_SERVER['SERVER_SOFTWARE'] ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Root Path"); ?></td>
				<td><?php echo DUPLICATOR_PRO_WPROOTPATH ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("ABSPATH"); ?></td>
				<td><?php echo ABSPATH ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Plugins Path"); ?></td>
				<td><?php echo DUP_PRO_U::safePath(WP_PLUGIN_DIR) ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Loaded PHP INI"); ?></td>
				<td><?php echo php_ini_loaded_file(); ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Server IP"); ?></td>
				<td><?php echo $_SERVER['SERVER_ADDR']; ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Client IP"); ?></td>
				<td><?php echo $client_ip_address; ?></td>
			</tr>
			<tr style="font-style: italic">
				<td>
					<?php DUP_PRO_U::_e("Host"); ?><br/>
					<small><?php DUP_PRO_U::_e("version scope"); ?></small>
				</td>
				<td>
					<?php
						$url =  parse_url(get_site_url(), PHP_URL_HOST);
						echo $url;
					?>
					<br/>
					<small><?php echo "WP-{$wp_version}, DP-" . DUPLICATOR_PRO_VERSION . " | PHP-" .  phpversion() . ', DB-' . DUP_PRO_DB::getVersion(); ?></small>
				</td>
			</tr>
			<tr>
				<td class='dpro-settings-diag-header' colspan="2">WordPress</td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Version"); ?></td>
				<td><?php echo $wp_version ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Langugage"); ?></td>
				<td><?php echo get_bloginfo('language') ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Charset"); ?></td>
				<td><?php echo get_bloginfo('charset') ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Memory Limit "); ?></td>
				<td><?php echo WP_MEMORY_LIMIT ?> (<?php
					DUP_PRO_U::_e("Max");
					echo '&nbsp;' . WP_MAX_MEMORY_LIMIT;
					?>)</td>
			</tr>
			<tr>
				<td class='dpro-settings-diag-header' colspan="2">PHP</td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Version"); ?></td>
				<td><?php echo phpversion() ?></td>
			</tr>
			<tr>
				<td>SAPI</td>
				<td><?php echo PHP_SAPI ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("User"); ?></td>
				<td><?php echo DUP_PRO_Server::getCurrentUser(); ?></td>
			</tr>
			<tr>
				<td><a href="http://php.net/manual/en/features.safe-mode.php" target="_blank"><?php DUP_PRO_U::_e("Safe Mode"); ?></a></td>
				<td>
					<?php
					echo (((strtolower(@ini_get('safe_mode')) == 'on') || (strtolower(@ini_get('safe_mode')) == 'yes') ||
					(strtolower(@ini_get('safe_mode')) == 'true') || (ini_get("safe_mode") == 1 ))) ? DUP_PRO_U::__('On') : DUP_PRO_U::__('Off');
					?>
				</td>
			</tr>
			<tr>
				<td><a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank"><?php DUP_PRO_U::_e("Memory Limit"); ?></a></td>
				<td><?php echo @ini_get('memory_limit') ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Memory In Use"); ?></td>
				<td><?php echo size_format(@memory_get_usage(TRUE), 2) ?></td>
			</tr>
			<tr>
				<td><a href="http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time" target="_blank"><?php DUP_PRO_U::_e("Max Execution Time"); ?></a></td>
				<td>
					<?php
						echo @ini_get('max_execution_time');
						$try_update = set_time_limit(0);
						$try_update = $try_update ? 'is dynamic' : 'value is fixed';
						echo " (default) - {$try_update}";
					?>
					<i class="fa fa-question-circle data-size-help"
						data-tooltip-title="<?php DUP_PRO_U::_e("Max Execution Time"); ?>"
						data-tooltip="<?php DUP_PRO_U::_e('If the value shows dynamic then this means its possible for PHP to run longer than the default.  '
							. 'If the value is fixed then PHP will not be allowed to run longer than the default.'); ?>"></i>
				</td>
			</tr>
			<tr>
				<td><a href="http://php.net/manual/en/ini.core.php#ini.open-basedir" target="_blank"><?php DUP_PRO_U::_e("open_basedir"); ?></a></td>
				<td>
					<?php
					$open_base_set = @ini_get('open_basedir');
					echo empty($open_base_set) ? DUP_PRO_U::__('Off') : $open_base_set;
					?>
				</td>
			</tr>
			<tr>
				<td><a href="http://us3.php.net/shell_exec" target="_blank"><?php DUP_PRO_U::_e("Shell Exec"); ?></a></td>
				<td><?php echo (DUP_PRO_Shell_U::isShellExecEnabled()) ? DUP_PRO_U::_e("Is Supported") : DUP_PRO_U::_e("Not Supported"); ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Shell Exec Zip"); ?></td>
				<td><?php echo (DUP_PRO_Zip_U::getShellExecZipPath() != null) ? DUP_PRO_U::_e("Is Supported") : DUP_PRO_U::_e("Not Supported"); ?></td>
			</tr>
			<tr>
				<td><a href="https://suhosin.org/stories/index.html" target="_blank"><?php DUP_PRO_U::_e("Suhosin Extension"); ?></a></td>
				<td><?php echo extension_loaded('suhosin') ? DUP_PRO_U::_e("Enabled") : DUP_PRO_U::_e("Disabled"); ?></td>
			</tr>
			<tr>
				<td>Architecture</td>
				<td>
					<?php 
						$php_int_size = PHP_INT_SIZE;
						switch($php_int_size) {
							case 4:
								DUP_PRO_U::_e('32-bit');
								break;
							case 8:
								DUP_PRO_U::_e('64-bit');
								break;
							default:
        						DUP_PRO_U::_e('Unknown');
						}
					?>
				</td>
			</tr>
			<tr>
				<td><?php _e("Error Log File ", 'duplicator'); ?></td>
				<td><?php echo $error_log_path; ?></td>
			</tr>
			<tr>
				<td class='dpro-settings-diag-header' colspan="2">MySQL</td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Version"); ?></td>
				<td><?php echo DUP_PRO_DB::getVersion() ?></td>
			</tr>
			<tr>
				<td><?php DUP_PRO_U::_e("Charset"); ?></td>
				<td><?php echo DB_CHARSET ?></td>
			</tr>
			<tr>
				<td><a href="http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_wait_timeout" target="_blank"><?php DUP_PRO_U::_e("Wait Timeout"); ?></a></td>
				<td><?php echo $dbvar_maxtime ?></td>
			</tr>
			<tr>
				<td style="white-space:nowrap"><a href="http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_max_allowed_packet" target="_blank"><?php DUP_PRO_U::_e("Max Allowed Packets"); ?></a></td>
				<td><?php echo $dbvar_maxpacks ?></td>
			</tr>
			<tr>
				<td><a href="http://dev.mysql.com/doc/refman/5.0/en/mysqldump.html" target="_blank"><?php DUP_PRO_U::_e("msyqldump Path"); ?></a></td>
				<td><?php echo $mysqlDumpSupport ?></td>
			</tr>
			<tr>
				<td class='dpro-settings-diag-header' colspan="2"><?php DUP_PRO_U::_e("Server Disk"); ?></td>
			</tr>
			<tr valign="top">
				<td><?php DUP_PRO_U::_e('Free space', 'hyper-cache'); ?></td>
				<td><?php echo $perc; ?>% -- <?php echo DUP_PRO_U::byteSize($space_free); ?> from <?php echo DUP_PRO_U::byteSize($space); ?><br/>
					<small>
						<?php DUP_PRO_U::_e("Note: This value is the physical servers hard-drive allocation."); ?> <br/>
						<?php DUP_PRO_U::_e("On shared hosts check your control panel for the 'TRUE' disk space quota value."); ?>
					</small>
				</td>
			</tr>

		</table><br/>
	</div>
</div>
<br/>