<?php
defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/net/class.u.gdrive.php');

if (DUP_PRO_U::PHP56()) {
    require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/net/class.u.onedrive.php');
}

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.storage.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/lib/DropPHP/DropboxV2Client.php');

global $wp_version;
global $wpdb;

$global			= DUP_PRO_Global_Entity::get_instance();
$nonce_action	= 'duppro-storage-edit';
$was_updated	= false;
$storage_id		= isset($_REQUEST['storage_id']) ? esc_html($_REQUEST['storage_id']) : -1;
$storage		= ($storage_id == -1) ?	new DUP_PRO_Storage_Entity() : DUP_PRO_Storage_Entity::get_by_id($storage_id);

$_REQUEST['_sftp_storage_folder'] = isset($_REQUEST['_sftp_storage_folder']) ? $_REQUEST['_sftp_storage_folder'] : '';

if (isset($_REQUEST['action'])) {
	check_admin_referer($nonce_action);

	if ($_REQUEST['action'] == 'save') {
		$gdrive_error_message = NULL;

		if ($_REQUEST['storage_type'] == DUP_PRO_Storage_Types::GDrive) {
			if ($storage->gdrive_authorization_state == DUP_PRO_GDrive_Authorization_States::Unauthorized) {
				if (!empty($_REQUEST['gdrive-auth-code'])) {
					try {
						$google_client_auth_code	 = $_REQUEST['gdrive-auth-code'];
						$google_client				 = DUP_PRO_GDrive_U::get_raw_google_client();
						$gdrive_token_pair_string	 = $google_client->authenticate($google_client_auth_code);

						$gdrive_token_pair = json_decode($gdrive_token_pair_string, true);

						DUP_PRO_LOG::traceObject('Token pair from authorization', $gdrive_token_pair);

						if (isset($gdrive_token_pair['refresh_token'])) {
							$storage->gdrive_refresh_token			 = $gdrive_token_pair['refresh_token'];
							$storage->gdrive_access_token_set_json	 = $google_client->getAccessToken(); //$gdrive_token_pair['access_token'];

							DUP_PRO_LOG::trace("Set refresh token to {$storage->gdrive_refresh_token}");
							DUP_PRO_LOG::trace("Set access token set to {$storage->gdrive_access_token_set_json}");

							$storage->gdrive_authorization_state = DUP_PRO_GDrive_Authorization_States::Authorized;
							$storage->save();
						} else {
							$gdrive_error_message = DUP_PRO_U::__("Couldn't connect. Google Drive refresh token not found.");
						}
					} catch (Exception $ex) {
						$gdrive_error_message = sprintf(DUP_PRO_U::__('Problem retrieving Google refresh and access tokens [%s] Please try again!'), $ex->getMessage());
					}
				}
			}
		}

		$dropbox_error_message = NULL;
		if ($_REQUEST['storage_type'] == DUP_PRO_Storage_Types::Dropbox) {
			if ($storage->dropbox_authorization_state == DUP_PRO_Dropbox_Authorization_States::Unauthorized) {
				if (!empty($_REQUEST['dropbox-auth-code'])) {
					try {
						$dropbox_client_auth_code	 = $_REQUEST['dropbox-auth-code'];
						$dropbox_client				 = DUP_PRO_Storage_Entity::get_raw_dropbox_client(false);
						$v2_access_token			 = $dropbox_client->authenticate($dropbox_client_auth_code);

						if ($v2_access_token !== false) {
							$storage->dropbox_v2_access_token = $v2_access_token;

							DUP_PRO_LOG::trace("Set dorpbox access token to {$storage->dropbox_v2_access_token}");

							$storage->dropbox_authorization_state = DUP_PRO_Dropbox_Authorization_States::Authorized;
							$storage->save();
						} else {
							$dropbox_error_message = DUP_PRO_U::__("Couldn't connect. Dropbox access token not found.");
						}
					} catch (Exception $ex) {
						$dropbox_error_message = sprintf(DUP_PRO_U::__('Problem retrieving Dropbox access token [%s] Please try again!'), $ex->getMessage());
					}
				}
			}
		}

        if ($_REQUEST['storage_type'] == DUP_PRO_Storage_Types::OneDrive) {
            if($storage->onedrive_authorization_state == DUP_PRO_OneDrive_Authorization_States::Unauthorized){
                if(!empty($_REQUEST['onedrive-auth-code'])){
                    if($_REQUEST['onedrive-is-business']){
                        $onedrive_auth_client = DUP_PRO_Onedrive_U::get_onedrive_client_from_state(
                            (object)array(
                                'redirect_uri' => DUP_PRO_OneDrive_Config::ONEDRIVE_REDIRECT_URI,
                                'token' => null
                            )
                        );
                        $onedrive_auth_client->setBusinessMode();
                        $onedrive_auth_client->obtainAccessToken(DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_SECRET,
                            array(
                                'code' => $_REQUEST['onedrive-auth-code'],
                                'resource' => DUP_PRO_OneDrive_Config::MICROSOFT_GRAPH_ENDPOINT,
                                'grant_type' => 'authorization_code'
                            )
                        );
                        DUP_PRO_Log::traceObject("Client State here", $onedrive_auth_client->getState());
                        $onedrive_info = $onedrive_auth_client->getServiceInfo();
                        $onedrive_auth_client->obtainAccessToken(DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_SECRET,
                            array(
                                'resource' => $onedrive_info['resource_id'],
                                'refresh_token' => $onedrive_auth_client->getState()->token->data->refresh_token,
                                'grant_type' => 'refresh_token'
                            )
                        );
                        $storage->onedrive_endpoint_url = $onedrive_info['endpoint_url'];
                        $storage->onedrive_resource_id  = $onedrive_info['resource_id'];
                    }else{
                        $onedrive_auth_client = DUP_PRO_Onedrive_U::get_onedrive_client_from_state(
                            (object)array(
                                'redirect_uri' => DUP_PRO_OneDrive_Config::ONEDRIVE_REDIRECT_URI,
                                'token' => null
                            )
                        );
                        $onedrive_auth_client->obtainAccessToken(DUP_PRO_OneDrive_Config::ONEDRIVE_CLIENT_SECRET,
                            array(
                                'code' => $_REQUEST['onedrive-auth-code'],
                                'grant_type' => 'authorization_code'
                            )
                        );
                    }
                    $onedrive_client_state = $onedrive_auth_client->getState();
                    $storage->storage_type                  = DUP_PRO_Storage_Types::OneDrive;
                    $storage->onedrive_access_token         = $onedrive_client_state->token->data->access_token;
                    $storage->onedrive_refresh_token        = $onedrive_client_state->token->data->refresh_token;
                    $storage->onedrive_user_id              = $onedrive_client_state->token->data->user_id;
                    $storage->onedrive_token_obtained       = $onedrive_client_state->token->obtained;
                    $storage->onedrive_authorization_state  = DUP_PRO_OneDrive_Authorization_States::Authorized;
                    $storage->save();
                }
            }
        }

		// Checkboxes don't set post values when off so have to manually set these
		$storage->local_storage_folder		 = trim(DUP_PRO_U::safePath(stripslashes($_REQUEST['_local_storage_folder'])));
		$storage->local_filter_protection	 = isset($_REQUEST['_local_filter_protection']);
		$storage->ftp_passive_mode		 = isset($_REQUEST['_ftp_passive_mode']);
		$storage->ftp_ssl				 = isset($_REQUEST['_ftp_ssl']);
		$storage->ftp_storage_folder	 = DUP_PRO_U::safePath(stripslashes($_REQUEST['_ftp_storage_folder']));
		$storage->sftp_storage_folder	 = DUP_PRO_U::safePath(stripslashes($_REQUEST['_sftp_storage_folder']));
		$storage->dropbox_storage_folder = DUP_PRO_U::safePath(stripslashes($_REQUEST['_dropbox_storage_folder']));
		$storage->gdrive_storage_folder	 = DUP_PRO_U::safePath(stripslashes($_REQUEST['_gdrive_storage_folder']));
		$storage->s3_storage_folder		 = DUP_PRO_U::safePath(stripslashes($_REQUEST['_s3_storage_folder']));
		if($storage->onedrive_storage_folder != DUP_PRO_U::safePath(stripslashes($_REQUEST['_onedrive_storage_folder']))){
            $storage->onedrive_storage_folder = DUP_PRO_U::safePath(stripslashes($_REQUEST['_onedrive_storage_folder']));
            $storage->onedrive_storage_folder_id = '';
        }

		$storage->set_post_variables($_REQUEST);
		$storage->save();

		$local_folder_created		 = false;
		$local_folder_creation_error = false;

		if ($storage->storage_type == DUP_PRO_Storage_Types::Local) {
			if ((trim($storage->local_storage_folder) != '') && (file_exists($storage->local_storage_folder) == false)) {
				if (@mkdir($storage->local_storage_folder, 0755, true)) {
					$local_folder_created = true;
				} else {
					$local_folder_creation_error = true;
				}
			}
		}

		$was_updated = true;
	} else if ($_REQUEST['action'] == 'copy-storage') {
		$source_id = $_REQUEST['duppro-source-storage-id'];
		if ($source_id != -1) {
			$storage->copy_from_source_id($source_id);
			$storage->save();
		}
	} else if ($_REQUEST['action'] == 'gdrive-revoke-access') {
		$google_client = DUP_PRO_GDrive_U::get_raw_google_client();

		if (!$google_client->revokeToken($storage->gdrive_refresh_token)) {
			DUP_PRO_LOG::trace("Problem revoking Google Drive refresh token");
		}

		$gdrive_access_token = json_decode($storage->gdrive_access_token_set_json)->access_token;

		if (!$google_client->revokeToken($gdrive_access_token)) {
			DUP_PRO_LOG::trace("Problem revoking Google Drive access token");
		}

		$storage->gdrive_access_token_set_json	 = '';
		$storage->gdrive_refresh_token			 = '';
		$storage->gdrive_authorization_state	 = DUP_PRO_GDrive_Authorization_States::Unauthorized;
		$storage->save();
	} else if ($_REQUEST['action'] == 'dropbox-revoke-access') {
		$dropbox_client = $storage->get_dropbox_client();
		if ($dropbox_client->revokeToken() === false) {
			DUP_PRO_LOG::trace("Problem revoking Dropbox access token");
		}

		$storage->dropbox_access_token			 = '';
		$storage->dropbox_access_token_secret	 = '';
		$storage->dropbox_v2_access_token		 = '';
		$storage->dropbox_authorization_state	 = DUP_PRO_Dropbox_Authorization_States::Unauthorized;
		$storage->save();
	}else if($_REQUEST['action'] == 'onedrive-revoke-access'){

        $storage->storage_type                  = DUP_PRO_Storage_Types::Local;
        $storage->onedrive_access_token         = '';
        $storage->onedrive_refresh_token        = '';
        $storage->onedrive_user_id              = '';
        $storage->onedrive_token_obtained       = '';
        $storage->onedrive_authorization_state  = DUP_PRO_OneDrive_Authorization_States::Unauthorized;
        $storage->save();
    }
}

