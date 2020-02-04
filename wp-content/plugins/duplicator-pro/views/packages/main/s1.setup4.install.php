<?php defined("ABSPATH") or die(""); ?>

<style>
	 /*INSTALLER: Area*/
    .dup-installer-header-1 {font-weight:bold; padding-bottom:2px; width:100%}
    div.dup-installer-header-2 {font-weight:bold; border-bottom:1px solid #dfdfdf; padding-bottom:2px; width:100%}
	tr.dup-installer-header-2 td:first-child {font-weight:bold;}
	tr.dup-installer-header-2 td {border-bottom:1px solid #dfdfdf; padding-bottom:2px;}
    label.chk-labels {display:inline-block; margin-top:1px}
    table.dup-installer-tbl {width:98%;}

	div.secure-pass-area {display:none}
	input#secure-pass, input#secure-pass2{width:300px; margin: 3px 0 5px 0}
	label.secure-pass-lbl {display:inline-block; width:125px}
	div#dup-pack-installer-panel div.tabs-panel{min-height:150px}
    div.dpro-panel-optional-txt {color:maroon}
</style>

<!-- ===================
INSTALLER -->
<div class="dup-box">
	<div class="dup-box-title">
		<i class="fa fa-bolt"></i> <?php DUP_PRO_U::_e('Installer') ?>
		<div class="dup-box-arrow"></div>
	</div>		
	<div class="dup-box-panel" id="dup-pack-installer-panel" style="<?php echo $ui_css_installer ?>">
		<div class="dpro-panel-optional-txt">
			<b><?php DUP_PRO_U::_e('All values in this section are'); ?> <u><?php DUP_PRO_U::_e('optional'); ?></u>.</b> <br/>
			<?php DUP_PRO_U::_e("These fields can be pre-filled at install time but are not required here."); ?>
            <i class="fa fa-question-circle"
                data-tooltip-title="<?php DUP_PRO_U::_e("MySQL Server Prefills"); ?>"
                data-tooltip="<?php DUP_PRO_U::_e('The values in this section are NOT required! If you know ahead of time the database input fields the installer will use, '
                    . 'then you can optionally enter them here.  Otherwise you can just enter them in at install time.'); ?>"></i>
		</div>

		<table>
			<tr>
				<td style="vertical-align: top"><b><?php DUP_PRO_U::_e("Security") ?></b></td>
				<td>
					<input type="checkbox" name="secure-on" id="secure-on" onclick="DupPro.Pack.ToggleInstallerPassword()" />
					<label for="secure-on"><?php DUP_PRO_U::_e("Enable Password Protection") ?></label>
					<i class="fa fa-question-circle" 
					   data-tooltip-title="<?php DUP_PRO_U::_e("Password Protection:"); ?>" 
					   data-tooltip="<?php DUP_PRO_U::_e('Enabling this option will allow for basic password protection on the installer. Before running the installer the '
							   . 'password below must be entered before proceeding with an install.  This password is a general deterrent and should not be substituted for properly '
							   . 'keeping your files secure.'); ?>"></i>
					<br/>
					<div class="secure-pass-area">
						<label class="secure-pass-lbl" for="secure-pass"><?php DUP_PRO_U::_e("Password") ?>:</label> 
						<input type="password" name="secure-pass" id="secure-pass" maxlength="50" /> <br/>
						<label class="secure-pass-lbl" for="secure-pass"><?php DUP_PRO_U::_e("Confirm") ?>:</label> 
						<input type="password" name="secure-pass2" id="secure-pass2" maxlength="50" />
					</div>
					<br/>
				</td>
			</tr>			
			<!--tr>
				<td>
					<input type="checkbox" name="skipscan" id="skipscan" />
					<label for="skipscan"><?php DUP_PRO_U::_e("Skip System Scan Screen") ?></label>
					<i class="fa fa-question-circle" 
					   data-tooltip-title="<?php DUP_PRO_U::_e("Skip System Scan:"); ?>" 
					   data-tooltip="<?php DUP_PRO_U::_e('By default every time the installer is opened it will run a simple scan on the server environment.  If the scan check '
							   . 'passes then enabling this option automatically take you to step one of the installer and will skip the system scan screen.'); ?>"></i>
				</td>
			</tr-->

			<tr>
				<td style="width:130px"><b><?php DUP_PRO_U::_e("Branding") ?></b></td>
				<td>
					<?php
						$brands = DUP_PRO_Brand_Entity::get_all();
						if($is_freelancer_plus) :
					?>
						<select name="brand" id="brand">
							<?php
							$active_brand_id = 0;
							foreach ($brands as $i=>$brand) :
								if($brand->active) $active_brand_id = $brand->id;
							?>
								<option value="<?php echo $brand->id; ?>" title="<?php echo esc_attr(esc_html($brand->notes)); ?>"<?php echo $brand->active ? ' selected' : ''; ?>><?php echo $brand->name; ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						if(defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE === true) {
                            $preview_url = array(
                                network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default", (DUP_PRO_U::is_ssl() ? 'https' : 'http') ),
                                network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id={$active_brand_id}", (DUP_PRO_U::is_ssl() ? 'https' : 'http') )
                            );
                        } else {
                            $preview_url = array(
                                get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default" ),
                                get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id={$active_brand_id}" )
                            );
                        }
						?>
						<a href="<?php echo $preview_url[$active_brand_id > 0 ? 1 : 0]; ?>" target="_blank" class="button" id="brand-preview"><?php DUP_PRO_U::_e("Preview"); ?></a> &nbsp;
						<i class="fa fa-question-circle"
						   data-tooltip-title="<?php DUP_PRO_U::_e("Choose Brand:"); ?>"
						   data-tooltip="<?php DUP_PRO_U::_e('This option changes the branding of the installer file.  Click the preview button to see the selected style.'); ?>"></i>
					<?php else : ?>
						<a href="admin.php?page=duplicator-pro-settings&tab=package&sub=brand"><?php DUP_PRO_U::_e("Enable Branding"); ?></a>
					<?php endif; ?>
				</td>
			</tr>

		</table><br/>
		
		<!--div class="dup-installer-header-1"><i class="fa fa-caret-square-o-right"></i> <?php DUP_PRO_U::_e('STEP 1 - INPUTS'); ?></div-->
		
		<!-- ===================
		STEP1 TABS -->
		<div data-dpro-tabs="true">
			<ul>
				<li><?php DUP_PRO_U::_e('Basic') ?></li>
				<li id="dpro-cpnl-tab-lbl"><?php DUP_PRO_U::_e('cPanel') ?></li>
			</ul>

			<!-- ===================
			TAB1: Basic -->
			<div>
				<table class="dup-installer-tbl" id="s1-installer-dbbasic">
					<tr class="dup-installer-header-2">
						<td><?php DUP_PRO_U::_e("MySQL Server") ?></td>
						<td colspan="2" style="text-align: right">
							<a href="javascript:void(0)" onclick="DupPro.Pack.ApplyDataCurrent('s1-installer-dbbasic')">[use current]</a>
						</td>
					</tr>
					<tr>
						<td style="width:130px"><?php DUP_PRO_U::_e("Host") ?></td>
						<td><input type="text" name="dbhost" id="dbhost" maxlength="200" placeholder="<?php DUP_PRO_U::_e("example: localhost (value is optional)") ?>" data-current="<?php echo DB_HOST ?>"/></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Database") ?></td>
						<td><input type="text" name="dbname" id="dbname" maxlength="100" placeholder="<?php DUP_PRO_U::_e("example: DatabaseName (value is optional)") ?>" data-current="<?php echo DB_NAME ?>" /></td>
					</tr>							
					<tr>
						<td><?php DUP_PRO_U::_e("User") ?></td>
						<td><input type="text" name="dbuser" id="dbuser" maxlength="100" placeholder="<?php DUP_PRO_U::_e("example: DatabaseUser (value is optional)") ?>" data-current="<?php echo DB_USER ?>"/></td>
					</tr>
				</table>					
			</div>
			
			<!-- ===================
			TAB2: cPanel -->
			<div>
				<table class="dup-installer-tbl">
					<tr>
						<td colspan="2"><div class="dup-installer-header-2"><?php DUP_PRO_U::_e("cPanel Login") ?></div></td>
					</tr>
					<tr>
						<td style="width:130px"><?php DUP_PRO_U::_e("Automation") ?></td>
						<td>
							<input type="checkbox" name="cpnl-enable" id="cpnl-enable" />
							<label for="cpnl-enable"><?php DUP_PRO_U::_e("Auto Select cPanel") ?></label> 
							<i class="fa fa-question-circle" 
								data-tooltip-title="<?php DUP_PRO_U::_e("Auto Select cPanel:"); ?>" 
								data-tooltip="<?php DUP_PRO_U::_e('Enabling this options will automatically select the cPanel tab when step one of the installer is shown.'); ?>">
							</i>
						</td>
					</tr>						
					<tr>
						<td><?php DUP_PRO_U::_e("Host") ?></td>
						<td><input type="text" name="cpnl-host" id="cpnl-host"  maxlength="200" placeholder="<?php DUP_PRO_U::_e("example: cpanelHost (value is optional)") ?>"/></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("User") ?></td>
						<td><input type="text" name="cpnl-user" id="cpnl-user" maxlength="200" placeholder="<?php DUP_PRO_U::_e("example: cpanelUser (value is optional)") ?>"/></td>
					</tr>					
				</table><br/>
				
				<table class="dup-installer-tbl" id="s1-installer-dbcpanel">
					<tr class="dup-installer-header-2">
						<td><?php DUP_PRO_U::_e("MySQL Server") ?></td>
						<td colspan="2" style="text-align: right">
							<a href="javascript:void(0)" onclick="DupPro.Pack.ApplyDataCurrent('s1-installer-dbcpanel')">[use current]</a>
						</td>
					</tr>
					<tr>
						<td style="width:130px"><?php DUP_PRO_U::_e("Action") ?></td>
						<td>							
							<select name="cpnl-dbaction" id="cpnl-dbaction">
								<option value="create">Create A New Database</option>
								<option value="empty">Connect and Delete Any Existing Data</option>
								<option value="rename">Connect and Backup Any Existing Data</option>
								<option value="manual">Manual SQL Execution (Advanced)</option>
							</select>
						</td>
					</tr>					
					<tr>
						<td style="width:130px"><?php DUP_PRO_U::_e("Host") ?></td>
						<td><input type="text" name="cpnl-dbhost" id="cpnl-dbhost" maxlength="200" placeholder="<?php DUP_PRO_U::_e("example: localhost (value is optional)") ?>" data-current="<?php echo DB_HOST ?>"/></td>
					</tr>
					<tr>
						<td><?php DUP_PRO_U::_e("Database") ?></td>
						<td><input type="text" name="cpnl-dbname" id="cpnl-dbname" data-parsley-pattern="/^[a-zA-Z0-9-_]+$/" maxlength="100" placeholder="<?php DUP_PRO_U::_e("example: DatabaseName (value is optional)") ?>" data-current="<?php echo DB_NAME ?>"/></td>
					</tr>							
					<tr>
						<td><?php DUP_PRO_U::_e("User") ?></td>
						<td><input type="text" name="cpnl-dbuser" id="cpnl-dbuser" data-parsley-pattern="/^[a-zA-Z0-9-_]+$/" maxlength="100" placeholder="<?php DUP_PRO_U::_e("example: DatabaseUserName (value is optional)") ?>" data-current="<?php echo DB_USER ?>" /></td>
					</tr>
				</table>
			
			</div>
		</div><br/>

        <small><?php DUP_PRO_U::_e("Additional inputs can be entered at install time.") ?></small>
		<br/><br/>
	</div>		
