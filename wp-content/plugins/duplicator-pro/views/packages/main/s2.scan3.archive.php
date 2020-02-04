<?php
defined("ABSPATH") or die("");
	function _duplicatorGetRootPath() {
		$txt   = DUP_PRO_U::__('Root Path');
		$root  = rtrim(DUPLICATOR_PRO_WPROOTPATH, '//');
		$sroot = strlen($root) > 50 ? substr($root, 0, 50) . '...' : $root;
		echo "<div title='{$root}' class='divider'><i class='fa fa-folder-open'></i> {$sroot}</div>";
	}
	$dbbuild_mode =  DUP_PRO_DB::getBuildMode();
	$legacy_sql_string = ($Package->Database->Compatible) ? "<i style='color:maroon'>".DUP_PRO_U::__('Compatibility Mode Enabled').'</i>' : '';
?>

<!-- ================================================================
ARCHIVE
================================================================ -->
<div class="details-title">
	<i class="fa fa-file-archive-o"></i>&nbsp;<?php DUP_PRO_U::_e('Archive'); ?>
	<sup class="dup-small-ext-type"><?php echo $global->get_archive_extension_type(); ?></sup>
	<div class="dup-more-details" onclick="DupPro.Pack.showDetailsDlg()" title="<?php DUP_PRO_U::_e('Show Scan Details');?>"><i class="fa fa-window-maximize"></i></div>
</div>

<div class="scan-header scan-item-first">
	<i class="fa fa-files-o"></i>
	<?php DUP_PRO_U::_e("Files"); ?>
	<div class="scan-header-details">
		<div class="dup-scan-filter-status">
			<?php
				if ($archive_export_onlydb) {
					echo '<i class="fa fa-filter"></i> '; DUP_PRO_U::_e('Database Only');
				} elseif ($Package->Archive->FilterOn) {
					echo '<i class="fa fa-filter"></i> '; DUP_PRO_U::_e('Enabled');
				}
			?>
		</div>

		<div id="data-arc-size1"></div>
		<i class="fa fa-question-circle data-size-help"
			data-tooltip-title="<?php DUP_PRO_U::_e("File Size:"); ?>"
			data-tooltip="<?php DUP_PRO_U::_e('The files size represents only the included files before compression is applied. It does not include the size of the database '
				. 'script and in most cases the package size once completed will be smaller than this number unless shell_exec zip with no compression is enabled.'); ?>"></i>
		<div class="dup-data-size-uncompressed"><?php DUP_PRO_U::_e("uncompressed"); ?></div>
	</div>
</div>