if ($storage->dropbox_authorization_state == DUP_PRO_Dropbox_Authorization_States::Authorized) {
	$dropbox		 = $storage->get_dropbox_client();
	$account_info	 = $dropbox->GetAccountInfo();
}

if ($storage->onedrive_authorization_state == DUP_PRO_OneDrive_Authorization_States::Authorized) {
    $onedrive = $storage->get_onedrive_client();
    $storage->get_onedrive_storage_folder();
    $onedrive_account_info = $onedrive->fetchAccountInfo($storage->onedrive_storage_folder_id);
}

if (DUP_PRO_U::PHP53()) {
	if ($storage->gdrive_authorization_state == DUP_PRO_GDrive_Authorization_States::Authorized) {
		try {
			$google_client		 = $storage->get_full_google_client();
			$gdrive_user_info	 = DUP_PRO_GDrive_U::get_user_info($google_client);
		} catch (Exception $e) {
			// This is an oddball recommendation - don't queue it in system global entity
			$error_text	 = 'Error retrieving  Google Client'.$e->getMessage();
			$fix_text	 = "Delete the Google endpoint and recreate.";
			echo DUP_PRO_U::__("$error_text: ** RECOMMENDATION: $fix_text");
			die;
		}
	} else {
		$google_client = DUP_PRO_GDrive_U::get_raw_google_client();
	}
}

$storages = DUP_PRO_Storage_Entity::get_all();
$storage_count = count($storages);
$txt_auth_note = DUP_PRO_U::__('Note: Clicking the button below will open a new tab/window. Please be sure your browser does not block popups. If a new tab/window does not  '
							      . 'open check your browsers address bar to allow popups from this URL.');
?>

<style>
    table.dpro-edit-toolbar select {float:left}
    #dup-storage-form input[type="text"], input[type="password"] { width: 250px;}
	#dup-storage-form input#name {width:100%; max-width: 500px}
    #dup-storage-form #ftp_timeout {width:100px !important} 
	#dup-storage-form input#_local_storage_folder, input#_ftp_storage_folder {width:100% !important; max-width: 500px}
    .provider { display:none; }
    .stage {display:none; }
	td.dpro-sub-title {padding:0; margin: 0}
	td.dpro-sub-title b{padding:20px 0; margin: 0; display:block; font-size:1.25em;}
	input.dpro-storeage-folder-path {width: 450px !important}
	small.dpro-store-type-notice {display:block; padding-left:15px; font-size:12px !important; line-height:18px; color: maroon}
	
	/*Common */
	#s3_max_files, #dropbox_max_files, #ftp_max_files, #local_max_files, #gdrive_max_files {width:50px !important}
	
	/*DropBox*/
    td.dropbox-authorize {line-height:30px; padding-top:0px !important;}
    div#dropbox-account-info label {display: inline-block; width:100px; font-weight: bold} 
	button#dpro-dropbox-connect-btn {margin:10px 0}
	div.auth-code-popup-note {width:525px; font-size:11px; padding: 0; margin:-5px 0 10px 10px; line-height: 16px; font-style: italic}
	
	/*Google Drive */
	td.gdrive-authorize {line-height:25px}
	div#dpro-gdrive-steps {display:none}
	div#dpro-gdrive-steps div {margin: 0 0 20px 0}
	div#dpro-gdrive-connect-progress {display:none}
</style>

<form id="dup-storage-form" action="<?php echo $edit_storage_url; ?>" method="post" data-parsley-ui-enabled="true" target="_self">
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="dup-storage-form-action" name="action" value="save">
<input type="hidden" name="storage_id" value="<?php echo $storage->id; ?>">

<?php if(false): ?>
<input type="hidden" id="dropbox_access_token" name="dropbox_access_token" value="<?php echo $storage->dropbox_access_token ?>">
<input type="hidden" id="dropbox_access_token_secret" name="dropbox_access_token_secret" value="<?php echo $storage->dropbox_access_token_secret ?>">
<input type="hidden" id="dropbox_authorization_state" name="dropbox_authorization_state" value="<?php echo $storage->dropbox_authorization_state ?>">
<?php endif;//end false ?>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
<tr>
	<td>
		<?php  if ($storage_count > 0) : ?>
			<select name="duppro-source-storage-id">
				<option value="-1" selected="selected"><?php _e("Copy From"); ?></option>
				<?php
				foreach ($storages as $copy_storage) {
					echo ($copy_storage->id != $storage->id) ? "<option value='{$copy_storage->id}'>{$copy_storage->name}</option>" : '';
				}
				?>
			</select>
			<input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Storage.Copy()">
		<?php else :  ?>
			<select disabled="disabled"><option value="-1" selected="selected"><?php _e("Copy From"); ?></option></select>
			<input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" disabled="disabled">
		<?php endif; ?>
	</td>
	<td>
		<div class="btnnav">
			<a href="<?php echo $storage_tab_url; ?>" class="add-new-h2"> <i class="fa fa-database"></i> <?php DUP_PRO_U::_e('Providers'); ?></a>
			<?php if ($storage_id == -1) : ?>
				<span><?php DUP_PRO_U::_e('Add New') ?></span>
			<?php else : ?>
				<a href="admin.php?page=duplicator-pro-storage&tab=storage&inner_page=edit" class="add-new-h2"><?php DUP_PRO_U::_e("Add New"); ?></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
</table>
<hr class="dpro-edit-toolbar-divider"/>

<!-- ====================
SUB-TABS -->
<?php 
	if ($was_updated) {
		if ($gdrive_error_message != NULL) {
			echo "<div id='message' class='notice notice-error is-dismissible'><p><i class='fa fa-exclamation-triangle'></i> $gdrive_error_message </p></div>";
		} else if ($local_folder_created) {
			$update_message = sprintf(DUP_PRO_U::__('Storage Provider Updated - Folder %1$s was created'), $storage->local_storage_folder);
			echo "<div class='notice notice-success is-dismissible dpro-wpnotice-box'><p>$update_message</p></div>";
		} else {
			if ($local_folder_creation_error) {
				$update_message = sprintf(DUP_PRO_U::__('Storage Provider Updated - Unable to create folder %1$s'), $storage->local_storage_folder);
				echo "<div class='notice notice-success is-dismissible dpro-wpnotice-box'><p><i class='fa fa-exclamation-triangle'></i> $update_message </p></div>";
			} else {
				$update_message = DUP_PRO_U::__('Storage Provider Updated');
				echo "<div class='notice notice-success is-dismissible dpro-wpnotice-box'><p>$update_message</p></div>";
			}
		}
	}
?>

<table class="form-table top-entry">
<tr valign="top">
	<th scope="row"><label><?php DUP_PRO_U::_e("Name"); ?></label></th>
	<td>
		<input data-parsley-errors-container="#name_error_container" type="text" id="name" name="name" value="<?php echo $storage->name; ?>" autocomplete="off" />
		<div id="name_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr valign="top">
	<th scope="row"><label><?php DUP_PRO_U::_e("Notes"); ?></label></th>
	<td><textarea id="notes" name="notes" style="width:100%; max-width: 500px"><?php echo $storage->notes; ?></textarea></td>
</tr>
<tr valign="top">
	<th scope="row"><label><?php DUP_PRO_U::_e("Type"); ?></label></th>
	<td>
		<select id="change-mode" name="storage_type" onchange="DupPro.Storage.ChangeMode()">
			<?php if(DUP_PRO_U::PHP53()) : ?>
				<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::S3); ?> value="<?php echo DUP_PRO_Storage_Types::S3; ?>"><?php DUP_PRO_U::_e("Amazon S3"); ?></option>
			<?php endif;?>
			<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::Dropbox); ?> value="<?php echo DUP_PRO_Storage_Types::Dropbox; ?>"><?php DUP_PRO_U::_e("Dropbox"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::FTP); ?> value="<?php echo DUP_PRO_Storage_Types::FTP; ?>"><?php DUP_PRO_U::_e("FTP"); ?></option>
			<?php if(DUP_PRO_U::PHP55() && extension_loaded('gmp')) : ?>
				<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::SFTP); ?> value="<?php echo DUP_PRO_Storage_Types::SFTP; ?>"><?php DUP_PRO_U::_e("SFTP"); ?></option>
			<?php endif;?>
			<?php if(DUP_PRO_U::PHP53()) : ?>
				<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::GDrive); ?> value="<?php echo DUP_PRO_Storage_Types::GDrive; ?>"><?php DUP_PRO_U::_e("Google Drive"); ?></option>
			<?php endif;?>
			<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::Local); ?> value="<?php echo DUP_PRO_Storage_Types::Local; ?>"><?php DUP_PRO_U::_e("Local Server"); ?></option>
			<?php if(DUP_PRO_U::PHP56()) : ?>
				<option <?php DUP_PRO_UI::echoSelected($storage->storage_type == DUP_PRO_Storage_Types::OneDrive); ?> value="<?php echo DUP_PRO_Storage_Types::OneDrive; ?>"><?php DUP_PRO_U::_e("OneDrive"); ?></option>
			<?php endif;?>
		</select>
		<small class="dpro-store-type-notice">
			<?php
			if (DUP_PRO_U::PHP53() == false) {
				echo sprintf(DUP_PRO_U::__('Google Drive &amp; Amazon S3 requires PHP 5.3.2+. This server is running PHP (%s).'), PHP_VERSION) . '<br/>';
			}
			if (DUP_PRO_U::PHP55() == false) {
				echo sprintf(DUP_PRO_U::__('SFTP requires PHP 5.5.2+. This server is running PHP (%s).'), PHP_VERSION) . '<br/>';
			}
			if (!extension_loaded('gmp')) {
				echo DUP_PRO_U::__('SFTP requires the <a href="http://php.net/manual/en/book.gmp.php" target="_blank">gmp extension</a>. Please contact your host to install.') . '<br/>';
			}
			if (DUP_PRO_U::PHP56() == false) {
				echo sprintf(DUP_PRO_U::__('OneDrive requires PHP 5.6+. This server is running PHP (%s).'), PHP_VERSION). '<br/>';
			}
			?>
		</small>
	</td>