</div><br/>

<script>
(function($) {
	DupPro.Pack.ToggleInstallerPassword = function () 
	{
		if ($('#secure-on').is(':checked')) 
		{
			$('.secure-pass-area').show();
			$('#secure-pass, #secure-pass2').attr('required', 'true');
			$('#secure-pass').attr('data-parsley-equalto', '#secure-pass2');
		} else {
			 $('.secure-pass-area').hide();
			 $('#secure-pass, #secure-pass2').removeAttr('required');
			 $('#secure-pass').removeAttr('data-parsley-equalto');
		}
	};
	
	DupPro.Pack.ApplyDataCurrent = function(id) 
	{
		$('#' + id + ' input').each(function() 
		{
			var attr = $(this).attr('data-current');
			if (typeof attr !== typeof undefined && attr !== false) {
				$(this).attr('value', $(this).attr('data-current'));
			}
		});
	};
<?php if($is_freelancer_plus) : ?>
    // brand-preview
    var $brand = $("#brand"),
        brandCheck = function(e){
            var $this = $(this) || $brand,
                $id = $this.val(),
                <?php if(defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE === true) : ?>
                $url = [
                    '<?php echo network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default", (DUP_PRO_U::is_ssl() ? 'https' : 'http') ); ?>',
                    '<?php echo network_admin_url("admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id=", (DUP_PRO_U::is_ssl() ? 'https' : 'http') ); ?>' + $id
                ];
                <?php else: ?>
                $url = [
                    '<?php echo get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=default" ); ?>',
                    '<?php echo get_admin_url(null, "admin.php?page=duplicator-pro-settings&tab=package&sub=brand&view=edit&action=edit&id=" ); ?>' + $id
                ];
                <?php endif; ?>
            $("#brand-preview").attr( 'href', $url[ $id > 0 ? 1 : 0 ] );

            $this.find('option[value="'+ $id +'"]')
                .attr('selected', 'selected')
                .parent();
        };
    $brand.on('select change', brandCheck);
<?php endif; ?>
    //INIT
    $(document).ready(function ()
    {
        DupPro.Pack.ToggleInstallerPassword();
    });
}(window.jQuery));
</script>
