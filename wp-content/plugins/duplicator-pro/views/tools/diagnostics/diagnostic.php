<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=duplicator-pro-tools&tab=diagnostics'); ?>" method="post">
<?php wp_nonce_field('duplicator_pro_settings_page'); ?>
<input type="hidden" id="dup-settings-form-action" name="action" value="">

<?php if (!empty($action_response)) : ?>
	<div id="message" class="notice notice-success is-dismissible"><p><?php echo $action_response; ?></p>
	<?php if ($_REQUEST['action'] != 'display') : ?>
		<?php if ($_REQUEST['action'] == 'installer') :

			delete_option("duplicator_pro_exe_safe_mode");
			$html = "";

			foreach ($installer_files as $filename => $path) {
				if (is_file($path)) {
					DUP_PRO_IO::deleteFile($path);
				} else if (is_dir($path)) {
					// Extra protection to ensure we only are deleting the installer directory
					if(DUP_PRO_STR::contains($path, 'dup-installer')) {
						if(file_exists("{$path}/archive.cfg")) {
							DUP_PRO_IO::deleteTree($path);
						} else {
							DUP_PRO_LOG::trace("Was going to delete {$path} but archive.cfg doesn't exist!");
						}
					}
					else {
						DUP_PRO_LOG::trace("Attempted to delete $path but it isn't the dup-installer directory!");
					}
				}

				echo (file_exists($path))
					? "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$path}  </div>"
					: "<div class='success'> <i class='fa fa-check'></i> {$txt_not_found} - {$path}	</div>";
			}

			//No way to know exact name of archive file except from installer.
			//The only place where the package can be remove is from installer
			//So just show a message if removing from plugin.
			if (!empty($archive_path)) {
				$path_parts	 = pathinfo($archive_path);
				$path_parts	 = (isset($path_parts['extension'])) ? $path_parts['extension'] : '';
				if ((($path_parts == "zip") || ($path_parts == "daf")) && !is_dir($archive_path)) {
					@unlink($archive_path);
					$html .= (file_exists($archive_path))
						? "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$archive_path}  </div>"
						: "<div class='success'> <i class='fa fa-check'></i> {$txt_not_found} - {$archive_path}	</div>";
				} else {
					$html .= "<div class='failed'>Does not exist or unable to remove archive file.  Please validate that an archive file exists.</div>";
				}
			} else {
				$html .= '<div><br/>It is recommended to remove your archive file from the root of your WordPress install.  This may need to be removed manually if it exists.</div>';
			}

			//Long Installer Check
			if (!empty($long_installer_path) && $long_installer_path != $installer_files['installer.php']) {
				$path_parts	 = pathinfo($long_installer_path);
				$path_parts	 = (isset($path_parts['extension'])) ? $path_parts['extension'] : '';
				if ($path_parts == "php" && !is_dir($long_installer_path)) {
					@unlink($long_installer_path);
					$html .= (file_exists($long_installer_path))
							? "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$txt_found} - {$long_installer_path}  </div>"
							: "<div class='success'> <i class='fa fa-check'></i> {$txt_not_found} - {$long_installer_path}	</div>";
				}
			}

			echo $html;
			?>
			<br/>

			<i>
				<?php DUP_PRO_U::_e('If the installation files did not successfully get removed, then you WILL need to remove them manually') ?>. <br/>
				<?php DUP_PRO_U::_e('Please remove all installation files to avoid leaving open security issues on your server') ?>. <br/><br/>
			</i>
		<?php elseif ($_REQUEST['action'] == 'purge-orphans') :?>
			<?php
			$html = "";

			foreach($orphaned_filepaths as $filepath) {
				@unlink($filepath);
				echo (file_exists($filepath))
					? "<div class='failed'><i class='fa fa-exclamation-triangle'></i> {$filepath}  </div>"
					: "<div class='success'> <i class='fa fa-check'></i> {$filepath} </div>";
			}

			echo $html;
			$orphaned_filepaths		= DUP_PRO_Server::getOrphanedPackageFiles();
			?>
			<br/>

			<i><?php DUP_PRO_U::_e('If any orphaned files didn\'t get removed then delete them manually') ?>. <br/><br/></i>
		<?php endif; ?>
	<?php endif; ?>
	</div>

<?php endif; ?>



<?php