</tr>
</table> <hr size="1" />


<!-- ===============================
AMAZON S3 PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::S3 ?>" class="form-table provider" >
<tr>
	<td colspan="2" style="padding-left:0">
		<i><?php DUP_PRO_U::_e("Amazon S3 Setup Guide: <a target='_blank' href='https://snapcreek.com/duplicator/docs/https://snapcreek.com/duplicator/docs/amazon-s3-step-by-step/'>Step-by-Step</a> and <a href='https://snapcreek.com/duplicator/docs/amazon-s3-policy-setup/' target='_blank'>User Bucket Policy</a>"); ?></i>
	</td>
</tr>
<tr>
	<td class="dpro-sub-title" colspan="2">
		<b><?php DUP_PRO_U::_e("Credentials"); ?></b>
	</td>
</tr>
<tr>
	<th scope="row"><label for="s3_access_key"><?php DUP_PRO_U::_e("Access Key"); ?></label></th>
	<td>
		<input id="s3_access_key" name="s3_access_key" data-parsley-errors-container="#s3_access_key_error_container" type="text" autocomplete="off" value="<?php echo $storage->s3_access_key ?>">
		<div id="s3_access_key_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row">
		<label for="s3_secret_key"><?php DUP_PRO_U::_e("Secret Key"); ?></label>
	</th>

	<td>
		<input id="s3_secret_key" name="s3_secret_key" data-parsley-errors-container="#s3_secret_key_error_container" type="password" autocomplete="off" value="<?php echo $storage->s3_secret_key ?>">
		<div id="s3_secret_key_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<td class="dpro-sub-title" colspan="2"><b><?php DUP_PRO_U::_e("Settings"); ?></b></td>
</tr>
<tr>
	<th scope="row"><label for="s3_bucket"><?php DUP_PRO_U::_e("Bucket"); ?></label></th>
	<td>
		<input id="s3_bucket" name="s3_bucket" type="text" value="<?php echo $storage->s3_bucket; ?>">
		<p><i><?php DUP_PRO_U::_e("S3 Bucket where you want to save the backups."); ?></i></p>
	</td>
</tr>
<tr>
	<th scope="row"><label for="s3_region"><?php DUP_PRO_U::_e("Region"); ?></label></th>
	<td>
		<select id="s3_region" name="s3_region">
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'us-east-1'); ?> value="us-east-1"><?php DUP_PRO_U::_e("US East (N. Virginia)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'us-east-2'); ?> value="us-east-2"><?php DUP_PRO_U::_e("US East (Ohio)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'us-west-1'); ?> value="us-west-1"><?php DUP_PRO_U::_e("US West (N. California)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'us-west-2'); ?> value="us-west-2"><?php DUP_PRO_U::_e("US West (Oregon)"); ?></option>			
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-south-1'); ?> value="ap-south-1"><?php DUP_PRO_U::_e("Asia Pacific (Mumbai)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-northeast-2'); ?> value="ap-northeast-2"><?php DUP_PRO_U::_e("Asia Pacific (Seoul)"); ?></option>
            <option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-northeast-3'); ?> value="ap-northeast-3"><?php DUP_PRO_U::_e("Asia Pacific (Osaka-Local)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-southeast-1'); ?> value="ap-southeast-1"><?php DUP_PRO_U::_e("Asia Pacific (Singapore)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-southeast-2'); ?> value="ap-southeast-2"><?php DUP_PRO_U::_e("Asia Pacific (Sydney)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ap-northeast-1'); ?> value="ap-northeast-1"><?php DUP_PRO_U::_e("Asia Pacific (Tokyo)"); ?></option>
            <option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'ca-central-1'); ?> value="ca-central-1"><?php DUP_PRO_U::_e("Canada (Central)"); ?></option>
            <option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'cn-north-1'); ?> value="cn-north-1"><?php DUP_PRO_U::_e("China (Beijing)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'eu-central-1'); ?> value="eu-central-1"><?php DUP_PRO_U::_e("EU (Frankfurt)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'eu-west-1'); ?> value="eu-west-1"><?php DUP_PRO_U::_e("EU (Ireland)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'eu-west-2'); ?> value="eu-west-2"><?php DUP_PRO_U::_e("EU (London)"); ?></option>
            <option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'eu-west-3'); ?> value="eu-west-3"><?php DUP_PRO_U::_e("EU (Paris)"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_region == 'sa-east-1'); ?> value="sa-east-1"><?php DUP_PRO_U::_e("South America (Sao Paulo)"); ?></option>
		</select>
	</td>
</tr>
<tr>
	<th scope="row"><label for="_s3_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<input id="_s3_storage_folder" name="_s3_storage_folder" type="text" value="<?php echo $storage->s3_storage_folder; ?>">
		<p><i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i></p>
	</td>
</tr>
<tr>
	<th scope="row"><label for="s3_storage_class"><?php DUP_PRO_U::_e("Storage Class"); ?></label></th>
	<td>
		<select id="s3_storage_class" name="s3_storage_class">
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_storage_class == 'REDUCED_REDUNDANCY'); ?> value="REDUCED_REDUNDANCY"><?php DUP_PRO_U::_e("Reduced Redundancy"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_storage_class == 'STANDARD'); ?> value="STANDARD"><?php DUP_PRO_U::_e("Standard"); ?></option>
			<option <?php DUP_PRO_UI::echoSelected($storage->s3_storage_class == 'STANDARD_IA'); ?> value="STANDARD_IA"><?php DUP_PRO_U::_e("Standard IA"); ?></option>
		</select>
	</td>
</tr>
<tr>
	<th scope="row"><label for="s3_max_files"><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="s3_max_files">
			<input id="s3_max_files" name="s3_max_files" data-parsley-errors-container="#s3_max_files_error_container" type="text" value="<?php echo $storage->s3_max_files; ?>">
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?><br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit."); ?></i>
		</label>
		<div id="s3_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<button class="button button_s3_test" id="button_s3_send_file_test" type="button" onclick="DupPro.Storage.S3.SendFileTest();">
		   <i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test S3 Connection'); ?>
		</button>
		<p><i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?></i></p>
	</td>
</tr>
</table>

<!-- ===============================
DROP-BOX PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::Dropbox ?>" class="form-table provider" >
<tr>
	<th scope="row"><label><?php DUP_PRO_U::_e("Authorization"); ?></label></th>
	<td class="dropbox-authorize">
		<div class='authorization-state' id="state-unauthorized">
			<!-- CONNECT -->
			<button id="dpro-dropbox-connect-btn" type="button" class="button button-large" onclick="DupPro.Storage.Dropbox.DropboxGetAuthUrl();">
				<i class="fa fa-plug"></i> <?php DUP_PRO_U::_e('Connect to Dropbox'); ?>
				<img src="<?php echo DUPLICATOR_PRO_IMG_URL ?>/dropbox-24.png" style='vertical-align: middle; margin:-2px 0 0 3px; height:18px; width:18px' />
			</button>
		</div>

		<div class='authorization-state' id="state-waiting-for-request-token">
			<div style="padding:10px">
				<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Getting Dropbox request token'); ?>...
			</div>
		</div>

		<div class='authorization-state' id="state-waiting-for-auth-button-click">
			<!-- STEP 2 -->
			<b><?php DUP_PRO_U::_e("Step 1:"); ?></b>&nbsp;
			<?php DUP_PRO_U::_e(' Duplicator needs to authorize at the Dropbox.com website.'); ?>
			<div class="auth-code-popup-note">
				<?php echo $txt_auth_note ?>
			</div>
			<button id="auth-redirect" type="button" class="button button-large" onclick="DupPro.Storage.Dropbox.OpenAuthPage(); return false;">
				<i class="fa fa-user"></i> <?php DUP_PRO_U::_e('Authorize Dropbox'); ?>
			</button>
			<br/><br/>

			<div id="dropbox-auth-code-area">
				<b><?php DUP_PRO_U::_e('Step 2:'); ?></b> <?php DUP_PRO_U::_e("Paste code from Dropbox authorization page."); ?> <br/>
				<input style="width:400px" id="dropbox-auth-code" name="dropbox-auth-code" />
			</div>

			<!-- STEP 3 -->
			<b><?php DUP_PRO_U::_e("Step 3:"); ?></b>&nbsp;
			<?php DUP_PRO_U::_e('Finalize Dropbox validation by clicking the "Finalize Setup" button.'); ?>
			<br/>
			<button type="button" class="button" onclick="DupPro.Storage.Dropbox.FinalizeSetup(); return false;"><i class="fa fa-check-square"></i> <?php DUP_PRO_U::_e('Finalize Setup'); ?></button>
		</div>

		<div class='authorization-state' id="state-waiting-for-access-token">
			<div><i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Performing final authorization...Please wait'); ?></div>
		</div>

		<div class='authorization-state' id="state-authorized" style="margin-top:-5px">
			<?php if ($storage->dropbox_authorization_state == DUP_PRO_Dropbox_Authorization_States::Authorized) : ?>
				<h3>
					<img src="<?php echo DUPLICATOR_PRO_IMG_URL ?>/dropbox-24.png" style='vertical-align: bottom; margin-bottom: 5px' />
					<?php DUP_PRO_U::_e('Dropbox Account'); ?><br/>
					<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Duplicator has been authorized to access this user\'s Dropbox account'); ?></i>
				</h3>
				<div id="dropbox-account-info">
					<label><?php DUP_PRO_U::_e('Name'); ?>:</label>
					<?php echo $account_info->name->display_name; ?><br/>

					<label><?php DUP_PRO_U::_e('Email'); ?>:</label>
					<?php echo $account_info->email; ?>
				</div>
			<?php endif; ?>
			<br/>

			<button type="button" class="button button-large" onclick='DupPro.Storage.Dropbox.CancelAuthorization();'>
				<?php DUP_PRO_U::_e('Cancel Authorization'); ?>
			</button><br/>
			<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Disassociates storage provider with the Dropbox account. Will require re-authorization.'); ?> </i>
		</div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="_dropbox_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<b>//Dropbox/Apps/Duplicator Pro/</b>
		<input id="_dropbox_storage_folder" name="_dropbox_storage_folder" type="text" value="<?php echo $storage->dropbox_storage_folder; ?>" class="dpro-storeage-folder-path" />
		<p><i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i></p>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="dropbox_max_files">
			<input data-parsley-errors-container="#dropbox_max_files_error_container" id="dropbox_max_files" name="dropbox_max_files" type="text" value="<?php echo $storage->dropbox_max_files; ?>" maxlength="4">
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?> <br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit."); ?></i>
		</label>
		<div id="dropbox_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<button class="button button_dropbox_test" id="button_dropbox_send_file_test" type="button" onclick="DupPro.Storage.Dropbox.SendFileTest();">
			<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e('Test Dropbox Connection'); ?>
		</button>
		<p><i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?></i></p>
	</td>
