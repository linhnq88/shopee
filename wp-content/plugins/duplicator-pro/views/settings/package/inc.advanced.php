<?php
/* @var $global DUP_PRO_Global_Entity */
defined("ABSPATH") or die("");

$_REQUEST['ajax_protocol'] = isset($_REQUEST['ajax_protocol']) ? $_REQUEST['ajax_protocol'] : 'admin';
$_REQUEST['lock_mode']     = isset($_REQUEST['lock_mode'])     ? $_REQUEST['lock_mode']     : 0;

$max_execution_time			= ini_get("max_execution_time");
$max_execution_time			= empty($max_execution_time) ? 30 : $max_execution_time;
$max_worker_cap_in_sec		= (int) (0.7 * (float) $max_execution_time);

//SAVE RESULTS
if (isset($_POST['action']) && $_POST['action'] == 'save') {
	check_admin_referer($nonce_action);

	//ADVANCED
	$global->lock_mode			= (int) $_REQUEST['lock_mode'];
	$global->json_mode			= (int) $_REQUEST['json_mode'];

	$global->ajax_protocol		= $_REQUEST['ajax_protocol'];
	$global->custom_ajax_url	= $_REQUEST['custom_ajax_url'];
	$global->clientside_kickoff	= isset($_REQUEST['_clientside_kickoff']);

    // Auto setting the max package runtime in case of client kickoff is turned off and
	// the max package runtime is less than 180 minutes - 3 hours
	if($global->clientside_kickoff && $global->max_package_runtime_in_min < 180 ) {
		$global->max_package_runtime_in_min = 180;
	}

    $global->basic_auth_enabled	= isset($_REQUEST['_basic_auth_enabled']) ? 1 : 0;
	if ($global->basic_auth_enabled == true) {
		$global->basic_auth_user = trim($_REQUEST['basic_auth_user']);
		$sglobal->basic_auth_password = $_REQUEST['basic_auth_password'];
	} else {
		$global->basic_auth_user	  = '';
		$sglobal->basic_auth_password = '';
	}
	$global->basic_auth_enabled	 = isset($_REQUEST['_basic_auth_enabled']) ? 1 : 0;
	$global->installer_base_name = isset($_REQUEST['_installer_base_name']) ? $_REQUEST['_installer_base_name'] : 'installer.php';
    $global->chunk_size = isset($_REQUEST['_chunk_size']) ? $_REQUEST['_chunk_size'] : 2048;

  	$global->php_max_worker_time_in_sec	 = $_REQUEST['php_max_worker_time_in_sec'];

	$action_updated = $global->save();
    $sglobal->save();
	$global->adjust_settings_for_system();
}
?>

<!-- ===============================
ADVANCED SETTINGS -->
<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=' . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG . "&sub={$section}"); ?>" method="post" data-parsley-validate>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" name="action" value="save">
<input type="hidden" name="page"   value="<?php echo DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ?>">
<input type="hidden" name="tab"   value="package">

<?php if ($action_updated) : ?>
	<div class="notice notice-success is-dismissible dpro-wpnotice-box"><p><?php echo $action_response; ?></p></div><br/>
<?php endif; ?>

<h3 class="title"><?php DUP_PRO_U::_e("Advanced") ?> </h3>
<hr size="1" />
<p class="description" style="color:maroon">
	<?php DUP_PRO_U::_e("Do not modify advanced settings unless you know the expected result or have talked to support."); ?>
