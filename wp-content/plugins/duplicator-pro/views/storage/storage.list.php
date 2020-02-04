<?php
defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.storage.entity.php');

$nonce_action = 'duppro-storage-list';
$display_edit = false;

if (isset($_REQUEST['action'])) {
	check_admin_referer($nonce_action);

	$action = $_REQUEST['action'];
	switch ($action) {
		case 'add':
			$display_edit = true;
			break;

		case 'bulk-delete':
			$storage_ids = $_REQUEST['selected_id'];

			foreach ($storage_ids as $storage_id) {
				DUP_PRO_Storage_Entity::delete_by_id($storage_id);
			}
			break;

		case 'edit':
			$display_edit = true;
			break;


		case 'delete':
			$storage_id = (int) $_REQUEST['storage_id'];

			DUP_PRO_LOG::trace("attempting to delete storage id $storage_id");
			DUP_PRO_Storage_Entity::delete_by_id($storage_id);
			break;

		default:

			break;
	}
}

$storages = DUP_PRO_Storage_Entity::get_all();
$storage_count = count($storages);
?>

<style>
    /*Detail Tables */
    table.storage-tbl td {height: 45px}
    table.storage-tbl a.name {font-weight: bold}
    table.storage-tbl input[type='checkbox'] {margin-left: 5px}
    table.storage-tbl div.sub-menu {margin: 5px 0 0 2px; display: none}
    table tr.storage-detail {display:none; margin: 0;}
    table tr.storage-detail td { padding: 3px 0 5px 20px}
    table tr.storage-detail div {line-height: 20px; padding: 2px 2px 2px 15px}
    table tr.storage-detail td button {margin:5px 0 5px 0 !important; display: block}
    tr.storage-detail label {min-width: 150px; display: inline-block; font-weight: bold}
</style>

<!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
    <tr>
        <td>
            <select id="bulk_action">
                <option value="-1" selected="selected"><?php _e("Bulk Actions"); ?></option>
                <option value="delete" title="Delete selected storage endpoint(s)"><?php _e("Delete"); ?></option>
            </select>
            <input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Storage.BulkAction()">
			<span class="btn-separator"></span>
			<a href="admin.php?page=duplicator-pro-settings&tab=storage" class="button grey-icon" title="<?php DUP_PRO_U::_e("Settings") ?>"><i class="fa fa-gear"></i></a>
        </td>
        <td>
			<div class="btnnav">
				<span><i class="fa fa-database"></i> <?php DUP_PRO_U::_e("Providers"); ?></span>
				<a href="<?php echo $edit_storage_url; ?>" class="add-new-h2"><?php DUP_PRO_U::_e('Add New'); ?></a>
			</div>
        </td>
    </tr>
</table>