</tr>
</table>	

<!-- ===============================
ONE-DRIVE PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::OneDrive ?>" class="form-table provider" >
<tr>
	<th scope="row"><label><?php DUP_PRO_U::_e("Authorization"); ?></label></th>
	<td class="onedrive-authorize">
		<?php if ($storage->onedrive_authorization_state == DUP_PRO_OneDrive_Authorization_States::Unauthorized) : ?>
		<div class='onedrive-authorization-state' id="onedrivestate-unauthorized">
			<!-- CONNECT -->
			<button id="dpro-onedrive-connect-btn" type="button" class="button button-large" onclick="DupPro.Storage.OneDrive.GetAuthUrl(); return false;">
				<i class="fa fa-plug"></i> <?php DUP_PRO_U::_e('Connect to OneDrive'); ?>
			</button>
            <button id="dpro-onedrive-business-connect-btn" type="button" class="button button-large" onclick="DupPro.Storage.OneDrive.GetAuthUrl(1); return false;">
                <i class="fa fa-plug"></i> <?php DUP_PRO_U::_e('Connect to OneDrive for Business'); ?>
            </button>

            <div class='onedrive-auth-container' style="display: none;">
                <!-- STEP 2 -->
                <b><?php DUP_PRO_U::_e("Step 1:"); ?></b>&nbsp;
                <?php DUP_PRO_U::_e(' Duplicator needs to authorize at OneDrive.'); ?>
               	<div class="auth-code-popup-note" style="margin-top:1px">
					<?php echo $txt_auth_note ?>
				</div>
                <button id="auth-redirect" type="button" class="button button-large" onclick="DupPro.Storage.OneDrive.OpenAuthPage(); return false;">
                    <i class="fa fa-user"></i> <?php DUP_PRO_U::_e('Authorize Onedrive'); ?>
                </button>
                <br/><br/>

                <div id="onedrive-auth-container">
                    <b><?php DUP_PRO_U::_e('Step 2:'); ?></b> <?php DUP_PRO_U::_e("Paste code from OneDrive authorization page."); ?> <br/>
                    <input style="width:400px" id="onedrive-auth-code" name="onedrive-auth-code" />
                </div>
                <br><br>
                <!-- STEP 3 -->
                <b><?php DUP_PRO_U::_e("Step 3:"); ?></b>&nbsp;
                <?php DUP_PRO_U::_e('Finalize OneDrive validation by clicking the "Finalize Setup" button.'); ?>
                <br/>
                <button type="button" class="button" onclick="DupPro.Storage.OneDrive.FinalizeSetup(); return false;"><i class="fa fa-check-square"></i> <?php DUP_PRO_U::_e('Finalize Setup'); ?></button>
            </div>
		</div>
            <input type="hidden" id="onedrive-is-business" name="onedrive-is-business" value="0">
		<?php endif; ?>
		<div class='onedrive-authorization-state' id="onedrive-state-authorized" style="margin-top:-5px">
			<?php if ($storage->onedrive_authorization_state == DUP_PRO_OneDrive_Authorization_States::Authorized) : ?>
				<h3>
					<?php echo (!$storage->onedrive_is_business()) ? DUP_PRO_U::__('OneDrive Personal Account') : DUP_PRO_U::__('OneDrive Business Account'); ?><br/>
					<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Duplicator has been authorized to access this user\'s OneDrive account'); ?></i>
				</h3>
				<div id="onedrive-account-info">
					<label><?php DUP_PRO_U::_e('Name'); ?>:</label>
					<?php echo $onedrive_account_info->displayName; ?><br/>
				</div>
			<br/>

			<button type="button" class="button button-large" onclick='DupPro.Storage.OneDrive.CancelAuthorization();'>
				<?php DUP_PRO_U::_e('Cancel Authorization'); ?>
			</button><br/>
			<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Disassociates storage provider with the OneDrive account. Will require re-authorization.'); ?> </i>
			<?php endif; ?>
		</div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="_onedrive_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<b>//OneDrive/Apps/Duplicator Pro/</b>
		<input id="_onedrive_storage_folder" name="_onedrive_storage_folder" type="text" value="<?php echo $storage->onedrive_storage_folder; ?>" class="dpro-storeage-folder-path" />
		<p><i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i></p>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="onedrive_max_files">
			<input data-parsley-errors-container="#onedrive_max_files_error_container" id="onedrive_max_files" name="onedrive_max_files" type="text" value="<?php echo $storage->onedrive_max_files; ?>" maxlength="4">
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?> <br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit."); ?></i>
		</label>
		<div id="dropbox_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<button class="button button_onedrive_test" id="button_onedrive_send_file_test" type="button" onclick="DupPro.Storage.OneDrive.SendFileTest();">
			<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e('Test OneDrive Connection'); ?>
		</button>
		<p><i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?></i></p>
	</td>
</tr>
</table>

<!-- ===============================
FTP PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::FTP ?>" class="form-table provider" >
<tr>
	<td class="dpro-sub-title" colspan="2"><b><?php DUP_PRO_U::_e("Credentials"); ?></b></td>
</tr>
<tr>
	<th scope="row"><label for="ftp_server"><?php DUP_PRO_U::_e("Server"); ?></label></th>
	<td>
		<input id="ftp_server" name="ftp_server" data-parsley-errors-container="#ftp_server_error_container" type="text" autocomplete="off" value="<?php echo $storage->ftp_server ?>">
		<label for="ftp_server"><?php DUP_PRO_U::_e("Port"); ?></label> <input name="ftp_port" id="ftp_port" data-parsley-errors-container="#ftp_server_error_container" type="text" style="width:75px"  value="<?php echo $storage->ftp_port ?>">
		<div id="ftp_server_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="ftp_username"><?php DUP_PRO_U::_e("Username"); ?></label></th>
	<td><input id="ftp_username" name="ftp_username" type="text" autocomplete="off" value="<?php echo $storage->ftp_username; ?>" /></td>
</tr>
<tr>
	<th scope="row"><label for="ftp_password"><?php DUP_PRO_U::_e("Password"); ?></label></th>
	<td>
		<input id="ftp_password" name="ftp_password" type="password" autocomplete="off" value="<?php echo $storage->ftp_password; ?>" >
	</td>
</tr>
<tr>
	<th scope="row"><label for="ftp_password2"><?php DUP_PRO_U::_e("Retype Password"); ?></label></th>
	<td>
		<input id="ftp_password2" name="ftp_password2" type="password"  autocomplete="off" value="<?php echo $storage->ftp_password; ?>" data-parsley-errors-container="#ftp_password2_error_container"  data-parsley-trigger="change" data-parsley-equalto="#ftp_password" data-parsley-equalto-message="<?php DUP_PRO_U::_e("Passwords do not match"); ?>" /><br/>
		<div id="ftp_password2_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<td class="dpro-sub-title" colspan="2"><b><?php DUP_PRO_U::_e("Settings"); ?></b></td>
</tr>
<tr>
	<th scope="row"><label for="_ftp_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<input id="_ftp_storage_folder" name="_ftp_storage_folder" type="text" value="<?php echo $storage->ftp_storage_folder; ?>">
		<p><i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i></p>
	</td>