if(isset($_GET['safe_mode'])){
	
	$safe_title = DUP_PRO_U::__('This site has been successfully migrated!');
	$safe_msg = DUP_PRO_U::__('Please test the entire site to validate the migration process!');

	switch($_GET['safe_mode']){

		//safe_mode basic
		case 1:
			$safe_msg = DUP_PRO_U::__('NOTICE: Safe mode (Basic) was enabled during install, be sure to re-enable all your plugins.');
		break;

		//safe_mode advance
		case 2:
			$safe_msg = DUP_PRO_U::__('NOTICE: Safe mode (Advanced) was enabled during install, be sure to re-enable all your plugins.');

			$temp_theme = null;
			$active_theme = wp_get_theme();
			$available_themes = wp_get_themes();
			foreach($available_themes as $theme){
				if($temp_theme == null && $theme->stylesheet != $active_theme->stylesheet){
					$temp_theme = array('stylesheet' => $theme->stylesheet, 'template' => $theme->template);
					break;
				}
			}

			if($temp_theme != null){
				//switch to another theme then backto default
				switch_theme($temp_theme['template'], $temp_theme['stylesheet']);
				switch_theme($active_theme->template, $active_theme->stylesheet);
			}

		break;
	}


	if (! DUP_PRO_Server::hasInstallFiles()) {
		echo  "<div class='notice notice-success is-dismissible cleanup-notice'><p><b class='title'><i class='fa fa-check-circle'></i> {$safe_title}</b> "
			. "<div class='notice-safemode'>{$safe_msg}</p></div></div>";
	}

}

include_once 'inc.data.php';
include_once 'inc.settings.php';
include_once 'inc.validator.php';
include_once 'inc.phpinfo.php';
?>

</form>
<?php
	$confirm1 = new DUP_PRO_UI_Dialog();
	$confirm1->title			 = DUP_PRO_U::__('Are you sure, you want to delete?');
	$confirm1->message			 = DUP_PRO_U::__('Delete this option value.');
	$confirm1->progressText      = DUP_PRO_U::__('Removing, Please Wait...');
	$confirm1->jsCallback		 = 'DupPro.Settings.DeleteThisOption(this)';
	$confirm1->initConfirm();

    $confirm2 = new DUP_PRO_UI_Dialog();
    $confirm2->title            = DUP_PRO_U::__('Do you want to Continue?');
	$confirm2->message          = DUP_PRO_U::__('This will run the scan validation check. This may take several minutes.');
    $confirm2->progressText     = DUP_PRO_U::__('Please Wait...');
	$confirm2->jsCallback		= 'DupPro.Tools.RecursionRun()';
	$confirm2->initConfirm();


    $confirm3 = new DUP_PRO_UI_Dialog();
    $confirm3->title            = DUP_PRO_U::__('This process will remove all build cache files.');
	$confirm3->message          = DUP_PRO_U::__('Be sure no packages are currently building or else they will be cancelled.');
    $confirm3->progressText     = $confirm1->progressText;
	$confirm3->jsCallback		= 'DupPro.Tools.ClearBuildCacheRun()';
	$confirm3->initConfirm();
?>
<script>
jQuery(document).ready(function ($) {

	DupPro.Settings.DeleteOption = function (anchor) {
		var key = $(anchor).text(),
            text = '<?php DUP_PRO_U::_e("Delete this option value"); ?> [' + key + '] ?';
        <?php $confirm1->showConfirm(); ?>
        $("#<?php echo $confirm1->getID(); ?>-confirm").attr('data-key', key);
        $("#<?php echo $confirm1->getID(); ?>_message").html(text);

	};

    DupPro.Settings.DeleteThisOption = function(e){
        var key = $(e).attr('data-key');
        jQuery('#dup-settings-form-action').val(key);
		jQuery('#dup-settings-form').submit();
    }

	DupPro.Tools.removeOrphans = function () {
		window.location = '?page=duplicator-pro-tools&tab=diagnostics&action=purge-orphans';
	};

	DupPro.Tools.removeInstallerFiles = function () {
		window.location = '<?php echo "?page=duplicator-pro-tools&tab=diagnostics&action=installer&package={$archive_file}&installer_name={$long_installer_path}"; ?>';
	};


	DupPro.Tools.ClearBuildCache = function () {
		<?php $confirm3->showConfirm(); ?>
	};

    DupPro.Tools.ClearBuildCacheRun = function(){
        window.location = '?page=duplicator-pro-tools&tab=diagnostics&action=tmp-cache';
    }


	DupPro.Tools.Recursion = function()
	{
		<?php $confirm2->showConfirm(); ?>
	}

    DupPro.Tools.RecursionRun = function(){
        jQuery('#dup-settings-form-action').val('duplicator_recursion');
		jQuery('#dup-settings-form').submit();
    }

	<?php
		if ($scan_run) {
			echo "$('#duplicator-scan-results-1').html($('#duplicator-scan-results-2').html())";
		}
	?>

});
</script>