<!-- ======================
SIZE CHECKS -->
<div class="scan-item">
	<div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php DUP_PRO_U::_e('Size Checks');?></div>
		<div id="data-arc-status-size"></div>
	</div>
	<div class="info" id="scan-item-file-size">
		<b><?php DUP_PRO_U::_e('Size'); ?>:</b> <span id="data-arc-size2"></span>  &nbsp; | &nbsp;
		<b><?php DUP_PRO_U::_e('Files'); ?>:</b> <span id="data-arc-files"></span>  &nbsp; | &nbsp;
		<b><?php DUP_PRO_U::_e('Directories '); ?>:</b> <span id="data-arc-dirs"></span>   &nbsp; | &nbsp;
		<b><?php DUP_PRO_U::_e('Total'); ?>:</b> <span id="data-arc-fullcount"></span>
		<br/>
		<?php
			DUP_PRO_U::_e('Compressing larger sites on <i>some budget hosts</i> may cause timeouts.  ' );
			echo "<i>&nbsp; <a href='javascipt:void(0)' onclick='jQuery(\"#size-more-details\").toggle(100)'>[" . DUP_PRO_U::__('more details...') . "]</a></i>";
		?>
		<div id="size-more-details">
			<?php
				echo "<b>" . DUP_PRO_U::__('Overview') . ":</b><br/>";
				$total_size_max = ($global->archive_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec)
						? DUPLICATOR_PRO_SCAN_SITE_SHELL_EXEC_WARNING_SIZE
						: DUPLICATOR_PRO_SCAN_SITE_WARNING_SIZE;

				printf(DUP_PRO_U::__('This notice is triggered at <b>%s</b> and can be ignored on most hosts.  If the build process hangs or is unable to complete '
					. 'then this host has strict processing limits.  Below are some options you can take to overcome constraints setup on this host.'),
					DUP_PRO_U::byteSize($total_size_max));

				echo '<br/><br/>';

				echo "<b>" . DUP_PRO_U::__('Timeout Options') . ":</b><br/>";
				echo '<ul>';
				echo '<li>' . DUP_PRO_U::__('Apply the "Quick Filters" below or click the back button to apply on previous page.') . '</li>';
				echo '<li>' . DUP_PRO_U::__('See the FAQ link to adjust this hosts timeout limits: ') . "&nbsp;<a href='https://snapcreek.com/duplicator/docs/faqs-tech/#faq-trouble-100-q' target='_blank'>" . DUP_PRO_U::__('What can I try for Timeout Issues?') . '</a></li>';
				echo '</ul>';

				$hlptxt = sprintf(DUP_PRO_U::__('Files over %1$s are listed below. Larger files such as movies or zipped content can cause timeout issues on some budget hosts.  '
					. 'If you are having issues creating a package try excluding the directory paths below or go back to Step 1 and add them.'),
					DUP_PRO_U::byteSize(DUPLICATOR_PRO_SCAN_WARNFILESIZE));
			?>
		</div>
		<script id="hb-files-large" type="text/x-handlebars-template">
			<div class="container">
				<div class="hdrs">
					<span style="font-weight:bold">
						<?php DUP_PRO_U::_e('Quick Filters'); ?>
						<sup><i class="fa fa-question-circle" data-tooltip-title="<?php DUP_PRO_U::_e("Large Files"); ?>" data-tooltip="<?php echo $hlptxt; ?>"></i></sup>
					</span>
					<div class='hdrs-up-down'>
						<i class="fa fa-caret-up fa-lg dup-nav-toggle" onclick="DupPro.Pack.toggleAllDirPath(this, 'hide')" title="<?php DUP_PRO_U::_e("Hide All"); ?>"></i>
						<i class="fa fa-caret-down fa-lg dup-nav-toggle" onclick="DupPro.Pack.toggleAllDirPath(this, 'show')" title="<?php DUP_PRO_U::_e("Show All"); ?>"></i>
					</div>
				</div>
				<div class="data">
					<?php _duplicatorGetRootPath();	?>
					{{#if ARC.FilterInfo.Files.Size}}
						{{#each ARC.FilterInfo.TreeSize as |directory|}}
							<div class="directory">
								<i class="fa fa-caret-right fa-lg dup-nav" onclick="DupPro.Pack.toggleDirPath(this)"></i> &nbsp;
								{{#if directory.iscore}}
									<i class="fa fa-window-close-o  chk-off" title="<?php DUP_PRO_U::_e('Core WordPress directories should not be filtered. Use caution when excluding files.'); ?>"></i>
								{{else}}
									<input type="checkbox" name="dir_paths[]" value="{{directory.dir}}" id="lf_dir_{{@index}}" onclick="DupPro.Pack.filesOff(this)" />
								{{/if}}
								<label for="lf_dir_{{@index}}" title="{{directory.dir}}">
									<i class="size">[{{directory.size}}]</i> /{{directory.sdir}}/
								</label> <br/>
								<div class="files">
									{{#each directory.files as |file|}}
										<input type="checkbox" name="file_paths[]" value="{{file.path}}" id="lf_file_{{directory.dir}}-{{@index}}" />
										<label for="lf_file_{{directory.dir}}-{{@index}}" title="{{file.path}}">
											<i class="size">[{{file.bytes}}]</i>	{{file.name}}
										</label> <br/>
									{{/each}}
								</div>
							</div>
						{{/each}}
					{{else}}
						 <?php DUP_PRO_U::_e('No large files found during this scan.'); ?>
					{{/if}}
				</div>
			</div>
			<div class="apply-btn">
				<div class="apply-warn">
					 <?php DUP_PRO_U::_e('*Checking a directory will exclude all items in that path recursively.'); ?>
				</div>
				<button type="button" class="button-small" onclick="DupPro.Pack.applyFilters(this, 'large')">
					<i class="fa fa-filter"></i> <?php DUP_PRO_U::_e('Add Filters &amp; Rescan');?>
				</button>
				<button type="button" class="button-small" onclick="DupPro.Pack.showPathsDlg('large')" title="<?php DUP_PRO_U::_e('Copy Paths to Clipboard');?>">
					<i class="fa fa-clipboard" aria-hidden="true"></i>
				</button>
			</div>
		</script>
		<div id="hb-files-large-result" class="hb-files-style"></div>
	</div>
</div>

<!-- ======================
ADDON SITES -->
<div id="addonsites-block"  class="scan-item">
	<div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php DUP_PRO_U::_e('Addon Sites');?></div>
		<div id="data-arc-status-addonsites"></div>
	</div>
    <div class="info">
        <div style="margin-bottom:10px;">
            <small>
            <?php
                printf(DUP_PRO_U::__('An "Addon Site" is a separate WordPress site(s) residing in subdirectories within this site. If you confirm these to be separate sites, '
					. 'then it is recommended that you exclude them by checking the corresponding boxes below and clicking the \'Add Filters & Rescan\' button.  To backup the other sites '
					. 'install the plugin on the sites needing to be backed-up.'));
            ?>
            </small>
        </div>
        <script id="hb-addon-sites" type="text/x-handlebars-template">
            <div class="container">
                <div class="hdrs">
                    <span style="font-weight:bold">
                        <?php DUP_PRO_U::_e('Quick Filters'); ?>
                    </span>
                </div>
                <div class="data">
                    {{#if ARC.FilterInfo.Dirs.AddonSites.length}}
                        {{#each ARC.FilterInfo.Dirs.AddonSites as |path|}}
                        <div class="directory">
                            <input type="checkbox" name="dir_paths[]" value="{{path}}" id="as_dir_{{@index}}"/>
                            <label for="as_dir_{{@index}}" title="{{path}}">
                                {{path}}
                            </label>
                        </div>
                        {{/each}}
                    {{else}}
                    <?php DUP_PRO_U::_e('No add on sites found.'); ?>
                    {{/if}}
                </div>
            </div>
            <div class="apply-btn">
                <div class="apply-warn">
                    <?php DUP_PRO_U::_e('*Checking a directory will exclude all items in that path recursively.'); ?>
                </div>
                <button type="button" class="button-small" onclick="DupPro.Pack.applyFilters(this, 'addon')">
                    <i class="fa fa-filter"></i> <?php DUP_PRO_U::_e('Add Filters &amp; Rescan');?>
                </button>
            </div>
        </script>
        <div id="hb-addon-sites-result" class="hb-files-style"></div>
    </div>
</div>

<!-- ======================
NAME CHECKS -->
<div class="scan-item">
	<div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
		<div class="text"><i class="fa fa-caret-right"></i> <?php DUP_PRO_U::_e('Name Checks');?></div>
		<div id="data-arc-status-names"></div>
	</div>
	<div class="info">
		<?php
			DUP_PRO_U::_e('Unicode and special characters such as "*?><:/\|", can be problematic on some hosts.  ');
            echo '<b>';
            DUP_PRO_U::_e('Only consider using this filter if the package build is failing. Select files that are not important to your site or you can migrate manually.');
            echo'</b>';
			$txt = DUP_PRO_U::__('If this environment/system and the system where it will be installed are setup to support Unicode and long paths then these filters can be ignored.  '
				. 'If you run into issues with creating or installing a package, then is recommended to filter these paths.');
		?>
		<script id="hb-files-utf8" type="text/x-handlebars-template">
			<div class="container">
				<div class="hdrs">
					<span style="font-weight:bold"><?php DUP_PRO_U::_e('Quick Filters');?></span>
						<sup><i class="fa fa-question-circle" data-tooltip-title="<?php DUP_PRO_U::_e("Name Checks"); ?>" data-tooltip="<?php echo $txt; ?>"></i></sup>
					<div class='hdrs-up-down'>
						<i class="fa fa-caret-up fa-lg dup-nav-toggle" onclick="DupPro.Pack.toggleAllDirPath(this, 'hide')" title="<?php DUP_PRO_U::_e("Hide All"); ?>"></i>
						<i class="fa fa-caret-down fa-lg dup-nav-toggle" onclick="DupPro.Pack.toggleAllDirPath(this, 'show')" title="<?php DUP_PRO_U::_e("Show All"); ?>"></i>
					</div>
				</div>
				<div class="data">
					<?php _duplicatorGetRootPath();	?>
					{{#if  ARC.FilterInfo.TreeWarning}}
						{{#each ARC.FilterInfo.TreeWarning as |directory|}}
							<div class="directory">
								{{#if directory.count}}
									<i class="fa fa-caret-right fa-lg dup-nav" onclick="DupPro.Pack.toggleDirPath(this)"></i> &nbsp;
								{{else}}
									<i class="empty"></i>
								{{/if}}

								{{#if directory.iscore}}
									<i class="fa fa-window-close-o  chk-off" title="<?php DUP_PRO_U::_e('Core WordPress directories should not be filtered. Use caution when excluding files.'); ?>"></i>
								{{else}}
									<input type="checkbox" name="dir_paths[]" value="{{directory.dir}}" id="nc1_dir_{{@index}}" onclick="DupPro.Pack.filesOff(this)" />
								{{/if}}

								<label for="nc1_dir_{{@index}}" title="{{directory.dir}}">
									<i class="count">({{directory.count}})</i>
									/{{directory.sdir}}/
								</label> <br/>
								<div class="files">
									{{#each directory.files}}
										<input type="checkbox" name="file_paths[]" value="{{path}}" id="warn_file_{{directory.dir}}-{{@index}}" />
										<label for="warn_file_{{directory.dir}}-{{@index}}" title="{{path}}">
											{{name}}
										</label> <br/>
									{{/each}}
								</div>
							</div>
						{{/each}}
					{{else}}
						<?php DUP_PRO_U::_e('No file/directory name warnings found.');?>
					{{/if}}
				</div>
			</div>
			<div class="apply-btn">
				<button type="button" class="button-small" onclick="DupPro.Pack.applyFilters(this, 'utf8')">
					<i class="fa fa-filter"></i> <?php DUP_PRO_U::_e('Add Filters &amp; Rescan');?>
				</button>
				<button type="button" class="button-small" onclick="DupPro.Pack.showPathsDlg('utf8')" title="<?php DUP_PRO_U::_e('Copy Paths to Clipboard');?>">
					<i class="fa fa-clipboard" aria-hidden="true"></i>
				</button>
			</div>
		</script>
		<div id="hb-files-utf8-result" class="hb-files-style"></div>
	</div>
</div>

<!-- ======================
UNREADABLE FILES -->
<div id="scan-unreadable-items" class="scan-item scan-item-last">
    <div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
        <div class="text"><i class="fa fa-caret-right"></i> <?php DUP_PRO_U::_e('Read Checks');?></div>
        <div id="data-arc-status-unreadablefiles"></div>
    </div>
    <div class="info">
        <?php
        DUP_PRO_U::_e('PHP is unable to read the following items and they will <u>not</u> be included in the package.  Please work with your host to adjust the permissions or resolve the '
			. 'symbolic-link(s) shown in the lists below.  If these items are not needed then this notice can be ignored.');
        ?>
        <script id="unreadable-files" type="text/x-handlebars-template">
            <div class="container">
                <div class="data">
					<b><?php DUP_PRO_U::_e('Unreadable Items:');?></b> <br/>
					<div class="directory">
						{{#if ARC.UnreadableItems}}
							{{#each ARC.UnreadableItems as |uitem|}}
								<i class="fa fa-lock"></i> {{uitem}} <br/>
							{{/each}}
						{{else}}
                            <i><?php DUP_PRO_U::_e('No unreadable items found.<br>');?></i>
						{{/if}}
					</div>

					<b><?php DUP_PRO_U::_e('Recursive Links:');?></b> <br/>
					<div class="directory">
						{{#if  ARC.RecursiveLinks}}
							{{#each ARC.RecursiveLinks as |link|}}
								<i class="fa fa-lock"></i> {{link}} <br/>
							{{/each}}
						{{else}}
							<i><?php DUP_PRO_U::_e('No recursive sym-links found.<br>');?></i>
						{{/if}}
					</div>
                </div>
            </div>
        </script>
        <div id="unreadable-files-result" class="hb-files-style"></div>
    </div>
</div>


<!-- ================================================================
DATABASE
================================================================ -->
<div class="scan-header">
	<i class="fa fa-table"></i>
	<?php DUP_PRO_U::_e("Database"); ?>
	<div class="scan-header-details">
		<small style="font-weight:normal; font-size:12px"><?php echo $legacy_sql_string ?></small>
		<div class="dup-scan-filter-status">
			<?php
			if ($Package->Database->FilterOn) {
				echo '<i class="fa fa-filter"></i> ';
				DUP_PRO_U::_e('Enabled');
			}
			?>
		</div>
		
		<div id="data-db-size1"></div>
		<i class="fa fa-question-circle data-size-help"
			data-tooltip-title="<?php DUP_PRO_U::_e("Database Size:"); ?>"
			data-tooltip="<?php DUP_PRO_U::_e('The database size represents only the included tables. The process for gathering the size uses the query SHOW TABLE STATUS.  '
						. 'The overall size of the database file can impact the final size of the package.'); ?>"></i>
		<div class="dup-data-size-uncompressed"><?php DUP_PRO_U::_e("uncompressed"); ?></div>


	</div>
</div>

<div id="dup-scan-db">
	<div class="scan-item  scan-item-last">
		<div class='title' onclick="DupPro.Pack.toggleScanItem(this);">
			<div class="text"><i class="fa fa-caret-right"></i> <?php DUP_PRO_U::_e('Overview');?></div>
			<div id="data-db-status-size1"></div>
		</div>
		<div class="info">
			<?php echo '<b>' . DUP_PRO_U::__('TOTAL SIZE') . ' &nbsp; &#8667; &nbsp; </b>'; ?>
			<b><?php DUP_PRO_U::_e('Size'); ?>:</b> <span id="data-db-size2"></span> &nbsp; | &nbsp;
			<b><?php DUP_PRO_U::_e('Tables'); ?>:</b> <span id="data-db-tablecount"></span> &nbsp; | &nbsp;
			<b><?php DUP_PRO_U::_e('Records'); ?>:</b> <span id="data-db-rows"></span> <br/>
			<?php
				printf(DUP_PRO_U::__('Total size and row count are approximate values.  The thresholds that trigger warnings are <i>%1$s OR %2$s records</i> toal for the entire database.  '
					. 'Large databases take time to process and can cause issues with server timeout and memory settings on some budget hosts.  If your server supports shell_exec '
					. 'and mysqldump you can try to enable this option from the settings menu.'),
					DUP_PRO_U::byteSize(DUPLICATOR_PRO_SCAN_DB_ALL_SIZE), number_format(DUPLICATOR_PRO_SCAN_DB_ALL_ROWS));


				echo '<br/><br/><hr size="1" />';

				//TABLE DETAILS
				echo '<b>' . DUP_PRO_U::__('TABLE DETAILS:') . '</b><br/>';
				printf(DUP_PRO_U::__('The notices for tables are <i>%1$s, %2$s records or names with upper-case characters</i>.  Individual tables will not trigger '
					. 'a notice message, but can help narrow down issues if they occur later on.'),
						DUP_PRO_U::byteSize(DUPLICATOR_PRO_SCAN_DB_TBL_SIZE),
						number_format(DUPLICATOR_PRO_SCAN_DB_TBL_ROWS));

				echo '<div id="dup-scan-db-info"><div id="data-db-tablelist"></div></div>';

				//RECOMMENDATIONS
				echo '<br/><hr size="1" />';
				echo '<b>' . DUP_PRO_U::__('RECOMMENDATIONS:', 'duplicator') . '</b><br/>';

				echo '<div style="padding:5px">';
				$lnk = '<a href="maint/repair.php" target="_blank">' . DUP_PRO_U::__('repair and optimization') . '</a>';
				printf(DUP_PRO_U::__('1. Run a %1$s on the table to improve the overall size and performance.'), $lnk);
				echo '<br/><br/>';
				_e('2. Remove post revisions and stale data from tables.  Tables such as logs, statistical or other non-critical data should be cleared.');
				echo '<br/><br/>';
				$lnk = '<a href="?page=duplicator-pro-settings&tab=package" target="_blank">' . DUP_PRO_U::__('Enable mysqldump') . '</a>';
				printf(DUP_PRO_U::__('3. %1$s if this host supports the option.'), $lnk);
				echo '<br/><br/>';
				$lnk = '<a href="http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_lower_case_table_names" target="_blank">' . DUP_PRO_U::__('lower_case_table_names') . '</a>';
				printf(DUP_PRO_U::__('4. For table name case sensitivity issues either rename the table with lower case characters or be prepared to work with the %1$s system variable setting.'), $lnk);
				echo '</div>';
			?>
		</div>
	</div>
</div>
<br/>


<!-- ==========================================
DIALOGS:
========================================== -->
<?php
	$alert1 = new DUP_PRO_UI_Dialog();
	$alert1->height     = 600;
	$alert1->width      = 600;
	$alert1->title		= DUP_PRO_U::__('Scan Details');
	$alert1->message	= "<div id='arc-details-dlg'></div>";
	$alert1->initAlert();

	$alert2 = new DUP_PRO_UI_Dialog();
	$alert2->height     = 425;
	$alert2->width      = 650;
	$alert2->title		= DUP_PRO_U::__('Copy Quick Filter Paths');
	$alert2->message	= "<div id='arc-paths-dlg'></div>";
	$alert2->initAlert();

    $alert3 = new DUP_PRO_UI_Dialog();
    $alert3->title		= DUP_PRO_U::__('WARNING!');
    $alert3->message	= DUP_PRO_U::__('Manual copy of selected text required on this browser.');
    $alert3->initAlert();

    $alert4 = new DUP_PRO_UI_Dialog();
    $alert4->title		= $alert3->title;
    $alert4->message	= DUP_PRO_U::__('Error applying filters.  Please go back to Step 1 to add filter manually!');
    $alert4->initAlert();
?>

<!-- =======================
DIALOG: Scan Results -->
<div id="dup-archive-details" style="display:none">

	<!-- PACKAGE -->
	<h2><i class="fa fa-archive"></i> <?php DUP_PRO_U::_e('Package');?></h2>
	<div class="info">
		<label><?php DUP_PRO_U::_e('Name');?>:</label> <?php echo $_POST['package-name']; ?><br/>
		<label><?php DUP_PRO_U::_e('Notes');?>:</label> <?php echo strlen($_POST['package-notes']) ? $_POST['package-notes'] : DUP_PRO_U::__('- no notes -') ; ?> <br/>
		<label><?php DUP_PRO_U::_e('Archive Engine');?>:</label> <a href="?page=duplicator-pro-settings&tab=package" target="_blank"><?php echo $global->get_archive_engine(); ?></a>
	</div><br/>

	<!-- DATABASE -->
	<h2><i class="fa fa-table"></i> <?php DUP_PRO_U::_e('Database');?></h2>
	<div class="info">
		<label><?php DUP_PRO_U::_e('Name:');?></label><?php echo DB_NAME; ?> <br/>
		<label><?php DUP_PRO_U::_e('Host:');?></label><?php echo DB_HOST; ?> <br/>
		<label><?php DUP_PRO_U::_e('Build Mode:');?></label> <a href="?page=duplicator-pro-settings&tab=package" target="_blank"><?php echo $dbbuild_mode ;?></a>
		<?php echo $legacy_sql_string ?>
	</div><br/>
	
	<!-- FILE FILTERS -->
	<h2 style="border:none">
		<i class="fa fa-filter"></i> <?php DUP_PRO_U::_e('File Filters');?>:
		<small style="font-weight:none; font-style: italic">
			<?php echo ($Package->Archive->FilterOn) ? DUP_PRO_U::__('Is currently enabled') : DUP_PRO_U::__('Is currently disabled') ;?>
		</small>
	</h2>
	<div class="filter-area">
		<b><i class="fa fa-folder-open"></i> <?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, "//");?></b>

		<script id="hb-filter-file-list" type="text/x-handlebars-template">
			<div class="file-info">
				<b>[<?php DUP_PRO_U::_e('Directories');	?>]</b>
				<div class="file-info">
					{{#if ARC.FilterInfo.Dirs.Instance}}
						{{#each ARC.FilterInfo.Dirs.Instance as |dir|}}
							{{stripWPRoot dir}}/<br/>
						{{/each}}
					{{else}}
						 <?php	DUP_PRO_U::_e('No custom directory filters set.');?>
					{{/if}}
				</div>

				<b>[<?php DUP_PRO_U::_e('Files');	?>]</b>
				<div class="file-info">
					{{#if ARC.FilterInfo.Files.Instance}}
						{{#each ARC.FilterInfo.Files.Instance as |file|}}
							{{stripWPRoot file}}<br/>
						{{/each}}
					{{else}}
						 <?php	DUP_PRO_U::_e('No custom file filters set.');?>
					{{/if}}
				</div>

				<b>[<?php DUP_PRO_U::_e('Auto Filters');?>]</b>
				<div class="file-info">
					{{#each ARC.FilterInfo.Dirs.Global as |dir|}}
						{{stripWPRoot dir}}/<br/>
					{{/each}}
				</div>

			</div>
		</script>
		<div class="hb-filter-file-list-result"></div>

		<b>[<?php DUP_PRO_U::_e('Excluded File Extensions');?>]</b><br/>
		<?php
			if (strlen( $Package->Archive->FilterExts)) {
				echo $Package->Archive->FilterExts;
			} else {
				DUP_PRO_U::_e('No file extension filters have been set.');
			}
		?>
	</div>

	<small>
		<?php DUP_PRO_U::_e('Path filters will be skipped during the archive process when enabled.'); ?>
		<a href="<?php echo DUPLICATOR_PRO_SITE_URL ?>/wp-admin/admin-ajax.php?action=duplicator_pro_package_scan" target="dup_pro_report">
			<?php DUP_PRO_U::_e('[view json result report]');?>
		</a>
	</small><br/>
</div>

<!-- =======================
DIALOG: PATHS COPY & PASTE -->
<div id="dup-archive-paths" style="display:none">

	<b><i class="fa fa-folder"></i> <?php DUP_PRO_U::_e('Directories');?></b>
	<div class="copy-button">
		<button type="button" class="button-small" onclick="DupPro.Pack.copyText(this, '#arc-paths-dlg textarea.path-dirs')">
			<i class="fa fa-clipboard"></i> <?php DUP_PRO_U::_e('Click to Copy');?>
		</button>
	</div>
	<textarea class="path-dirs"></textarea>
	<br/><br/>

	<b><i class="fa fa-files-o"></i> <?php DUP_PRO_U::_e('Files');?></b>
	<div class="copy-button">
		<button type="button" class="button-small" onclick="DupPro.Pack.copyText(this, '#arc-paths-dlg textarea.path-files')">
			<i class="fa fa-clipboard"></i> <?php DUP_PRO_U::_e('Click to Copy');?>
		</button>
	</div>
	<textarea class="path-files"></textarea>
	<br/>
	<small><?php DUP_PRO_U::_e('Copy the paths above and apply them as needed on Step 1 &gt; Archive &gt; Files section.');?></small>
</div>


<script>
jQuery(document).ready(function($)
{
	Handlebars.registerHelper('stripWPRoot', function(path) {
		return  path.replace('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, "//") ?>', '');
	});

	//Opens a dialog to show scan details
	DupPro.Pack.filesOff = function (dir)
	{
		var $checks = $(dir).parent('div.directory').find('div.files input[type="checkbox"]');
		$(dir).is(':checked')
			? $.each($checks, function() {$(this).attr({disabled : true, checked : false, title : '<?php DUP_PRO_U::_e('Directory applied filter set.');?>'});})
			: $.each($checks, function() {$(this).removeAttr('disabled checked title');});
	}

	//Opens a dialog to show scan details
	DupPro.Pack.showDetailsDlg = function ()
	{
		$('#arc-details-dlg').html($('#dup-archive-details').html());
		<?php $alert1->showAlert(); ?>
		return;
	}

	//Opens a dialog to show scan details
	DupPro.Pack.showPathsDlg = function (type)
	{
		var id = (type == 'large') ? '#hb-files-large-result' : '#hb-files-utf8-result'
		var dirFilters  = [];
		var fileFilters = [];
		$(id + " input[name='dir_paths[]']:checked").each(function()  {dirFilters.push($(this).val());});
		$(id + " input[name='file_paths[]']:checked").each(function() {fileFilters.push($(this).val());});

		var $dirs  = $('#dup-archive-paths textarea.path-dirs');
		var $files = $('#dup-archive-paths textarea.path-files');
		(dirFilters.length > 0)
		   ? $dirs.text(dirFilters.join(";\n"))
		   : $dirs.text("<?php DUP_PRO_U::_e('No directories have been selected!');?>");

	    (fileFilters.length > 0)
		   ? $files.text(fileFilters.join(";\n"))
		   : $files.text("<?php DUP_PRO_U::_e('No files have been selected!');?>");

		$('#arc-paths-dlg').html($('#dup-archive-paths').html());
		<?php $alert2->showAlert(); ?>

		return;
	}

	//Toggles a directory path to show files
	DupPro.Pack.toggleDirPath = function(item)
	{
		var $dir   = $(item).parents('div.directory');
		var $files = $dir.find('div.files');
		var $arrow = $dir.find('i.dup-nav');
		if ($files.is(":hidden")) {
			$arrow.addClass('fa-caret-down').removeClass('fa-caret-right');
			$files.show();
		} else {
			$arrow.addClass('fa-caret-right').removeClass('fa-caret-down');
			$files.hide(250);
		}
	}

	//Toggles a directory path to show files
	DupPro.Pack.toggleAllDirPath = function(chkBox, toggle)
	{
		var $dirs  = $(chkBox).parents('div.container').find('div.data div.directory');
		 (toggle == 'hide')
			? $.each($dirs, function() {$(this).find('div.files').show(); $(this).find('i.dup-nav').trigger('click');})
			: $.each($dirs, function() {$(this).find('div.files').hide(); $(this).find('i.dup-nav').trigger('click');});
	}

	DupPro.Pack.copyText = function(btn, query)
	{
		$(query).select();
		 try {
		   document.execCommand('copy');
		   $(btn).css({color: '#fff', backgroundColor: 'green'});
		   $(btn).text("<?php DUP_PRO_U::_e('Copied to Clipboard!');?>");
		 } catch(err) {
           <?php $alert3->showAlert(); ?>
		 }
	}

	DupPro.Pack.applyFilters = function(btn, type)
	{
		var $btn = $(btn);
		$btn.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Initializing Please Wait...');?>');
		$btn.attr('disabled', 'true');
		$('#dup-build-button').removeAttr('disabled');

		var id = '';
        switch(type){
            case 'large':
                id = '#hb-files-large-result';
                break;
            case 'utf8':
                id = '#hb-files-utf8-result';
                break;
            case 'addon':
                id = '#hb-addon-sites-result';
                break;
        }
		var dirFilters  = [];
		var fileFilters = [];
		$(id + " input[name='dir_paths[]']:checked").each(function()  {dirFilters.push($(this).val());});
		$(id + " input[name='file_paths[]']:checked").each(function() {fileFilters.push($(this).val());});

		var data = {
			action: 'DUP_PRO_CTRL_Package_addQuickFilters',
			nonce: '<?php echo wp_create_nonce('DUP_PRO_CTRL_Package_addQuickFilters'); ?>',
			dir_paths : dirFilters.join(";"),
			file_paths : fileFilters.join(";"),
		};

		$.ajax({
			type: "POST",
			cache: false,
			url: ajaxurl,
			dataType: "json",
			timeout: 100000,
			data: data,
			complete: function() { },
			success:  function() {  DupPro.Pack.reRunScanner();},
			error: function(data) {
				console.log(data);
                <?php $alert4->showAlert(); ?>
			}
		});
	}

	DupPro.Pack.initArchiveFilesData = function(data)
	{
		//TOTAL SIZE
		//var sizeChecks = data.ARC.Status.Size == 'Warn' || data.ARC.Status.Big == 'Warn' ? 'Warn' : 'Good';
		$('#data-arc-status-size').html(DupPro.Pack.setScanStatus(data.ARC.Status.Size));
		$('#data-arc-status-names').html(DupPro.Pack.setScanStatus(data.ARC.Status.Names));
		$('#data-arc-status-unreadablefiles').html(DupPro.Pack.setScanStatus(data.ARC.Status.UnreadableItems));
		$('#data-arc-size1').text(data.ARC.Size || errMsg);
		$('#data-arc-size2').text(data.ARC.Size || errMsg);
		$('#data-arc-files').text(data.ARC.FileCount || errMsg);
		$('#data-arc-dirs').text(data.ARC.DirCount || errMsg);
		$('#data-arc-fullcount').text(data.ARC.FullCount || errMsg);

		//LARGE FILES
		var template = $('#hb-files-large').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('#hb-files-large-result').html(html);

        //ADDON SITES
        var template = $('#hb-addon-sites').html();
        var templateScript = Handlebars.compile(template);
        var html = templateScript(data);
        $('#hb-addon-sites-result').html(html);

		//NAME CHECKS
		var template = $('#hb-files-utf8').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('#hb-files-utf8-result').html(html);

        //UNREADABLE FILES
        var template = $('#unreadable-files').html();
        var templateScript = Handlebars.compile(template);
        var html = templateScript(data);
        $('#unreadable-files-result').html(html);


		//SCANNER DETAILS: Dirs
		var template = $('#hb-filter-file-list').html();
		var templateScript = Handlebars.compile(template);
		var html = templateScript(data);
		$('div.hb-filter-file-list-result').html(html);

		DupPro.UI.loadQtip();

	}
});
</script>