</tr>
<tr>
	<th scope="row"><label for="ftp_max_files"><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="ftp_max_files">
			<input id="ftp_max_files" name="ftp_max_files" data-parsley-errors-container="#ftp_max_files_error_container" type="text" value="<?php echo $storage->ftp_max_files; ?>">
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?> <br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit. "); ?></i>
		</label>
		<div id="ftp_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="ftp_timeout_in_secs"><?php DUP_PRO_U::_e("Timeout"); ?></label></th>
	<td>
		<input id="ftp_timeout" name="ftp_timeout_in_secs" data-parsley-errors-container="#ftp_timeout_error_container" type="text" value="<?php echo $storage->ftp_timeout_in_secs; ?>"> <label for="ftp_timeout_in_secs"><?php DUP_PRO_U::_e("seconds"); ?></label>
		<div id="ftp_timeout_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="ftp_ssl"><?php DUP_PRO_U::_e("SSL-FTP"); ?></label></th>
	<td>
		<input name="_ftp_ssl" <?php DUP_PRO_UI::echoChecked($storage->ftp_ssl); ?> class="checkbox" value="1" type="checkbox" id="_ftp_ssl" >
		<label for="_ftp_ssl"><?php DUP_PRO_U::_e("Use explicit SSL-FTP connection."); ?></label>
	</td>
</tr>
<tr>
	<th scope="row"><label for="_ftp_passive_mode"><?php DUP_PRO_U::_e("Passive Mode"); ?></label></th>
	<td>
		<input <?php DUP_PRO_UI::echoChecked($storage->ftp_passive_mode); ?> class="checkbox" value="1" type="checkbox" name="_ftp_passive_mode" id="_ftp_passive_mode">
		<label for="_ftp_passive_mode"><?php DUP_PRO_U::_e("Use FTP Passive Mode."); ?></label>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<button class="button button_ftp_test" id="button_ftp_send_file_test" type="button" onclick="DupPro.Storage.FTP.SendFileTest();">
		   <i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test FTP Connection'); ?>
		</button>
		<p>
			<i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?>
			<br/><br/>
			<?php DUP_PRO_U::_e("<b>Note:</b> Only FTP and FTPS (FTP/SSL) are supported here.<br/> To use SFTP (SSH File Transfer Protocol) change the type dropdown above."); ?></i>
		</p>
	</td>
</tr>
</table>	

<?php if(DUP_PRO_U::PHP55() && extension_loaded('gmp')) : ?>
<!-- ===============================
SFTP PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::SFTP ?>" class="form-table provider" >
<tr>
	<td class="dpro-sub-title" colspan="2"><b><?php DUP_PRO_U::_e("Credentials"); ?></b></td>
</tr>
<tr>
	<th scope="row"><label for="sftp_server"><?php DUP_PRO_U::_e("Server"); ?></label></th>
	<td>
		<input id="sftp_server" name="sftp_server" data-parsley-errors-container="#sftp_server_error_container" type="text" autocomplete="off" value="<?php echo $storage->sftp_server ?>">
		<label for="sftp_server"><?php DUP_PRO_U::_e("Port"); ?></label> <input name="sftp_port" id="sftp_port" data-parsley-errors-container="#sftp_server_error_container" type="text" style="width:75px"  value="<?php echo $storage->sftp_port ?>">
		<div id="sftp_server_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="sftp_username"><?php DUP_PRO_U::_e("Username"); ?></label></th>
	<td><input id="sftp_username" name="sftp_username" type="text" autocomplete="off" value="<?php echo $storage->sftp_username; ?>" /></td>
</tr>
<tr>
	<th scope="row"><label for="sftp_password"><?php DUP_PRO_U::_e("Password"); ?></label></th>
	<td>
		<input id="sftp_password" name="sftp_password" type="password" autocomplete="off" value="<?php echo $storage->sftp_password; ?>" >
	</td>
</tr>
<tr>
	<th scope="row"><label for="sftp_password2"><?php DUP_PRO_U::_e("Retype Password"); ?></label></th>
	<td>
		<input id="sftp_password2" name="sftp_password2" type="password"  autocomplete="off" value="<?php echo $storage->sftp_password; ?>" data-parsley-errors-container="#sftp_password2_error_container"  data-parsley-trigger="change" data-parsley-equalto="#sftp_password" data-parsley-equalto-message="<?php DUP_PRO_U::_e("Passwords do not match"); ?>" /><br/>
		<div id="sftp_password2_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
	<tr>
	<th scope="row"><label for="sftp_private_key"><?php DUP_PRO_U::_e("Private Key"); ?></label></th>
	<td>
		<input id="sftp_private_key_file" name="sftp_private_key_file" onchange="DupPro.Storage.SFTP.ReadPrivateKey(this);" type="file"  accept="ppk" value="" data-parsley-errors-container="#sftp_private_key_error_container" /><br/>
		<input type="hidden" name="sftp_private_key" id="sftp_private_key" value="<?php echo $storage->sftp_private_key; ?>" />
		<div id="sftp_private_key_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
	<tr>
	<th scope="row"><label for="sftp_private_key_password"><?php DUP_PRO_U::_e("Private Key Password"); ?></label></th>
	<td>
		<input id="sftp_private_key_password" name="sftp_private_key_password" type="password"  autocomplete="off" value="<?php echo $storage->sftp_private_key_password; ?>" data-parsley-errors-container="#sftp_private_key_password_error_container" /><br/>
		<div id="sftp_private_key_password_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="sftp_private_key_password2"><?php DUP_PRO_U::_e("Private Key Retype Password"); ?></label></th>
	<td>
		<input id="sftp_private_key_password2" name="sftp_private_key_password2" type="password"  autocomplete="off" value="<?php echo $storage->sftp_private_key_password; ?>" data-parsley-errors-container="#sftp_private_key_password2_error_container" data-parsley-trigger="change" data-parsley-equalto="#sftp_private_key_password" data-parsley-equalto-message="<?php DUP_PRO_U::_e("Passwords do not match"); ?>" /><br/>
		<div id="sftp_private_key_password2_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<td class="dpro-sub-title" colspan="2"><b><?php DUP_PRO_U::_e("Settings"); ?></b></td>
</tr>
<tr>
	<th scope="row"><label for="_sftp_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<input id="_sftp_storage_folder" name="_sftp_storage_folder" type="text" value="<?php echo $storage->sftp_storage_folder; ?>">
		<p><i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i></p>
	</td>
</tr>
	<tr>
	<th scope="row"><label for="sftp_max_files"><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="sftp_max_files">
			<input id="ftp_max_files" name="sftp_max_files" data-parsley-errors-container="#sftp_max_files_error_container" type="text" value="<?php echo $storage->sftp_max_files; ?>">
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?> <br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit. "); ?></i>
		</label>
		<div id="sftp_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="sftp_timeout_in_secs"><?php DUP_PRO_U::_e("Timeout"); ?></label></th>
	<td>
		<input id="sftp_timeout" name="sftp_timeout_in_secs" data-parsley-errors-container="#sftp_timeout_error_container" type="text" value="<?php echo $storage->sftp_timeout_in_secs; ?>"> <label for="sftp_timeout_in_secs"><?php DUP_PRO_U::_e("seconds"); ?></label>
		<div id="sftp_timeout_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<button class="button button_sftp_test" id="button_sftp_send_file_test" type="button" onclick="DupPro.Storage.SFTP.SendFileTest();">
		   <i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test SFTP Connection'); ?>
		</button>
		<p>
			<i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?></i>
		</p>
	</td>
</tr>
</table>
<?php endif;?>

<!-- ===============================
GOOGLE DRIVE PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::GDrive ?>" class="form-table provider" >
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Authorization"); ?></label></th>
	<td class="gdrive-authorize">
		<?php if($storage->gdrive_authorization_state == DUP_PRO_GDrive_Authorization_States::Unauthorized) : ?>
			<div class='gdrive-authorization-state' id="gdrive-state-unauthorized">
				<!-- CONNECT -->
				<div id="dpro-gdrive-connect-btn-area">
					<button id="dpro-gdrive-connect-btn" type="button" class="button button-large" onclick="DupPro.Storage.GDrive.GoogleGetAuthUrl(); ">
						<i class="fa fa-plug"></i> <?php DUP_PRO_U::_e('Connect to Google Drive'); ?>
						<img src="<?php echo DUPLICATOR_PRO_IMG_URL ?>/gdrive-24.png" style='vertical-align: middle; margin:-2px 0 0 3px; height:18px; width:18px' />
					</button>
				</div>
				<div class='authorization-state' id="dpro-gdrive-connect-progress">
					<div style="padding:10px">
						<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Getting Google Drive Request Token'); ?>...
					</div>
				</div>

				<!-- STEPS -->
				<div id="dpro-gdrive-steps">
					<div>
						<b><?php DUP_PRO_U::_e('Step 1:'); ?></b>&nbsp;
						<?php DUP_PRO_U::_e("Duplicator needs to authorize Google Drive."); ?>
						<div class="auth-code-popup-note">
							<?php echo $txt_auth_note ?>
						</div>
						<button id="gdrive-auth-window-button" class="button" onclick="DupPro.Storage.GDrive.OpenAuthPage(); return false;">
							<i class="fa fa-user"></i> <?php DUP_PRO_U::_e("Authorize Google Drive"); ?>
						</button>
					</div>

					<div id="gdrive-auth-code-area">
						<b><?php DUP_PRO_U::_e('Step 2:'); ?></b> <?php DUP_PRO_U::_e("Paste code from Google authorization page."); ?> <br/>
						<input style="width:400px" id="gdrive-auth-code" name="gdrive-auth-code" />
					</div>

					<b><?php DUP_PRO_U::_e('Step 3:'); ?></b> <?php DUP_PRO_U::_e('Finalize Google Drive setup by clicking the "Finalize Setup" button.') ?><br/>
					<button type="button" class="button" onclick="DupPro.Storage.GDrive.FinalizeSetup(); return false;"><i class="fa fa-check-square"></i> <?php DUP_PRO_U::_e('Finalize Setup'); ?></button>
				</div>
			</div>
		<?php  else : ?>
			<div class='gdrive-authorization-state' id="gdrive-state-authorized" style="margin-top:-5px">

				<?php if($gdrive_user_info != null) : ?>
					<h3>
						<img src="<?php echo DUPLICATOR_PRO_IMG_URL ?>/gdrive-24.png" style='vertical-align: bottom' />
						<?php DUP_PRO_U::_e('Google Drive Account'); ?><br/>
						<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Duplicator has been authorized to access this user\'s Google Drive account'); ?></i>
					</h3>
					<div id="gdrive-account-info">
						<label><?php DUP_PRO_U::_e('Name'); ?>:</label>
						<?php echo "$gdrive_user_info->givenName $gdrive_user_info->familyName"; ?><br/>

						<label><?php DUP_PRO_U::_e('Email'); ?>:</label>
						<?php echo $gdrive_user_info->email; ?>
					</div><br/>
				<?php else : ?>
					<div><?php DUP_PRO_U::_e('Error retrieving user information'); ?></div>
				<?php endif ?>

				<button type="button" class="button button-large" onclick='DupPro.Storage.GDrive.CancelAuthorization();'>
					<?php DUP_PRO_U::_e('Cancel Authorization'); ?>
				</button><br/>
				<i class="dpro-edit-info"><?php DUP_PRO_U::_e('Disassociates storage provider with the Google Drive account. Will require re-authorization.'); ?> </i>
			</div>
		<?php endif ?>
	</td>