<form id="dup-storage-form" action="<?php echo $storage_tab_url; ?>" method="post">
    <?php wp_nonce_field($nonce_action); ?>
    <input type="hidden" id="dup-storage-form-action" name="action" value=""/>
    <input type="hidden" id="dup-selected-storage" name="storage_id" value="-1"/>

    <!-- ====================
    LIST ALL STORAGE -->
    <table class="widefat storage-tbl">
        <thead>
            <tr>
                <th style='width:10px;'><input type="checkbox" id="dpro-chk-all" title="Select all storage endpoints" onclick="DupPro.Storage.SetAll(this)"></th>
                <th style='width:275px;'>Name</th>
                <th><?php DUP_PRO_U::_e('Type'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            foreach ($storages as $storage) :
                /* @var $storage DUP_PRO_Storage_Entity */
                $i++;
                $store_type = $storage->get_storage_type_string();
                ?>
                <tr id='main-view-<?php echo $storage->id ?>' class="storage-row <?php echo ($i % 2) ? 'alternate' : ''; ?>">
                    <td>
                        <?php if ($storage->editable) : ?>
                            <input name="selected_id[]" type="checkbox" value="<?php echo $storage->id; ?>" class="item-chk" />
                        <?php else : ?>
                            <input type="checkbox" disabled="disabled" />
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($storage->editable) : ?>                                                
                            <a href="javascript:void(0);" onclick="DupPro.Storage.Edit('<?php echo $storage->id; ?>')"><b><?php echo $storage->name; ?></b></a>
                            <div class="sub-menu">
                                <a href="javascript:void(0);" onclick="DupPro.Storage.Edit('<?php echo $storage->id; ?>')">Edit</a> |
                                <a href="javascript:void(0);" onclick="DupPro.Storage.View('<?php echo $storage->id; ?>');">Quick View</a> |
                                <a href="javascript:void(0);" onclick="DupPro.Storage.Delete('<?php echo $storage->id; ?>');">Delete</a>
                            </div>
                        <?php else : ?>
                 			<a href="javascript:void(0);" onclick="DupPro.Storage.EditDefault()"><b><?php DUP_PRO_U::_e('Default'); ?></b></a>
                            <div class="sub-menu">
								<a href="javascript:void(0);" onclick="DupPro.Storage.EditDefault()">Edit</a> |
                                <a href="javascript:void(0);" onclick="DupPro.Storage.View('<?php echo $storage->id; ?>');">Quick View</a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $store_type ?></td>
                </tr>
                <tr id='quick-view-<?php echo $storage->id ?>' class='<?php echo ($i % 2) ? 'alternate' : ''; ?> storage-detail'>
                    <td colspan="3">
                        <b><?php DUP_PRO_U::_e('QUICK VIEW') ?></b> <br/>

                        <div>
                            <label><?php DUP_PRO_U::_e('Name') ?>:</label>
                            <?php echo $storage->name ?>
                        </div>
                        <div>
                            <label><?php DUP_PRO_U::_e('Notes') ?>:</label>
                            <?php echo (strlen($storage->notes)) ? $storage->notes : DUP_PRO_U::__('(no notes)'); ?>
                        </div>
                        <div>
                            <label><?php DUP_PRO_U::_e('Type') ?>:</label>
                            <?php echo $storage->get_storage_type_string() ?>
                        </div>	

                        <?php switch ($store_type):
                            case 'Local':  ?>
                                <div>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
                                <?php echo $storage->get_storage_location_string(); ?>
                                </div>
                                <?php break; ?>
							 <?php case 'Dropbox': ?>
                                <div>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
									<?php 
										$url = $storage->get_storage_location_string();
										echo "<a href='{$url}' target='_blank'>" . urldecode($url) . "</a>";
									?>
                                </div>
                                <?php break; ?>
							<?php case 'FTP': ?>
                                <div>
									<label><?php DUP_PRO_U::_e('Server') ?>:</label>
									<?php echo $storage->ftp_server ?>:<?php echo $storage->ftp_port ?> <br/>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
									<?php 
										$url = $storage->get_storage_location_string();
										echo "<a href='{$url}' target='_blank'>" . urldecode($url) . "</a>";
									?>
                                </div>
                                <?php break; ?>
                                <?php case 'SFTP': ?>
                                <div>
									<label><?php DUP_PRO_U::_e('Server') ?>:</label>
									<?php echo $storage->sftp_server ?>:<?php echo $storage->sftp_port ?> <br/>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
									<?php 
										$url = $storage->get_storage_location_string();
										echo "<a href='{$url}' target='_blank'>" . urldecode($url) . "</a>";
									?>
                                </div>
                                <?php break; ?>
							<?php case 'Google Drive': ?>
                                <div>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
									<?php 
																		
									echo $storage->get_storage_location_string();
									?>
                                </div>
                                <?php break; ?>
							<?php case 'Amazon S3': ?>
                                <div>
                                    <label><?php DUP_PRO_U::_e('Location') ?>:</label>
									<?php 
																		
									echo $storage->get_storage_location_string();
									?>
                                </div>
                                <?php break; ?>
							<?php endswitch; ?>
                        <button type="button" class="button" onclick="DupPro.Storage.View('<?php echo $storage->id; ?>');"><?php DUP_PRO_U::_e('Close') ?></button>
                    </td>
                </tr>
<?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="8" style="text-align:right; font-size:12px">						
                    <?php echo DUP_PRO_U::__('Total') . ': ' . $storage_count; ?>
                </th>
            </tr>
        </tfoot>
    </table>

</form>
<?php
	$alert1 = new DUP_PRO_UI_Dialog();
	$alert1->title		= DUP_PRO_U::__('Bulk Action Required');
	$alert1->message	= DUP_PRO_U::__('Please select an action from the "Bulk Actions" drop down menu!');
	$alert1->initAlert();

	$alert2 = new DUP_PRO_UI_Dialog();
	$alert2->title		= DUP_PRO_U::__('Selection Required');
	$alert2->message	= DUP_PRO_U::__('Please select at least one storage to delete!');
	$alert2->initAlert();

	$confirm1 = new DUP_PRO_UI_Dialog();
	$confirm1->title			 = DUP_PRO_U::__('Delete Storage?');
	$confirm1->message			 = DUP_PRO_U::__('Are you sure, you want to delete the selected storage(s)?');
	$confirm1->message			.= '<br/>';
	$confirm1->message			.= DUP_PRO_U::__('<small><i>Note: This action removes all storages.</i></small>');
	$confirm1->progressText      = DUP_PRO_U::__('Removing Storages, Please Wait...');
	$confirm1->jsCallback		 = 'DupPro.Storage.BulkDelete()';
	$confirm1->initConfirm();

    $confirm2 = new DUP_PRO_UI_Dialog();
    $confirm2->title            = $confirm1->title;
	$confirm2->message          = DUP_PRO_U::__('Are you sure, you want to delete this storage?');
    $confirm2->progressText     = $confirm1->progressText;
	$confirm2->jsCallback		= 'DupPro.Storage.DeleteThis(this)';
	$confirm2->initConfirm();
?>
<script>
    jQuery(document).ready(function ($) {

		//Shows detail view
        DupPro.Storage.EditDefault = function () {
            document.location.href = '<?php echo $edit_default_storage_url; ?>';
        }
		
        //Shows detail view
        DupPro.Storage.Edit = function (id) {
            document.location.href = '<?php echo "$edit_storage_url&storage_id="; ?>' + id;
        }

        //Shows detail view
        DupPro.Storage.View = function (id) {
            $('#quick-view-' + id).toggle();
            $('#main-view-' + id).toggle();
        }

        //Delets a single record
        DupPro.Storage.Delete = function (id) {
            <?php $confirm2->showConfirm(); ?>
            $("#<?php echo $confirm2->getID(); ?>-confirm").attr('data-id', id);
            }
        DupPro.Storage.DeleteThis = function (e) {
            var id = $(e).attr('data-id');
            $("#dup-storage-form-action").val('delete');
            $("#dup-selected-storage").val(id);
            $("#dup-storage-form").submit();
        }

        //	Creats a comma seperate list of all selected package ids
        DupPro.Storage.DeleteList = function ()
        {
            var arr = [];

            $("input[name^='selected_id[]']").each(function(i, index) {
                var $this = $(index);

                if ($this.is(':checked')==true) {
                    arr[i] = $this.val();
        }
            });

            return arr.join(',');
        }
        // Bulk action
        DupPro.Storage.BulkAction = function () {
            var list = DupPro.Storage.DeleteList();

            if (list.length == 0) {
                <?php $alert2->showAlert(); ?>
                return;
            }

            var action = $('#bulk_action').val(),
                checked = ($('.item-chk:checked').length > 0);

            if (action != "delete") {
                <?php $alert1->showAlert(); ?>
                return;
            }

            if (checked)
            {
                switch (action) {
                    default:
                        <?php $alert2->showAlert(); ?>
                        break;
                    case 'delete':
                        <?php $confirm1->showConfirm(); ?>
                        break;
                }
            }
        }

        DupPro.Storage.BulkDelete = function ()
                        {
                            jQuery("#dup-storage-form-action").val('bulk-delete');
                            jQuery("#dup-storage-form").submit();
        }

        //Sets all for deletion
        DupPro.Storage.SetAll = function (chkbox) {
            $('.item-chk').each(function () {
                this.checked = chkbox.checked;
            });
        }

        //Name hover show menu
        $("tr.storage-row").hover(
                function () {
                    $(this).find(".sub-menu").show();
                },
                function () {
                    $(this).find(".sub-menu").hide();
                }
        );
    });
</script>