</p>
<table class="form-table">
	<tr>
		<th scope="row"><label><?php DUP_PRO_U::_e("Thread Lock"); ?></label></th>
		<td>
			<input type="radio" name="lock_mode" value="<?php echo DUP_PRO_Thread_Lock_Mode::Flock; ?>" <?php echo DUP_PRO_UI::echoChecked($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock); ?> />
			<label for="lock_mode"><?php DUP_PRO_U::_e("File"); ?></label> &nbsp;
			<input type="radio" name="lock_mode" value="<?php echo DUP_PRO_Thread_Lock_Mode::SQL_Lock; ?>" <?php echo DUP_PRO_UI::echoChecked($global->lock_mode == DUP_PRO_Thread_Lock_Mode::SQL_Lock); ?> />
			<label for="lock_mode"><?php DUP_PRO_U::_e("SQL"); ?></label> &nbsp;
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php DUP_PRO_U::_e("JSON"); ?></label></th>
		<td>
			<input type="radio" name="json_mode" value="<?php echo DUP_PRO_JSON_Mode::PHP; ?>" <?php echo DUP_PRO_UI::echoChecked($global->json_mode == DUP_PRO_JSON_Mode::PHP); ?> />
			<label for="json_mode"><?php DUP_PRO_U::_e("PHP"); ?></label> &nbsp;
			<input type="radio" name="json_mode" value="<?php echo DUP_PRO_JSON_Mode::Custom; ?>" <?php echo DUP_PRO_UI::echoChecked($global->json_mode == DUP_PRO_JSON_Mode::Custom); ?> />
			<label for="json_mode"><?php DUP_PRO_U::_e("Custom"); ?></label> &nbsp;
		</td>
	</tr>
    <tr valign="top">
	<th scope="row"><label><?php DUP_PRO_U::_e("Max Worker Time"); ?></label></th>
	<td>
		<input style="float:left;display:block;margin-right:6px;" data-parsley-required data-parsley-errors-container="#php_max_worker_time_in_sec_error_container" data-parsley-min="10" data-parsley-type="number" class="narrow-input" type="text" name="php_max_worker_time_in_sec" id="php_max_worker_time_in_sec" value="<?php echo $global->php_max_worker_time_in_sec; ?>" />
		<p style="margin-left:4px;"><?php DUP_PRO_U::_e('Seconds'); ?></p>
		<div id="php_max_worker_time_in_sec_error_container" class="duplicator-error-container"></div>
		<p class="description">
			<?php
			DUP_PRO_U::_e("Lower is more reliable but slower. Recommended max is $max_worker_cap_in_sec sec based on PHP setting 'max_execution_time'.");
			?>
		</p>
	</td>
    </tr>
    <tr valign="top">
		<th scope="row"><label><?php DUP_PRO_U::_e('Chunk Size'); ?></label></th>
		<td>
            <input type="number" name="_chunk_size" id="_chunk_size" value="<?php echo $global->chunk_size; ?>" minlength="4" min="1024"
				  data-parsley-required
				  data-parsley-minlength="10"
				  data-parsley-errors-container="#chunk_size_error_container" />
			<div id="chunk_size_error_container" class="duplicator-error-container"></div>
			<p class="description">
				<?php DUP_PRO_U::_e('Archive upload chunk size'); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label><?php DUP_PRO_U::_e("Ajax"); ?></label></th>
		<td>
			<input type="radio" id="ajax_protocol_1" name="ajax_protocol" class="ajax_protocol" value="admin" <?php echo DUP_PRO_UI::echoChecked($global->ajax_protocol == 'admin'); ?> />
			<label for="ajax_protocol_1"><?php DUP_PRO_U::_e("Auto"); ?></label> &nbsp;
			<input type="radio" id="ajax_protocol_2" name="ajax_protocol" class="ajax_protocol" value="http" <?php echo DUP_PRO_UI::echoChecked($global->ajax_protocol == 'http'); ?> />
			<label for="ajax_protocol_2"><?php DUP_PRO_U::_e("HTTP"); ?></label> &nbsp;
			<input type="radio" id="ajax_protocol_3" name="ajax_protocol" class="ajax_protocol"  value="https" <?php echo DUP_PRO_UI::echoChecked($global->ajax_protocol == 'https'); ?> />
			<label for="ajax_protocol_3"><?php DUP_PRO_U::_e("HTTPS"); ?></label> &nbsp;
			<input type="radio" id="ajax_protocol_4" name="ajax_protocol" class="ajax_protocol" value="custom" <?php echo DUP_PRO_UI::echoChecked($global->ajax_protocol == 'custom'); ?> />
			<label for="ajax_protocol_4"><?php DUP_PRO_U::_e("Custom URL"); ?></label> <br/>
            <input style="width:600px" type="<?php echo ($global->ajax_protocol == 'custom' ? 'text' : 'hidden'); ?>" id="custom_ajax_url" name="custom_ajax_url" placeholder="<?php DUP_PRO_U::_e('Consult support before changing.'); ?>" value="<?php echo $global->custom_ajax_url; ?>" /> <span id="custom_ajax_url_error" style="color: maroon; text-weight: bold; display: none"><?php DUP_PRO_U::_e("Bad URL!"); ?></span>
			<p class="description">
				<?php DUP_PRO_U::_e("Used to kick off build worker. Only change if packages get stuck at start of build."); 	?>
			</p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label><?php DUP_PRO_U::_e('Client-side Kickoff'); ?></label></th>
		<td>
			<input type="checkbox" name="_clientside_kickoff" id="_clientside_kickoff" <?php DUP_PRO_UI::echoChecked($global->clientside_kickoff); ?> />
			<label for="_clientside_kickoff"><?php DUP_PRO_U::_e("Enabled") ?> </label><br/>
			<p class="description">
				<?php DUP_PRO_U::_e('Initiate package build from client. Only check this if instructed to by Snap Creek support.'); ?>
			</p>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label><?php DUP_PRO_U::_e("Basic Auth"); ?></label></th>
		<td>
			<input type="checkbox" name="_basic_auth_enabled" id="_basic_auth_enabled" <?php DUP_PRO_UI::echoChecked($global->basic_auth_enabled); ?> />
			<label for="_basic_auth_enabled"><?php DUP_PRO_U::_e("Enabled") ?> </label><br/>
			<input style="margin-top:8px;width:200px;" class="wide-input" autocomplete="off"  placeholder="<?php DUP_PRO_U::_e('User'); ?>" type="text" name="basic_auth_user" id="basic_auth_user" value="<?php echo $global->basic_auth_user; ?>" />
			<input id='auth_password' autocomplete="off" style="width:200px;" class="wide-input"  placeholder="<?php DUP_PRO_U::_e('Password'); ?>" type="password" name="basic_auth_password" id="basic_auth_password" value="<?php echo $sglobal->basic_auth_password; ?>" />
			<label for="auth_password">
				<i class="dpro-edit-info">
					<input type="checkbox" onclick="DupPro.UI.TogglePasswordDisplay(this.checked, 'auth_password');" /> <?php DUP_PRO_U::_e('Show Password') ?>
				</i>
			</label>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row"><label><?php DUP_PRO_U::_e('Installer Name'); ?></label></th>
		<td>
		   <input type="text" name="_installer_base_name" id="_installer_base_name" value="<?php echo $global->installer_base_name; ?>"
				  data-parsley-required
				  data-parsley-minlength="10"
				  data-parsley-errors-container="#installer_base_name_error_container" />
			<div id="installer_base_name_error_container" class="duplicator-error-container"></div>
			<p class="description">
				<?php DUP_PRO_U::_e('The base name of the installer file. Only change if host prevents using installer.php'); ?>
			</p>
		</td>
	</tr>