</tr>
<tr>
	<th scope="row"><label for="_gdrive_storage_folder"><?php DUP_PRO_U::_e("Storage Folder"); ?></label></th>
	<td>
		<b>//Google Drive/</b>
		<input id="_gdrive_storage_folder" name="_gdrive_storage_folder" type="text" value="<?php echo $storage->gdrive_storage_folder; ?>"  class="dpro-storeage-folder-path"/>
		<p>
			<i><?php DUP_PRO_U::_e("Folder where packages will be stored. This should be unique for each web-site using Duplicator."); ?></i>
			<i class="fa fa-question-circle" data-tooltip-title="<?php DUP_PRO_U::_e("Storage Folder Notice"); ?>" data-tooltip="<?php DUP_PRO_U::_e('If the directory path above is already in Google Drive before connecting then a duplicate folder name will be made in the same path. This is because the plugin only has rights to folders it creates.'); ?>"></i>

		</p>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="gdrive_max_files">
			<input data-parsley-errors-container="#gdrive_max_files_error_container" id="gdrive_max_files" name="gdrive_max_files" type="text" value="<?php echo $storage->gdrive_max_files; ?>" maxlength="4">&nbsp;
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?> <br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit."); ?></i>
		</label>
		<div id="gdrive_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Connection"); ?></label></th>
	<td>
		<?php
			$gdrive_test_button_disabled = '';
			if($storage->id == -1 || (($storage->storage_type == DUP_PRO_Storage_Types::GDrive) && ($storage->gdrive_access_token_set_json == '')))
			{
				$gdrive_test_button_disabled = 'disabled';
			}

		?>
		<button class="button button_gdrive_test" id="button_gdrive_send_file_test" type="button" onclick="DupPro.Storage.GDrive.SendFileTest();" <?php echo $gdrive_test_button_disabled; ?>>
			<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e('Test Google Drive Connection'); ?>
		</button>
		<p><i><?php DUP_PRO_U::_e("Test connection by sending and receiving a small file to/from the account."); ?></i></p>
	</td>
</tr>
</table>

<!-- ===============================
LOCAL PROVIDER -->
<table id="provider-<?php echo DUP_PRO_Storage_Types::Local ?>" class="provider form-table">
<tr valign="top">
	<th scope="row">
		<label onclick="jQuery('#_local_storage_folder').val('<?php echo rtrim(DUPLICATOR_PRO_WPROOTPATH, '/'); ?>')">
			<?php DUP_PRO_U::_e("Storage Folder"); ?>
		</label>
	</th>
	<td>
		<input data-parsley-errors-container="#_local_storage_folder_error_container" data-parsley-required="true"  type="text" id="_local_storage_folder" name="_local_storage_folder" data-parsley-pattern=".*" value="<?php echo $storage->local_storage_folder; ?>"   />
		<p>
			<i>
				<?php DUP_PRO_U::_e("Where to store on the server hosting this site. <b>This will not store to your local computer unless that is where this web-site is hosted.</b><br/>"
				. " On Linux servers start with '/' (e.g. /mypath). On Windows use drive letters (e.g. E:/mypath)."); ?>
			</i>
		</p>
		<div id="_local_storage_folder_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
<tr>
	<th scope="row"><label for="local_filter_protection"><?php DUP_PRO_U::_e("Filter Protection"); ?></label></th>
	<td>
		<input id="_local_filter_protection" name="_local_filter_protection" type="checkbox" <?php DUP_PRO_UI::echoChecked($storage->local_filter_protection); ?> onchange="DupPro.Storage.LocalFilterToggle()">&nbsp;
		<label for="_local_filter_protection">
			<?php DUP_PRO_U::_e("Filter the Storage Folder (recommended)"); ?>
		</label>
		<div style="padding-top:6px">
			<i><?php DUP_PRO_U::_e("When checked this will exclude the 'Storage Folder' and all of its content and sub-folders from package builds."); ?></i>
			<div id="_local_filter_protection_message" style="display:none; color:maroon">
				<i><?php DUP_PRO_U::_e("Unchecking filter protection is not recommended.  This setting helps to prevents packages from getting bundled in other packages."); ?></i>
			</div>
		</div>
	</td>
</tr>
<tr>
	<th scope="row"><label for=""><?php DUP_PRO_U::_e("Max Packages"); ?></label></th>
	<td>
		<label for="local_max_files">
			<input data-parsley-errors-container="#local_max_files_error_container" id="local_max_files" name="local_max_files" type="text" value="<?php echo $storage->local_max_files; ?>" maxlength="4">&nbsp;
			<?php DUP_PRO_U::_e("Number of packages to keep in folder."); ?><br/>
			<i><?php DUP_PRO_U::_e("When this limit is exceeded, the oldest package will be deleted. Set to 0 for no limit."); ?></i>
		</label>
		<div id="local_max_files_error_container" class="duplicator-error-container"></div>
	</td>
</tr>
</table>	


<br style="clear:both" />
<button class="button button-primary" type="submit"><?php DUP_PRO_U::_e('Save Provider'); ?></button>
</form>
<?php
	$alert1 = new DUP_PRO_UI_Dialog();
	$alert1->title		= DUP_PRO_U::__('Dropbox Authentication Error');
	$alert1->message	= DUP_PRO_U::__('Error getting Dropbox authentication URL. Please try again later.');
	$alert1->initAlert();

    $alert2 = new DUP_PRO_UI_Dialog();
	$alert2->title		= $alert1->title;
	$alert2->message	= DUP_PRO_U::__('Unable to get Dropbox authentication URL.');
	$alert2->initAlert();

    $alert3 = new DUP_PRO_UI_Dialog();
	$alert3->title		= 'Token Error';
	$alert3->message	= DUP_PRO_U::__('Tried transitioning to auth button click but don\'t have the request token!');
	$alert3->initAlert();

    $alert4 = new DUP_PRO_UI_Dialog();
	$alert4->title		= 'Dropbox Error';
	$alert4->message	= DUP_PRO_U::__('Send Dropbox file test failed.');
	$alert4->initAlert();

    $alert5 = new DUP_PRO_UI_Dialog();
	$alert5->title		= $alert1->title;
	$alert5->message	= DUP_PRO_U::__('Please enter your Dropbox authorization code!');
	$alert5->initAlert();

    $alert6 = new DUP_PRO_UI_Dialog();
	$alert6->title		= 'Google Drive Authorization Error';
	$alert6->message	= DUP_PRO_U::__('Please enter your Google Drive authorization code!');
	$alert6->initAlert();

    $alert7 = new DUP_PRO_UI_Dialog();
	$alert7->title		= 'Google Drive Error';
	$alert7->message	= DUP_PRO_U::__('Google Drive not supported on systems running PHP version < 5.3.2.');
	$alert7->initAlert();

    $alert8 = new DUP_PRO_UI_Dialog();
	$alert8->title		= $alert6->title;
	$alert8->message	= DUP_PRO_U::__('Error getting Google Drive authentication URL. Please try again later.');
	$alert8->initAlert();

    $alert9 = new DUP_PRO_UI_Dialog();
	$alert9->title		= $alert6->title;
	$alert9->message	= DUP_PRO_U::__('Unable to get Google Drive authentication URL.');
	$alert9->initAlert();

    $alert10 = new DUP_PRO_UI_Dialog();
	$alert10->title		= $alert7->title;
	$alert10->message	= DUP_PRO_U::__('Send Google Drive file test failed.');
	$alert10->initAlert();

    $alert11 = new DUP_PRO_UI_Dialog();
	$alert11->title		= 'FTP Test Error';
	$alert11->message	= DUP_PRO_U::__('Send FTP file test failed! Be sure the full storage path exists. For additional help see the online '
		. '<a href="https://snapcreek.com/duplicator/docs/faqs-tech/#faq-trouble-400-q" target="_blank">FTP troubleshooting steps</a>.');
	$alert11->initAlert();

    $alert12 = new DUP_PRO_UI_Dialog();
	$alert12->title		= 'S3 Test Error';
	$alert12->message	= DUP_PRO_U::__('Test failed. Check configuration.');
	$alert12->initAlert();

    $alert13 = new DUP_PRO_UI_Dialog();
	$alert13->title		= 'SUCCESS!';
	$alert13->message	= '';  // javascript inserted message
	$alert13->initAlert();

    $alert14 = new DUP_PRO_UI_Dialog();
	$alert14->title		= $alert13->title;
	$alert14->message	= '';  // javascript inserted message
	$alert14->initAlert();

    $alert15 = new DUP_PRO_UI_Dialog();
	$alert15->title		= $alert13->title;
	$alert15->message	= '';  // javascript inserted message
	$alert15->initAlert();

    $alert16 = new DUP_PRO_UI_Dialog();
	$alert16->title		= $alert13->title;
	$alert16->message	= '';  // javascript inserted message
	$alert16->initAlert();

	$alert17 = new DUP_PRO_UI_Dialog();
    $alert17->title		= "OneDrive Error";
    $alert17->message	= DUP_PRO_U::__('Send OneDrive file test failed.');
    $alert17->initAlert();
?>
<script>
jQuery(document).ready(function ($) {

	// Quick fix for submint/enter error
	$(window).on('keyup keydown',function(e){
		if(!$( e.target ).is('textarea'))
		{
			var keycode = (typeof e.keyCode != 'undefined' && e.keyCode > -1 ? e.keyCode : e.which);
			if( (keycode === 13)) {
				e.preventDefault();
				return false;
			}
		}
	});

	var counter = 0;

	DupPro.Storage.Modes = {
		LOCAL: 0,
		DROPBOX: 1,
		FTP: 2,
		GDRIVE: 3,
		S3: 4
	};

	DupPro.Storage.BindParsley = function (mode)
	{
		if(counter++ > 0) {
			$('#dup-storage-form').parsley().destroy();
		}

		$('#dup-storage-form input').removeAttr('data-parsley-required');
		$('#dup-storage-form input').removeAttr('data-parsley-type');
		$('#dup-storage-form input').removeAttr('data-parsley-range');
		$('#dup-storage-form input').removeAttr('data-parsley-min');
		$('#name').attr('data-parsley-required', 'true');

		switch (parseInt(mode)) {

			case DupPro.Storage.Modes.LOCAL:
				$('#_local_storage_folder').attr('data-parsley-required', 'true');

				$('#local_max_files').attr('data-parsley-required', 'true');
				$('#local_max_files').attr('data-parsley-type', 'number');
				$('#local_max_files').attr('data-parsley-min', '0');
				break;

			case DupPro.Storage.Modes.DROPBOX:
				$('#dropbox_max_files').attr('data-parsley-required', 'true');
				$('#dropbox_max_files').attr('data-parsley-type', 'number');
				$('#dropbox_max_files').attr('data-parsley-min', '0');
				break;

			case DupPro.Storage.Modes.FTP:
				$('#ftp_server').attr('data-parsley-required', 'true');
				$('#ftp_port').attr('data-parsley-required', 'true');
				$('#ftp_password, #ftp_password2').attr('data-parsley-required', 'true');
				$('#ftp_max_files').attr('data-parsley-required', 'true');
				$('#ftp_timeout').attr('data-parsley-required', 'true');
				$('#ftp_port').attr('data-parsley-type', 'number');
				$('#ftp_max_files').attr('data-parsley-type', 'number');
				$('#ftp_timeout').attr('data-parsley-type', 'number');
				$('#ftp_port').attr('data-parsley-range', '[1,65535]');
				$('#ftp_max_files').attr('data-parsley-min', '0');
				$('#ftp_timeout').attr('data-parsley-min', '10');
				break;

			case DupPro.Storage.Modes.GDRIVE:
				$('#gdrive_max_files').attr('data-parsley-required', 'true');
				$('#gdrive_max_files').attr('data-parsley-type', 'number');
				$('#gdrive_max_files').attr('data-parsley-min', '0');
				break;

			case DupPro.Storage.Modes.S3:
				$('#s3_max_files').attr('data-parsley-required', 'true');
				$('#s3_access_key').attr('data-parsley-required', 'true');
				$('#s3_secret_key').attr('data-parsley-required', 'true');
				$('#s3_bucket').attr('data-parsley-required', 'true');
				break;

		};
		$('#dup-storage-form').parsley();

	};

	// GENERAL STORAGE LOGIC
	DupPro.Storage.ChangeMode = function (animateOverride) {
		var mode = $("#change-mode option:selected").val();
		var animate = 400;

		if (arguments.length == 1) {
			animate = animateOverride;
		}

		$('.provider').hide();
		$('#provider-' + mode).show(animate);
		DupPro.Storage.BindParsley(mode);
	}

	DupPro.Storage.ChangeMode(0);

	// DROPBOX RELATED METHODS
	DupPro.Storage.Dropbox.AuthorizationStates = {
		UNAUTHORIZED: 0,
		WAITING_FOR_REQUEST_TOKEN: 1,
		WAITING_FOR_AUTH_BUTTON_CLICK: 2,
		WAITING_FOR_ACCESS_TOKEN: 3,
		AUTHORIZED: 4
	}

	//=========================================================================
	//ONEDRIVE SPECIFIC
	//=========================================================================
	DupPro.Storage.OneDrive.GetAuthUrl = function(isBusiness = 0)
	{
		jQuery('.button_onedrive_test').prop('disabled', true);
		var data = {action: 'duplicator_pro_onedrive_get_auth_url', business: isBusiness };

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				if(data['status'] == 0) {
				    $(".onedrive-auth-container").show();
				    $("#onedrive-is-business").val(isBusiness);
				    $("#dpro-onedrive-connect-btn").hide();
				    $("#dpro-onedrive-business-connect-btn").hide();
					DupPro.Storage.OneDrive.AuthUrl = data['onedrive_auth_url'];
				} else {
					alert("<?php DUP_PRO_U::_e('Error getting OneDrive authentication URL. Please try again later.') ?>");
				}
			},
			error: function (data) {
				alert("<?php DUP_PRO_U::_e('Unable to get OneDrive authentication URL.') ?>");
			}
		});
	};

	DupPro.Storage.OneDrive.OpenAuthPage = function () {
	    console.log(DupPro.Storage.OneDrive.AuthUrl);
        window.open(DupPro.Storage.OneDrive.AuthUrl,'_blank');
    }

    DupPro.Storage.OneDrive.CancelAuthorization = function()
    {
        <?php if (DUP_PRO_U::PHP56()): ?>
        window.open('<?php echo DUP_PRO_Onedrive_U::get_onedrive_logout_url(); ?>', '_blank');
        $("#dup-storage-form-action").val('onedrive-revoke-access');
        $("#dup-storage-form").submit();
        <?php endif; ?>
    }

    DupPro.Storage.OneDrive.FinalizeSetup = function () {
        if ($('#onedrive-auth-code').val().length > 5) {
            $("#dup-storage-form").submit();
        } else {
            <?php $alert5->showAlert(); ?>
        }
    }

	DupPro.Storage.OneDrive.SendFileTest = function ()
	{
		var current_storage_folder = $('#_onedrive_storage_folder').val();
		var data = {action: 'duplicator_pro_onedrive_send_file_test', storage_id: <?php echo $storage->id; ?>, storage_folder: current_storage_folder};
		var $test_button = $('#button_onedrive_send_file_test');
		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e("Attempting Connection Please Wait..."); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e("Test Onedrive Connection"); ?>');
				if (typeof (data.success) !== 'undefined') {
					<?php $alert15->showAlert(); ?>
					$("#<?php echo $alert15->getID(); ?>_message").html(data.success);
				} else {
					<?php $alert17->showAlert(); ?>
					console.log(data);
				}
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test Onedrive Connection"); ?>');
				<?php $alert17->showAlert(); ?>
				console.log(data);
			}
		});
	};


	//=========================================================================
	//DROPBOX SPECIFIC
	//=========================================================================
	DupPro.Storage.Dropbox.authorizationState = <?php echo $storage->dropbox_authorization_state; ?>;

	DupPro.Storage.Dropbox.CancelAuthorization = function()
	{
		$("#dup-storage-form-action").val('dropbox-revoke-access');
		$("#dup-storage-form").submit();
	}

	DupPro.Storage.Dropbox.DropboxGetAuthUrl = function()
	{
		jQuery('.authorization-state').hide();
		jQuery('#state-waiting-for-request-token').show();
		jQuery('.button_dropbox_test').prop('disabled', true);
		var data = {action: 'duplicator_pro_dropbox_get_auth_url' };

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				// Success
				if(data['status'] == 0) {
					DupPro.Storage.Dropbox.AuthUrl = data['dropbox_auth_url'];
					jQuery("#state-waiting-for-auth-button-click").show();
				} else {
					<?php $alert1->showAlert(); ?>
					jQuery('.authorization-state').show();
				}
			},
			error:		function (data) { <?php $alert2->showAlert(); ?> },
			complete:	function (data) {jQuery('#state-waiting-for-request-token').hide();}
		});
	};

	DupPro.Storage.Dropbox.TransitionAuthorizationState = function (newState)
	{
		jQuery('.authorization-state').hide();
		jQuery('.dropbox_access_type').prop('disabled', true);
		jQuery('.button_dropbox_test').prop('disabled', true);

		switch (newState) {
			case DupPro.Storage.Dropbox.AuthorizationStates.UNAUTHORIZED:
				jQuery('.dropbox_access_type').prop('disabled', false);
				$("#dropbox_authorization_state").val(DupPro.Storage.Dropbox.AuthorizationStates.UNAUTHORIZED);
				DupPro.Storage.Dropbox.requestToken = null;
				jQuery("#state-unauthorized").show();
				break;

			case DupPro.Storage.Dropbox.AuthorizationStates.WAITING_FOR_REQUEST_TOKEN:
				DupPro.Storage.Dropbox.GetRequestToken();
				jQuery("#state-waiting-for-request-token").show();
				break;

			case DupPro.Storage.Dropbox.AuthorizationStates.WAITING_FOR_AUTH_BUTTON_CLICK:
				// Nothing to do here other than show the button and wait
				jQuery("#state-waiting-for-auth-button-click").show();
				break;

			case DupPro.Storage.Dropbox.AuthorizationStates.WAITING_FOR_ACCESS_TOKEN:
				jQuery("#state-waiting-for-access-token").show();
				if (DupPro.Storage.Dropbox.requestToken != null) {
					DupPro.Storage.Dropbox.GetAccessToken();
				} else {
					<?php $alert3->showAlert(); ?>
					DupPro.Storage.Dropbox.TransitionAuthorizationState(DupPro.Storage.Dropbox.AuthorizationStates.UNAUTHORIZED);
				}
				break;

			case DupPro.Storage.Dropbox.AuthorizationStates.AUTHORIZED:
				var token = $("#dropbox_access_token").val();
				var token_secret = $("#dropbox_access_token_secret").val();
				DupPro.Storage.Dropbox.accessToken = {};
				DupPro.Storage.Dropbox.accessToken.t = token;
				DupPro.Storage.Dropbox.accessToken.s = token_secret;
				jQuery("#state-authorized").show();
				jQuery('.button_dropbox_test').prop('disabled', false);
				break;
		}

		DupPro.Storage.Dropbox.authorizationState = newState;
	}

	DupPro.Storage.Dropbox.SendFileTest = function ()
	{
		var fullAccess = $('#dropbox_accesstype_full').is(":checked");
		var current_storage_folder = $('#_dropbox_storage_folder').val();
		var data = {action: 'duplicator_pro_dropbox_send_file_test', storage_id: <?php echo $storage->id; ?>, storage_folder: current_storage_folder, full_access: fullAccess};
		var $test_button = $('#button_dropbox_send_file_test');
		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e("Attempting Connection Please Wait..."); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test Dropbox Connection"); ?>');
				if (typeof (data.success) !== 'undefined') {
					<?php $alert15->showAlert(); ?>
					$("#<?php echo $alert15->getID(); ?>_message").html(data.success);
				} else {
					<?php $alert4->showAlert(); ?>
					console.log(data);
				}
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test Dropbox Connection"); ?>');
				<?php $alert4->showAlert(); ?>
				console.log(data);
			}
		});
	}

	DupPro.Storage.Dropbox.OpenAuthPage = function ()
	{
		window.open(DupPro.Storage.Dropbox.AuthUrl, '_blank');
	}

	DupPro.Storage.Dropbox.Authorize = function ()
	{
		window.open(DupPro.Storage.Dropbox.AuthUrl, '_blank');
		$('button#auth-validate').prop('disabled', false);
	}

	DupPro.Storage.Dropbox.FinalizeSetup = function()
	{
		 if ($('#dropbox-auth-code').val().length > 5) {
			 $("#dup-storage-form").submit();
		 } else {
			  <?php $alert5->showAlert(); ?>
		 }
	}

	DupPro.Storage.Dropbox.TransitionAuthorizationState(DupPro.Storage.Dropbox.authorizationState);
	$('button#auth-validate').prop('disabled', true);

	// GOOGLE DRIVE RELATED METHODS
	DupPro.Storage.GDrive.OpenAuthPage = function()
	{
		window.open(DupPro.Storage.GDrive.AuthUrl,  '_blank');
	}


	//=========================================================================
	//GOOGLE-DRIVE SPECIFIC
	//=========================================================================
	DupPro.Storage.GDrive.FinalizeSetup = function()
	{
		 if ($('#gdrive-auth-code').val().length > 5) {
			 $("#dup-storage-form").submit();
		 } else {
			<?php $alert6->showAlert(); ?>
		 }
	}

	DupPro.Storage.GDrive.GoogleGetAuthUrl = function()
	{
		$('#dpro-gdrive-connect-btn-area').hide();
		$('#dpro-gdrive-connect-progress').show();

		var data = {action: 'duplicator_pro_gdrive_get_auth_url' };

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				if(data['status'] == 0) {
					DupPro.Storage.GDrive.AuthUrl = data['gdrive_auth_url'];
					$('#dpro-gdrive-connect-btn-area').hide();
					$('#dpro-gdrive-steps').show();
				} else if(data['status'] == -2) {
					<?php $alert7->showAlert(); ?>
					$('#dpro-gdrive-connect-btn-area').show();
				} else {
					<?php $alert8->showAlert(); ?>
					$('#dpro-gdrive-connect-btn-area').show();
				}
			},
			error:		function (data) { <?php $alert9->showAlert(); ?>},
			complete:	function (data) { $('#dpro-gdrive-connect-progress').hide();}
		});
	}

	DupPro.Storage.GDrive.CancelAuthorization = function()
	{
		$("#dup-storage-form-action").val('gdrive-revoke-access');
		$("#dup-storage-form").submit();
	}

	DupPro.Storage.GDrive.SendFileTest = function() {
		var current_storage_folder = $('#_gdrive_storage_folder').val();
		var data = {action: 'duplicator_pro_gdrive_send_file_test', storage_folder: current_storage_folder, storage_id: <?php echo $storage->id; ?> };
		var $test_button = $('#button_gdrive_send_file_test');
		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e("Attempting Connection Please Wait..."); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test Google Drive Connection"); ?>');
				if (typeof (data.success) !== 'undefined') {
					<?php $alert13->showAlert(); ?>
					$("#<?php echo $alert13->getID(); ?>_message").html(data.success);
				} else {
					<?php $alert10->showAlert(); ?>
					console.log(data);
				}
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test Google Drive Connection"); ?>');
				<?php $alert10->showAlert(); ?>
				console.log(data);
			}
		});
	}

	//=========================================================================
	//FTP SPECIFIC
	//=========================================================================
	DupPro.Storage.FTP.SendFileTest = function ()
	{
		var current_storage_folder = $('#_ftp_storage_folder').val();
		var server = $('#ftp_server').val();
		var port = $('#ftp_port').val();
		var username = $('#ftp_username').val();
		var password = $('#ftp_password').val();
		var ssl = $('#_ftp_ssl').prop('checked') ? 1 : 0;
		var passive_mode = $('#_ftp_passive_mode').prop('checked') ? 1 : 0;
		var $test_button = $('#button_ftp_send_file_test');

		var data = {action: 'duplicator_pro_ftp_send_file_test', storage_folder: current_storage_folder, server: server,
			port: port, username: username, password: password, ssl: ssl, passive_mode: passive_mode};

		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Attempting Connection Please Wait...'); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				if (typeof (data.success) !== 'undefined') {
					<?php $alert14->showAlert(); ?>
					$("#<?php echo $alert14->getID(); ?>_message").html(data.success);
				} else {
					<?php $alert11->showAlert(); ?>
					console.log(data);
				}
				$test_button.html('<i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test FTP Connection'); ?>');
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test FTP Connection'); ?>');
				<?php $alert11->showAlert(); ?>
				console.log(data);
			}
		});
	}

	//=========================================================================
	//SFTP SPECIFIC
	//=========================================================================
	DupPro.Storage.SFTP.SendFileTest = function ()
	{
		var current_storage_folder = $('#_sftp_storage_folder').val();
		var server = $('#sftp_server').val();
		var port = $('#sftp_port').val();
		var username = $('#sftp_username').val();
		var password = $('#sftp_password').val();
		var private_key_password = $('#sftp_private_key_password').val();
		var $test_button = $('#button_sftp_send_file_test');
		var sftp_private_key = $('#sftp_private_key').val();
		var data = {action: 'duplicator_pro_sftp_send_file_test', storage_folder: current_storage_folder, server: server,
			port: port, username: username, password: password, private_key: sftp_private_key, private_key_password: private_key_password};

		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e('Attempting Connection Please Wait...'); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				if (typeof (data.success) !== 'undefined') {
					alert(data.success)
				} else {
					if ( typeof (data.error) !== 'undefined' ) {
						alert(data.error);
					} else {
						alert("<?php DUP_PRO_U::_e('Send SFTP file test failed. Be sure the full storage path exists.') ?>");
					}
				}
				$test_button.html('<i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test SFTP Connection'); ?>');
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i> <?php DUP_PRO_U::_e('Test SFTP Connection'); ?>');
				alert("<?php DUP_PRO_U::_e('Send SFTP file test failed. Be sure the full storage path exists.') ?>");
				console.log(data);
			}
		});
	}

	DupPro.Storage.SFTP.ReadPrivateKey = function(file_obj)
	{
		var files = file_obj.files;
		var private_key = files[0];
		var reader = new FileReader();
		reader.onload = function(e) {
			$("#sftp_private_key").val(e.target.result);
		}
		reader.readAsText(private_key);
	}


	//=========================================================================
	//AMAZON S3 SPECIFIC
	//=========================================================================
	DupPro.Storage.S3.SendFileTest = function () {
		var current_storage_folder = $('#_s3_storage_folder').val();
		var current_bucket = $('#s3_bucket').val();
		var current_region = $('#s3_region').val();
		var current_storage_class = $('#s3_storage_class').val();
		var current_access_key = $('#s3_access_key').val();
		var current_secret_key = $('#s3_secret_key').val();

		var data = {
			action: 'duplicator_pro_s3_send_file_test',
			storage_folder: current_storage_folder,
			bucket: current_bucket,
			storage_class: current_storage_class,
			region: current_region,
			access_key: current_access_key,
			secret_key: current_secret_key
		}

		var $test_button = $('#button_s3_send_file_test');
		$test_button.html('<i class="fa fa-circle-o-notch fa-spin"></i> <?php DUP_PRO_U::_e("Attempting Connection Please Wait..."); ?>');

		$.ajax({
			type: "POST",
			url: ajaxurl,
			dataType: "json",
			data: data,
			success: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test S3 Connection"); ?>');
				if (typeof (data.success) !== 'undefined') {
					<?php $alert16->showAlert(); ?>
					$("#<?php echo $alert16->getID(); ?>_message").html(data.success);
				}
				else {
					<?php $alert12->showAlert(); ?>
					console.log(data);
				}
			},
			error: function (data) {
				$test_button.html('<i class="fa fa-cloud-upload"></i>	<?php DUP_PRO_U::_e("Test S3 Connection"); ?>');
				<?php $alert12->showAlert(); ?>
				console.log(data);
			}
		});
	}

	// COMMON STORAGE RELATED METHODS
	DupPro.Storage.Copy = function ()
	{
		$("#dup-storage-form-action").val('copy-storage');
		$("#dup-storage-form").parsley().destroy();
		$("#dup-storage-form").submit();
	};

	DupPro.Storage.LocalFilterToggle = function ()
	{
		$("#_local_filter_protection").is(":checked")
			? $("#_local_filter_protection_message").hide(400)
			: $("#_local_filter_protection_message").show(400);

	};

	//Init
	DupPro.Storage.LocalFilterToggle();
	jQuery('#name').focus();

});
</script>