</table>

<p class="submit dpro-save-submit">
	<input type="submit" name="submit" id="submit" class="button-primary" value="<?php DUP_PRO_U::_e('Save Package Settings') ?>" style="display: inline-block;" />
</p>
</form>

<script>
(function($){
    var url_error = $('#custom_ajax_url_error');
    // Check URL is valid
    $.urlExists = function(url) {
        var http = new XMLHttpRequest();
        http.open('HEAD', url, false);
        http.send();
        return http.status!=404;
    };
    var debounce;
    $('#custom_ajax_url').on('input keyup keydown change paste focus', function(e){
        clearTimeout(debounce);
        var $this = $(this);
        debounce = setTimeout(function() {
            $this.css({'border':''});
            url_error.hide();
            if(!$.urlExists($this.val()))
            {
                $this.css({'border':'maroon 1px solid'});
                url_error.show();
            }
        },250);
    });

    (function($this){
        $this.css({'border':''});
        url_error.hide();
        if(!$.urlExists($this.val()))
        {
            $this.css({'border':'maroon 1px solid'});
            url_error.show();
        }
    }($('#custom_ajax_url')))

    /*
     * DISPLAY OR HIDE CUSTOM_AJAX_URL
     */
    $('.ajax_protocol').on('input click change select touchstart',function(e){
        // Setup and collect value
        var $this = $(this),
            value = $this.val(),
            hideField = $('#custom_ajax_url'),
            hideFieldState = hideField.attr('type'),
            offset = 200;
        url_error.hide();
        if(value == 'custom')
        {
            // Display hidden field
            if(hideFieldState == 'hidden')
            {
                hideField.hide().attr('type','text').fadeIn(offset);

                hideField.css({'border':''});
                url_error.hide();
                if(!$.urlExists(hideField.val()))
                {
                    hideField.css({'border':'maroon 1px solid'});
                    url_error.show();
                }
            }
        }
        else
        {
            // Hide field but keep it active for POST reading
            if(hideFieldState == 'text')
            {
                hideField.fadeOut(Math.round(offset/2),function(){
                    $(this).attr('type','hidden').show();
                });
            }
        }
    });
}(window.jQuery || jQuery))
</script>