<?php
defined("ABSPATH") or die("");
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/lib/DropPHP/DropboxV2Client.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/utilities/class.u.settings.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.brand.entity.php');

if (DUP_PRO_U::PHP53()) {
    require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/net/class.u.gdrive.php');   
    require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/net/class.u.s3.php');
}

if (DUP_PRO_U::PHP55()) {
    require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/lib/phpseclib/class.phpseclib.php');
}

if (DUP_PRO_U::PHP56()) {
	require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/net/class.u.onedrive.php');
}

if (!class_exists('DUP_PRO_Web_Service_Execution_Status')) {

    abstract class DUP_PRO_Web_Service_Execution_Status
    {
        const Pass            = 1;
        const Warn            = 2;
        const Fail            = 3;
        const Incomplete      = 4; // Still more to go
        const ScheduleRunning = 5;

    }
}

if (!class_exists('DUP_PRO_Web_Services')) {

    class DUP_PRO_Web_Services
    {

        public function init()
        {

            $this->add_class_action('wp_ajax_duplicator_pro_package_scan', 'duplicator_pro_package_scan');
            $this->add_class_action('wp_ajax_duplicator_pro_clear_package_scanned_data', 'duplicator_pro_clear_package_scanned_data');
            $this->add_class_action('wp_ajax_duplicator_pro_package_delete', 'duplicator_pro_package_delete');
            $this->add_class_action('wp_ajax_duplicator_pro_reset_user_settings', 'duplicator_pro_reset_user_settings');

            $this->add_class_action('wp_ajax_duplicator_pro_dropbox_send_file_test', 'duplicator_pro_dropbox_send_file_test');
            $this->add_class_action('wp_ajax_duplicator_pro_gdrive_send_file_test', 'duplicator_pro_gdrive_send_file_test');
            $this->add_class_action('wp_ajax_duplicator_pro_sftp_send_file_test', 'duplicator_pro_sftp_send_file_test');
            $this->add_class_action('wp_ajax_duplicator_pro_s3_send_file_test', 'duplicator_pro_s3_send_file_test');
            $this->add_class_action('wp_ajax_duplicator_pro_onedrive_send_file_test', 'duplicator_pro_onedrive_send_file_test');

            $this->add_class_action('wp_ajax_duplicator_pro_ftp_send_file_test', 'duplicator_pro_ftp_send_file_test');
            $this->add_class_action('wp_ajax_duplicator_pro_get_storage_details', 'duplicator_pro_get_storage_details');


            $this->add_class_action('wp_ajax_duplicator_pro_get_trace_log', 'get_trace_log');
            $this->add_class_action('wp_ajax_duplicator_pro_delete_trace_log', 'delete_trace_log');
            $this->add_class_action('wp_ajax_duplicator_pro_get_package_statii', 'get_package_statii');


            $this->add_class_action('wp_ajax_duplicator_pro_process_worker', 'process_worker');
            $this->add_class_action('wp_ajax_nopriv_duplicator_pro_process_worker', 'process_worker');

            $this->add_class_action('wp_ajax_nopriv_duplicator_pro_ping', 'ping');

            $this->add_class_action('wp_ajax_duplicator_pro_gdrive_get_auth_url', 'get_gdrive_auth_url');
            $this->add_class_action('wp_ajax_duplicator_pro_dropbox_get_auth_url', 'get_dropbox_auth_url');
            $this->add_class_action('wp_ajax_duplicator_pro_onedrive_get_auth_url', 'get_onedrive_auth_url');
            $this->add_class_action('wp_ajax_duplicator_pro_onedrive_get_logout_url', 'get_onedrive_logout_url');

            $this->add_class_action('wp_ajax_duplicator_pro_manual_transfer_storage', 'manual_transfer_storage');

            /* Screen-Specific Web Methods */
            $this->add_class_action('wp_ajax_duplicator_pro_packages_details_transfer_get_package_vm', 'packages_details_transfer_get_package_vm');

            /* Granular Web Methods */
            $this->add_class_action('wp_ajax_duplicator_pro_package_stop_build', 'package_stop_build');

            $this->add_class_action('wp_ajax_duplicator_pro_export_settings', 'export_settings');

            /* Flock second process */
            $this->add_class_action('wp_ajax_nopriv_duplicator_pro_try_to_lock_test_file','try_to_lock_test_file');

            $this->add_class_action('wp_ajax_duplicator_pro_brand_delete', 'duplicator_pro_brand_delete');

            /* Quick Fix */
            $this->add_class_action('wp_ajax_duplicator_pro_quick_fix', 'duplicator_pro_quick_fix');
        }

        function process_worker()
        {
            header("HTTP/1.1 200 OK");

            DUP_PRO_LOG::trace("Process worker request");

            DUP_PRO_Package_Runner::process();

            DUP_PRO_LOG::trace("Exiting process worker request");

            echo 'ok';
            exit();
        }

        function ping()
        {
            DUP_PRO_LOG::trace("PING!");
            header("HTTP/1.1 200 OK");
            exit();
        }

        function manual_transfer_storage()
        {
            DUP_PRO_LOG::trace("manual transfer storage");
            $request = stripslashes_deep($_REQUEST);

            $package_id        = (int) $request['package_id'];
            $storage_id_string = $request['storage_ids'];

            // Do a quick check to ensure
            $storage_ids = explode(',', $storage_id_string);

            DUP_PRO_LOG::trace("package_id $storage_id_string $storage_id_string");

            $report              = array();
            $report['succeeded'] = false;
            $report['retval']    = DUP_PRO_U::__('Unknown');

            if (DUP_PRO_Package::is_active_package_present() === false) {
                $package = DUP_PRO_Package::get_by_id($package_id);

                if ($package != null) {
                    if (count($storage_ids > 0)) {
                        foreach ($storage_ids as $storage_id) {
                            if (trim($storage_id) != '') {
                                DUP_PRO_LOG::trace("Manually transferring package to storage location $storage_id");

                                /* @var $upload_info DUP_PRO_Package_Upload_Info */
                                DUP_PRO_LOG::trace("No Uploadinfo exists for storage id $storage_id so creating a new one");
                                $upload_info = new DUP_PRO_Package_Upload_Info();

                                $upload_info->storage_id = $storage_id;

                                array_push($package->upload_infos, $upload_info);
                            } else {
                                DUP_PRO_LOG::trace("Bogus storage ID sent to manual transfer");
                            }
                        }

                        $package->set_status(DUP_PRO_PackageStatus::STORAGE_PROCESSING);
                        $package->timer_start = DUP_PRO_U::getMicrotime();

                        $report['succeeded'] = true;
                        $report['retval']    = null;

                        $package->update();
                    } else {
                        $message          = 'Storage ID count not greater than 0!';
                        DUP_PRO_LOG::trace($message);
                        $report['retval'] = $message;
                    }
                } else {
                    $message          = sprintf(DUP_PRO_U::__('Could not find package ID %d!'), $package_id);
                    DUP_PRO_LOG::trace($message);
                    $report['retval'] = $message;
                }
            } else {
                DUP_PRO_LOG::trace("Trying to queue a transfer for package $package_id but a package is already active!");
                $report['retval'] = ''; // Indicates not to do the popup
            }

            $json = json_encode($report);

            die($json);
        }
        
        /**
         *  DUPLICATOR_PRO_PACKAGE_SCAN
         *  Returns a json scan report object which contains data about the system
         *  
         *  @return json   json report object
         *  @example	   to test: /wp-admin/admin-ajax.php?action=duplicator_pro_package_scan
         */
        function duplicator_pro_package_scan()
        {
            header('Content-Type: application/json');
            $global   = DUP_PRO_Global_Entity::get_instance();
            DUP_PRO_U::hasCapability('export');
            $json     = array();
            $errLevel = error_reporting();

            // Keep the locking file opening and closing just to avoid adding even more complexity
            $locking_file = true;
            if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                $locking_file = fopen(DUP_PRO_Constants::$LOCKING_FILE_FILENAME, 'c+');
            }

            if ($locking_file != false) {
                if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                    $acquired_lock = (flock($locking_file, LOCK_EX | LOCK_NB) != false);
                    ($acquired_lock) ? DUP_PRO_LOG::trace("File lock acquired") : DUP_PRO_LOG::trace("File lock denied");
                } else {
                    $acquired_lock = DUP_PRO_U::getSqlLock();
                }

                if ($acquired_lock) {
                    @set_time_limit(0);
                    error_reporting(E_ERROR);
                    DUP_PRO_U::initStorageDirectory();

                    $package     = DUP_PRO_Package::get_temporary_package();
                    $package->ID = null;
                    $report      = $package->create_scan_report();

                    //After scanner runs save FilterInfo (unreadable, warnings, globals etc)
                    $package->set_temporary_package();

                    $report['Status'] = DUP_PRO_Web_Service_Execution_Status::Pass;

                    // The package has now been corrupted with directories and scans so cant reuse it after this point
                    DUP_PRO_Package::set_temporary_package_member('ScanFile', $package->ScanFile);
                    DUP_PRO_Package::tmp_cleanup();
                    DUP_PRO_Package::set_temporary_package_member('Status', DUP_PRO_PackageStatus::AFTER_SCAN);

                    if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                        DUP_PRO_LOG::trace("File lock released");
                        flock($locking_file, LOCK_UN);
                    } else {
                        DUP_PRO_U::releaseSqlLock();
                    }
                } else {
                    // File is already locked indicating schedule is running
                    $report['Status'] = DUP_PRO_Web_Service_Execution_Status::ScheduleRunning;
                    DUP_PRO_LOG::trace("Already locked when attempting manual build - schedule running");
                }
                if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                    fclose($locking_file);
                }
            } else {
                // Problem opening the locking file report this is a critical error
                $report['Status'] = DUP_PRO_Web_Service_Execution_Status::Fail;

                DUP_PRO_LOG::trace("Problem opening locking file so auto switching to SQL lock mode");
                $global->lock_mode = DUP_PRO_Thread_Lock_Mode::SQL_Lock;
                $global->save();
            }

            //$json = json_encode($report);
            try {
                $json = null;

                if ($global->json_mode == DUP_PRO_JSON_Mode::PHP) {
                    try {
                        $json = DUP_PRO_JSON_U::encode($report);
                    } catch(Exception $jex) {
                        DUP_PRO_LOG::trace("Problem encoding using PHP JSON so switching to custom");
                        
                        $global->json_mode = DUP_PRO_JSON_Mode::Custom;
                        $global->save();
                    }
                }

                if($json === null) {
                    $json = DUP_PRO_JSON_U::customEncode($report);
                }
            } catch (Exception $ex) {
                $json = '{"Status" : 3, "Message" : "Unable to encode to JSON data.  Please validate that no invalid characters exist in your file tree."}';
            }

            //$json = ($json) ? $json : '{"Status" : 3, "Message" : "Unable to encode to JSON data.  Please validate that no invalid characters exist in your file tree."}';
            error_reporting($errLevel);
			
            die($json);
        }

        /**
         *  DUPLICATOR_PRO_QUICK_FIX
         *  Set default quick fix values automaticaly to help user
         *
         *  @return json   A json message about the action.
         * 				   Use console.log to debug from client
         */
        function duplicator_pro_quick_fix()
        {
            try {
                $json = array();
                $post = stripslashes_deep($_POST);

                
                if(isset($post['setup']) && is_array($post['setup']) && count($post['setup']) > 0)
                {

                    $data = array();
                    $find = 0;

                    /******************
                     *  GENERAL SETUP
                     *****************/
                    if(isset($post['setup']['global']) && is_array($post['setup']['global']) && count($post['setup']['global']) > 0)
                    {
                        $global   = DUP_PRO_Global_Entity::get_instance();
                        foreach($post['setup']['global'] as $object=>$value)
                        {
                            $value = DUP_PRO_U::valType($value);
                            if(isset($global->$object))
                            {
                                // Get current setup
                                $current = $global->$object;

                                // If setup is not the same - fix this
                                if($current !== $value)
                                {
                                    // Set new value
                                    $global->$object = $value;
                                    // Check value
                                    $data[$object] = $global->$object;
                                }
                            }
                        }
                    }

                    /******************
                     *  SPECIAL SETUP
                     ******************/
                    if(isset($post['setup']['special']) && is_array($post['setup']['special']) && count($post['setup']['special']) > 0)
                    {
                        $SPECIAL = $post['setup']['special'];
                        if(!(isset($global)))
                            $global   = DUP_PRO_Global_Entity::get_instance();

                        /**
                         * SPECIAL FIX: Package build stuck at 5% or Pending?
                        **/
                        if( isset($SPECIAL['stuck_5percent_pending_fix']) && $SPECIAL['stuck_5percent_pending_fix'] == 1 )
                        {
                            $kickoff    = true;
                            $custom     = false;

                            if($global->ajax_protocol === 'custom') $custom = true;

                            // Do things if SSL is active
                            if(DUP_PRO_U::is_ssl())
                            {
                                if($custom)
                                {
                                    // Set default admin ajax
                                    $custom_ajax_url = admin_url('admin-ajax.php','https');
                                    if($global->custom_ajax_url != $custom_ajax_url)
                                    {
                                        $global->custom_ajax_url = $custom_ajax_url;
                                        $data['custom_ajax_url']=$global->custom_ajax_url;
                                        $kickoff    = false;
                                    }
                                }
                                else
                                {
                                    // Set HTTPS protocol
                                    if($global->ajax_protocol === 'http')
                                    {
                                        $global->ajax_protocol = 'https';
                                        $data['ajax_protocol']=$global->ajax_protocol;
                                        $kickoff    = false;
                                    }
                                }
                            }
                            // SSL is OFF and we must handle that
                            else
                            {
                                if($custom)
                                {
                                    // Set default admin ajax
                                    $custom_ajax_url = admin_url('admin-ajax.php','http');
                                    if($global->custom_ajax_url != $custom_ajax_url)
                                    {
                                        $global->custom_ajax_url = $custom_ajax_url;
                                        $data['custom_ajax_url']=$global->custom_ajax_url;
                                        $kickoff    = false;
                                    }
                                }
                                else
                                {
                                    // Set HTTP protocol
                                    if($global->ajax_protocol === 'https')
                                    {
                                        $global->ajax_protocol = 'http';
                                        $data['ajax_protocol']=$global->ajax_protocol;
                                        $kickoff    = false;
                                    }
                                }
                            }

                            // Set KickOff true if all setups are gone
                            if($kickoff)
                            {
                                if($global->clientside_kickoff !== true)
                                {
                                    $global->clientside_kickoff = true;
                                    $data['clientside_kickoff']=$global->clientside_kickoff;
                                }
                            }
                        }

                    }

                    // Save new property
                    $find = count($data);

                    $json['error']=false;
                    $json['fixed']=$find;

                    if(isset($global) && $find > 0)
                    {
                        $system_global = DUP_PRO_System_Global_Entity::get_instance();
                        if(isset($post['id']) && !empty($post['id']))
                        {
                            $remove_by_id = $system_global->remove_by_id($post['id']);
                            if(false !== $remove_by_id)
                            {
                                $remove_by_id->save();
                            }
                            $json['id']=$post['id'];
                        }
                        $global->save();
                        $json['setup']=$data;
                        $json['recommended_fixes']=count($system_global->recommended_fixes);
                    }
                }
                else
                {
                    $json = array(
                        'error' => 'Object "setup" is not provided or formatted on proper way.',
                    );
                }
                exit(json_encode($json));
            } catch (Exception $e) {
                $json['error'] = "{$e}";
                die(json_encode($json));
            }
        }
        
        /**
         *  DUPLICATOR_PRO_BRAND_DELETE
         *  Deletes the files and database record entries
         *
         *  @return json   A json message about the action.  
         * 				   Use console.log to debug from client
         */
        function duplicator_pro_brand_delete()
        {
            DUP_PRO_U::hasCapability('export');
            try {
                $json = array();
                $post = stripslashes_deep($_POST);

                check_ajax_referer('duplicator_pro_brand_delete', 'nonce');

                $postIDs  = isset($post['duplicator_pro_delid']) ? $post['duplicator_pro_delid'] : null;
                $list     = explode(",", $postIDs);
                $delCount = 0;

                if ($postIDs != null) {
                    foreach ($list as $id) {
                        $brand = DUP_PRO_Brand_Entity::delete_by_id($id);
                        if( $brand ) {
                            $delCount++;
                        }
                    }
                }

                
            } catch (Exception $e) {
                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            $json['ids']     = "{$postIDs}";
            $json['removed'] = $delCount;
            exit(json_encode($json));
        }

        /**
         *  DUPLICATOR_PRO_PACKAGE_DELETE
         *  Deletes the files and database record entries
         *
         *  @return json   A json message about the action.  
         * 				   Use console.log to debug from client
         */
        function duplicator_pro_package_delete()
        {
            DUP_PRO_U::hasCapability('export');

            try {
                $json = array();
                $post = stripslashes_deep($_POST);

                check_ajax_referer('duplicator_pro_package_delete', 'nonce');

                $postIDs  = isset($post['duplicator_pro_delid']) ? $post['duplicator_pro_delid'] : null;
                $list     = explode(",", $postIDs);
                $delCount = 0;

                if ($postIDs != null) {
                    foreach ($list as $id) {
                        $package = DUP_PRO_Package::get_by_id($id);
                        if ($package->delete()) {
                            $delCount++;
                        }
                    }
                }
            } catch (Exception $e) {
                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            $json['ids']     = "{$postIDs}";
            $json['removed'] = $delCount;
            die(json_encode($json));
        }

        /**
         *  DUPLICATOR_PRO_PACKAGE_DELETE
         *  Deletes the files and database record entries
         *
         *  @return json   A json message about the action.
         * 				   Use console.log to debug from client
         */
        function duplicator_pro_reset_user_settings()
        {
            DUP_PRO_U::hasCapability('export');

            $json = array();
            
            try {
                /* @var $global DUP_PRO_Global_Entity */
                $global = DUP_PRO_Global_Entity::get_instance();

                $global->ResetUserSettings();

                // Display gift flag on update
              //  $global->dupHidePackagesGiftFeatures = false;

                $global->save();

            } catch (Exception $e) {
                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }

// DROPBOX METHODS
// <editor-fold>

        function duplicator_pro_get_storage_details()
        {
            DUP_PRO_U::hasCapability('export');

            try {
                $request = stripslashes_deep($_REQUEST);

                $package_id = (int) $request['package_id'];
                $json       = array();
                $package    = DUP_PRO_Package::get_by_id($package_id);

                if ($package != null) {
                    $providers = array();
//                    DUP_PRO_LOG::traceObject("upload infos for $package_id are", $providers);

                    foreach ($package->upload_infos as $upload_info) {
                        /* @var $upload_info DUP_PRO_Package_Upload_Info */
                        $storage = DUP_PRO_Storage_Entity::get_by_id($upload_info->storage_id);

                        /* @var $storage DUP_PRO_Storage_Entity */
                        if ($storage != null) {
                            $storage->storage_location_string = $storage->get_storage_location_string();

                            // Dynamic fields
                            $storage->failed    = $upload_info->failed;
                            $storage->cancelled = $upload_info->cancelled;

                            // Newest storage upload infos will supercede earlier attempts to the same storage
                            $providers[$upload_info->storage_id] = $storage;
                        }
                    }

                    $json['succeeded']         = true;
                    $json['message']           = DUP_PRO_U::__('Retrieved storage information');
                    $json['storage_providers'] = $providers;
                } else {
                    $message = sprintf("DUP_PRO_U::__('Unknown package %1$d')", $package_id);

                    $json['succeeded'] = false;
                    $json['message']   = $message;
                    DUP_PRO_LOG::traceError($message);
                    die(json_encode($json));
                }
            } catch (Exception $e) {
                $json['succeeded'] = false;
                $json['message']   = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }
       
        // Returns status: {['success']={message} | ['error'] message}
        function duplicator_pro_ftp_send_file_test()
        {
            //	DUP_PRO_LOG::traceObject("enter", $_REQUEST);
            DUP_PRO_U::hasCapability('export');

            $json = array();

            try {
                $source_handle = null;
                $dest_handle   = null;

                $request = stripslashes_deep($_REQUEST);

                $storage_folder = $request['storage_folder'];
                $server         = $request['server'];
                $port           = $request['port'];
                $username       = $request['username'];
                $password       = $request['password'];
                $ssl            = ($request['ssl'] == 1);
                $passive_mode   = ($request['passive_mode'] == 1);

                DUP_PRO_LOG::trace("ssl=".DUP_PRO_STR::boolToString($ssl));


                /** -- Store the temp file --* */
                $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

                DUP_PRO_LOG::trace("Created temp file $source_filepath");
                $source_handle = fopen($source_filepath, 'w');
                $rnd           = rand();
                fwrite($source_handle, "$rnd");

                DUP_PRO_LOG::trace("Wrote $rnd to $source_filepath");
                fclose($source_handle);
                $source_handle = null;

                /** -- Send the file -- * */
                $basename = basename($source_filepath);

                /* @var $ftp_client DUP_PRO_FTP_Chunker */
                $ftp_client = new DUP_PRO_FTP_Chunker($server, $port, $username, $password, 15, $ssl, $passive_mode);

                if ($ftp_client->open()) {
                    if (DUP_PRO_STR::startsWith($storage_folder, '/') == false) {
                        $storage_folder = '/'.$storage_folder;
                    }

                    $ftp_directory_exists = $ftp_client->create_directory($storage_folder);

                    if ($ftp_directory_exists) {
                        if ($ftp_client->upload_file($source_filepath, $storage_folder)) {
                            /** -- Download the file --* */
                            $dest_filepath          = tempnam(sys_get_temp_dir(), 'DUP');
                            $remote_source_filepath = "$storage_folder/$basename";
                            DUP_PRO_LOG::trace("About to FTP download $remote_source_filepath to $dest_filepath");

                            if ($ftp_client->download_file($remote_source_filepath, $dest_filepath, false)) {
                                $deleted_temp_file = true;

                                if ($ftp_client->delete($remote_source_filepath) == false) {
                                    DUP_PRO_LOG::traceError("Couldn't delete the remote test");
                                    $deleted_temp_file = false;
                                }

                                $dest_handle = fopen($dest_filepath, 'r');
                                $dest_string = fread($dest_handle, 100);
                                fclose($dest_handle);
                                $dest_handle = null;

                                /* The values better match or there was a problem */
                                if ($rnd == (int) $dest_string) {
                                    DUP_PRO_LOG::trace("Files match!");
                                    if ($deleted_temp_file) {
                                        $json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
                                    } else {
                                        $json['error'] = DUP_PRO_U::__("Successfully stored and retrieved file however coudldn't delete the temp file on the server");
                                    }
                                } else {
                                    DUP_PRO_LOG::traceError("mismatch in files $rnd != $dest_string");
                                    $json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
                                }
                                unlink($source_filepath);
                                unlink($dest_filepath);
                            } else {
                                $ftp_client->delete($remote_source_filepath);
                                $json['error'] = DUP_PRO_U::__('Error downloading file');
                            }
                        } else {
                            $json['error'] = DUP_PRO_U::__('Error uploading file');
                        }
                    } else {
                        $json['error'] = DUP_PRO_U::__("Directory doesn't exist");
                    }
                } else {
                    $json['error'] = DUP_PRO_U::__('Error opening FTP connection');
                }
            } catch (Exception $e) {
                if ($source_handle != null) {
                    fclose($source_handle);
                    unlink($source_filepath);
                }

                if ($dest_handle != null) {
                    fclose($dest_handle);
                    unlink($dest_filepath);
                }

                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }
        
        function duplicator_pro_sftp_send_file_test()
        {
            //	DUP_PRO_LOG::traceObject("enter", $_REQUEST);
            DUP_PRO_U::hasCapability('export');

            $json = array();
            $error = false;
            $request = stripslashes_deep($_REQUEST);
            
            $storage_folder         = $request['storage_folder'];
            $server                 = $request['server'];
            $port                   = $request['port'];
            $username               = $request['username'];
            $password               = $request['password'];
            $private_key            = $request['private_key'];
            $private_key_password   = $request['private_key_password'];
            
            try {
                /** -- Store the temp file --* */
                $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');
                $basename = basename($source_filepath);
                DUP_PRO_LOG::trace("Created temp file $source_filepath");

                if (DUP_PRO_STR::startsWith($storage_folder, '/') == false) {
                    $storage_folder = '/'.$storage_folder;
                }
                
                if (DUP_PRO_STR::endsWith($storage_folder, '/') == false) {
                    $storage_folder = $storage_folder.'/';
                }
                
                $dup_phpseclib = new DUP_PRO_PHPSECLIB();
                $sftp = $dup_phpseclib->connect_sftp_server($server, $port, $username, $password, $private_key, $private_key_password);
                
                if($sftp) {
                    if(!$sftp->file_exists($storage_folder)) {
                        $dup_phpseclib->mkdir_recursive($storage_folder,$sftp);                        
                    }
                    //Try to upload a test file
                    if($sftp->put($storage_folder.$basename, $source_filepath, $dup_phpseclib->source_local_files|$dup_phpseclib->sftp_resume)){
                        DUP_PRO_LOG::trace("Test file uploaded successfully.");
                        $json['success'] = DUP_PRO_U::__('Connection successful');
                        $sftp->delete($storage_folder.$basename);
                        DUP_PRO_LOG::trace("Test file deleted successfully.");
                    }else{
                        DUP_PRO_LOG::trace("Error uploading test file, may be directory not exists or you have no write permissions.");
                        $json['error'] = DUP_PRO_U::__('Error uploading test file.');
                    }                    
                }
            } catch (Exception $e) {
                $json['error'] = "{$e->getMessage()}";
                die(json_encode($json));
            }
            
            die(json_encode($json));
        }
        
        function duplicator_pro_gdrive_send_file_test()
        {
            DUP_PRO_U::hasCapability('export');

            try {
                $source_handle = null;
                $dest_handle   = null;

                $request = stripslashes_deep($_REQUEST);

                $storage_id     = $request['storage_id'];
                $storage_folder = $request['storage_folder'];

                $json = array();

                /* @var $storage DUP_PRO_Storage_Entity */
                $storage = DUP_PRO_Storage_Entity::get_by_id($storage_id);

                if ($storage != null) {
                    $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

                    DUP_PRO_LOG::trace("Created temp file $source_filepath");
                    $source_handle = fopen($source_filepath, 'w');
                    $rnd           = rand();
                    fwrite($source_handle, "$rnd");
                    DUP_PRO_LOG::trace("Wrote $rnd to $source_filepath");
                    fclose($source_handle);
                    $source_handle = null;

                    /** -- Send the file --* */
                    $basename        = basename($source_filepath);
                    $gdrive_filepath = $storage_folder."/$basename";

                    /* @var $google_client Google_Client */
                    $google_client = $storage->get_full_google_client();

                    DUP_PRO_LOG::trace("About to send $source_filepath to $gdrive_filepath on Google Drive");

                    $google_service_drive = new Google_Service_Drive($google_client);

                    $directory_id = DUP_PRO_GDrive_U::get_directory_id($google_service_drive, $storage_folder);

                    /* @var $google_file Google_Service_Drive_DriveFile */
                    $google_file = DUP_PRO_GDrive_U::upload_file($google_client, $source_filepath, $directory_id);

                    if ($google_file != null) {
                        /** -- Download the file --* */
                        $dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');

                        DUP_PRO_LOG::trace("About to download $gdrive_filepath on Google Drive to $dest_filepath");

                        if (DUP_PRO_GDrive_U::download_file($google_client, $google_file, $dest_filepath)) {
                            try {
                                $google_service_drive = new Google_Service_Drive($google_client);

                                $google_service_drive->files->delete($google_file->id);
                            } catch (Exception $ex) {
                                DUP_PRO_LOG::trace("Error deleting temporary file generated on Google File test");
                            }

                            $dest_handle = fopen($dest_filepath, 'r');
                            $dest_string = fread($dest_handle, 100);
                            fclose($dest_handle);
                            $dest_handle = null;

                            /* The values better match or there was a problem */
                            if ($rnd == (int) $dest_string) {
                                DUP_PRO_LOG::trace("Files match! $rnd $dest_string");
                                $json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
                            } else {
                                DUP_PRO_LOG::traceError("mismatch in files $rnd != $dest_string");
                                $json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
                            }
                        } else {
                            DUP_PRO_LOG::traceError("Couldn't download $source_filepath after it had been uploaded");
                        }

                        unlink($dest_filepath);
                    } else {
                        $json['error'] = DUP_PRO_U::__("Couldn't upload file to Google Drive.");
                    }

                    unlink($source_filepath);
                } else {
                    $json['error'] = "Couldn't find Storage ID $storage_id when performing Google Drive file test";
                }
            } catch (Exception $e) {
                if ($source_handle != null) {
                    fclose($source_handle);
                    unlink($source_filepath);
                }

                if ($dest_handle != null) {
                    fclose($dest_handle);
                    unlink($dest_filepath);
                }

                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }

           function duplicator_pro_s3_send_file_test()
        {
            DUP_PRO_U::hasCapability('export');

            try {
                $source_handle = null;
                $dest_handle   = null;

                $request = stripslashes_deep($_REQUEST);

                $storage_folder = $request['storage_folder'];
                $bucket         = $request['bucket'];
                $storage_class  = $request['storage_class'];
                $region         = $request['region'];
                $access_key     = $request['access_key'];
                $secret_key     = $request['secret_key'];

				$storage_folder = rtrim($storage_folder, '/');

                $json = array();

                $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

                DUP_PRO_LOG::trace("Created temp file $source_filepath");
                $source_handle = fopen($source_filepath, 'w');
                $rnd           = rand();
                fwrite($source_handle, "$rnd");
                DUP_PRO_LOG::trace("Wrote $rnd to $source_filepath");
                fclose($source_handle);
                $source_handle = null;

                /** -- Send the file --* */
                $filename = basename($source_filepath);

                $s3_client = DUP_PRO_S3_U::get_s3_client($region, $access_key, $secret_key);

                DUP_PRO_LOG::trace("About to send $source_filepath to $storage_folder in bucket $bucket on S3");

                if (DUP_PRO_S3_U::upload_file($s3_client, $bucket, $source_filepath, $storage_folder, $storage_class)) {
                    $json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
//                    /** -- Download the file --* */
//                    $dest_filepath = tempnam(sys_get_temp_dir(), 'DUP');
//
//                    DUP_PRO_LOG::trace("About to download $filename on S3 to $dest_filepath");

//                    //if (DUP_PRO_GDrive_U::download_file($google_client, $google_file, $dest_filepath))
//                    if (DUP_PRO_S3_U::download_file($s3_client, $bucket, $storage_folder, $filename, $dest_filepath)) {
//                        //public static function delete_file($s3_client, $bucket, $remote_filepath)
                        $remote_filepath = "$storage_folder/$filename";

                        if (DUP_PRO_S3_U::delete_file($s3_client, $bucket, $remote_filepath) == false) {
							DUP_PRO_LOG::trace("Error deleting temporary file generated on S3 File test - {$remote_filepath}");
                        }
//
//                        $dest_handle = fopen($dest_filepath, 'r');
//                        $dest_string = fread($dest_handle, 100);
//                        fclose($dest_handle);
//                        $dest_handle = null;
//
//                        /* The values better match or there was a problem */
//                        if ($rnd == (int) $dest_string) {
//                            DUP_PRO_LOG::trace("Files match! $rnd $dest_string");
//                            $json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
//                        } else {
//                            DUP_PRO_LOG::traceError("mismatch in files $rnd != $dest_string");
//                            $json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
//                        }
//                    } else {
//                        DUP_PRO_LOG::traceError("Couldn't download $source_filepath after it had been uploaded");
//                    }
//
//                    @unlink($dest_filepath);
                } else {
                    $json['error'] = DUP_PRO_U::__("Couldn't upload file to S3.");
                }

				DUP_PRO_LOG::trace("attempting to delete {$source_filepath}");
                @unlink($source_filepath);
            } catch (Exception $e) {
                if ($source_handle != null) {
                    fclose($source_handle);
                    @unlink($source_filepath);
                }

                if ($dest_handle != null) {
                    fclose($dest_handle);
                    @unlink($dest_filepath);
                }

                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }

        function duplicator_pro_dropbox_send_file_test()
        {
            DUP_PRO_U::hasCapability('export');


            try {
                $source_handle = null;

                $request = stripslashes_deep($_REQUEST);

                $storage_id     = $request['storage_id'];
                // $access_token = $request['access_token'];
                $storage_folder = $request['storage_folder'];
                // ob_start();
                // print_r($request);
                // $data=ob_get_clean();
                // file_put_contents(dirname(__FILE__) . '/request.log',$data,FILE_APPEND);

                $full_access = $request['full_access'] == 'true';

                $json = array();

                //this screws things up when returning
                //DUP_PRO_U::enable_implicit_flush();

                $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');

                DUP_PRO_LOG::trace("Created temp file $source_filepath");
                $source_handle = fopen($source_filepath, 'w');
                $rnd           = rand();
                fwrite($source_handle, "$rnd");
                DUP_PRO_LOG::trace("Wrote $rnd to $source_filepath");
                fclose($source_handle);
                $source_handle = null;



                /** -- Send the file --* */
                $basename           = basename($source_filepath);
                $dropbox_filepath   = trim($storage_folder, '/')."/$basename";
                $full_access_string = $full_access ? 'true' : 'false';

                /* @var $$dropbox DropboxClient */
                /* @var $storage DUP_PRO_Storage_Entity */
                $storage = DUP_PRO_Storage_Entity::get_by_id($storage_id);
                // ob_start();
                // print_r($storage);
                // $data=ob_get_clean();
                // file_put_contents(dirname(__FILE__) . '/storage.log',$data,FILE_APPEND);
                $dropbox = $storage->get_dropbox_client($full_access);
                // $dropbox = DUP_PRO_Storage_Entity::get_dropbox_client($full_access);
                if ($dropbox == null) {
                    DUP_PRO_LOG::trace("Couldn't find Storage ID $storage_id when performing Dropbox file test");

                    $json['error'] = "Couldn't find Storage ID $storage_id when performing Dropbox file test";
                    die(json_encode($json));
                }
                DUP_PRO_LOG::trace("About to send $source_filepath to $dropbox_filepath in dropbox");
                // $dropbox->SetAccessToken($access_token);
                $upload_result = $dropbox->UploadFile($source_filepath, $dropbox_filepath);

                $dropbox->Delete($dropbox_filepath);


                /* The values better match or there was a problem */
                if ($dropbox->checkFileHash($upload_result,$source_filepath)) {
                    DUP_PRO_LOG::trace("Files match!");
                    $json['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
                } else {
                    DUP_PRO_LOG::traceError("mismatch in files");
                    $json['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
                }

                unlink($source_filepath);
            } catch (Exception $e) {
                if ($source_handle != null) {
                    fclose($source_handle);
                    unlink($source_filepath);
                }

                if ($dest_handle != null) {
                    fclose($dest_handle);
                    unlink($dest_filepath);
                }

                $json['error'] = "{$e}";
                die(json_encode($json));
            }

            die(json_encode($json));
        }

        function get_trace_log()
        {
            DUP_PRO_LOG::trace("enter");
            DUP_PRO_U::hasCapability('export');

            $request     = stripslashes_deep($_REQUEST);
            $file_path   = DUP_PRO_LOG::getTraceFilepath();
            $backup_path = DUP_PRO_LOG::getBackupTraceFilepath();
            $zip_path    = DUPLICATOR_PRO_SSDIR_PATH."/".DUP_PRO_Constants::ZIPPED_LOG_FILENAME;
            $zipped      = DUP_PRO_Zip_U::zipFile($file_path, $zip_path, true, null, true);

            if ($zipped && file_exists($backup_path)) {
                $zipped = DUP_PRO_Zip_U::zipFile($backup_path, $zip_path, false, null, true);
            }

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Transfer-Encoding: binary");

            $fp = fopen($zip_path, 'rb');

            if (($fp !== false) && $zipped) {
                $zip_filename = basename($zip_path);

                header("Content-Type: application/octet-stream");
                header("Content-Disposition: attachment; filename=\"$zip_filename\";");

                // required or large files wont work
                if (ob_get_length()) {
                    ob_end_clean();
                }

                DUP_PRO_LOG::trace("streaming $zip_path");
                if (fpassthru($fp) === false) {
                    DUP_PRO_LOG::trace("Error with fpassthru for $zip_path");
                }

                fclose($fp);
                @unlink($zip_path);
            } else {
                header("Content-Type: text/plain");
                header("Content-Disposition: attachment; filename=\"error.txt\";");
                if ($zipped === false) {
                    $message = "Couldn't create zip file.";
                } else {
                    $message = "Couldn't open $file_path.";
                }
                DUP_PRO_LOG::trace($message);
                echo $message;
            }

            exit;
        }

        function delete_trace_log()
        {
            DUP_PRO_LOG::trace("enter");
            DUP_PRO_U::hasCapability('export');

            DUP_PRO_LOG::deleteTraceLog();

            exit;
        }       

        function export_settings()
        {
            DUP_PRO_LOG::trace("enter");
            check_ajax_referer('duplicator_pro_import_export_settings', 'nonce');

            DUP_PRO_LOG::trace("after referrer check");
            DUP_PRO_U::hasCapability('export');

            $request = stripslashes_deep($_REQUEST);

            try {
                $settings_u = new DUP_PRO_Settings_U();
                $settings_u->runExport();

                DUP_PRO_U::getDownloadAttachment($settings_u->export_filepath, 'application/json');
            } catch (Exception $ex) {
                // RSR TODO: set the error message to this $this->message = 'Error processing with export:' .  $e->getMessage();
                header("Content-Type: text/plain");
                header("Content-Disposition: attachment; filename=\"error.txt\";");
                $message = DUP_PRO_U::__("{$ex->getMessage()}");
                DUP_PRO_LOG::trace($message);
                echo $message;
            }
            exit;
        }

        // Stop a package build
        // Input: package_id
        // Output:
        //			succeeded: true|false
        //			retval: null or error message
        public function package_stop_build()
        {
            $succeeded  = false;
            $retval     = '';
            $request    = stripslashes_deep($_REQUEST);
            $package_id = (int) $request['package_id'];

            DUP_PRO_LOG::trace("Web service stop build of $package_id");
            $package = DUP_PRO_Package::get_by_id($package_id);

            if ($package != null) {
                DUP_PRO_LOG::trace("set $package->ID for cancel");
                $package->set_for_cancel();

                $succeeded = true;
            } else {
                DUP_PRO_LOG::trace("could not find package so attempting hard delete. Old files may end up sticking around although chances are there isnt much if we couldnt nicely cancel it.");
                $result = DUP_PRO_Package::force_delete($package_id);

                if ($result) {
                    $message   = 'Hard delete success';
                    $succeeded = true;
                } else {
                    $message   = 'Hard delete failure';
                    $succeeded = false;
                    $retval    = $message;
                }

                DUP_PRO_LOG::trace($message);
                $succeeded = $result;
            }

            $json['succeeded'] = $succeeded;
            $json['retval']    = $retval;

            die(json_encode($json));
        }

        // Retrieve view model for the Packages/Details/Transfer screen
        // active_package_id: true/false
        // percent_text: Percent through the current transfer
        // text: Text to display
        // transfer_logs: array of transfer request vms (start, stop, status, message)
        function packages_details_transfer_get_package_vm()
        {
            $request    = stripslashes_deep($_REQUEST);
            $package_id = (int) $request['package_id'];

            $package = DUP_PRO_Package::get_by_id($package_id);
			if($package == null)
				return;

            $json = array();

            $vm = new stdClass();

            /* -- First populate the transfer log information -- */

            // If this is the package being requested include the transfer details
            $vm->transfer_logs = array();

            $active_upload_info = null;

            $storages = DUP_PRO_Storage_Entity::get_all();

            /* @var $upload_info DUP_PRO_Package_Upload_Info */
            foreach ($package->upload_infos as &$upload_info) {
                if ($upload_info->storage_id != DUP_PRO_Virtual_Storage_IDs::Default_Local) {
                    $status      = $upload_info->get_status();
                    $status_text = $upload_info->get_status_text();

                    $transfer_log = new stdClass();

                    if ($upload_info->get_started_timestamp() == null) {
                        $transfer_log->started = DUP_PRO_U::__('N/A');
                    } else {
                        $transfer_log->started = DUP_PRO_DATE::getLocalTimeFromGMTTicks($upload_info->get_started_timestamp());
                    }

                    if ($upload_info->get_stopped_timestamp() == null) {
                        $transfer_log->stopped = DUP_PRO_U::__('N/A');
                    } else {
                        $transfer_log->stopped = DUP_PRO_DATE::getLocalTimeFromGMTTicks($upload_info->get_stopped_timestamp());
                    }

                    $transfer_log->status_text = $status_text;
                    $transfer_log->message     = $upload_info->get_status_message();

                    $transfer_log->storage_type_text = DUP_PRO_U::__('Unknown');
                    /* @var $storage DUP_PRO_Storage_Entity */
                    foreach ($storages as $storage) {
                        if ($storage->id == $upload_info->storage_id) {
                            $transfer_log->storage_type_text = $storage->get_type_text();
                        }
                    }

                    array_unshift($vm->transfer_logs, $transfer_log);

                    if ($status == DUP_PRO_Upload_Status::Running) {
                        if ($active_upload_info != null) {
                            DUP_PRO_LOG::trace("More than one upload info is running at the same time for package {$package->ID}");
                        }

                        $active_upload_info = &$upload_info;
                    }
                }
            }

            /* -- Now populate the activa package information -- */

            /* @var $active_package DUP_PRO_Package */
            $active_package = DUP_PRO_Package::get_next_active_package();

            if ($active_package == null) {
                // No active package
                $vm->active_package_id = -1;
                $vm->text              = DUP_PRO_U::__('No package is building.');
            } else {
                $vm->active_package_id = $active_package->ID;

                if ($active_package->ID == $package_id) {
                    //$vm->is_transferring = (($package->Status >= DUP_PRO_PackageStatus::COPIEDPACKAGE) && ($package->Status < DUP_PRO_PackageStatus::COMPLETE));
                    if ($active_upload_info != null) {
                        $vm->percent_text = "{$active_upload_info->progress}%";
                        $vm->text         = $active_upload_info->get_status_message();
                    } else {
                        // We see this condition at the beginning and end of the transfer so throw up a generic message
                        $vm->percent_text = "";
                        $vm->text         = DUP_PRO_U::__("Synchronizing with server...");
                    }
                } else {
                    $vm->text = DUP_PRO_U::__("Another package is presently running.");
                }

                if ($active_package->is_cancel_pending()) {
                    // If it's getting cancelled override the normal text
                    $vm->text = DUP_PRO_U::__("Cancellation pending...");
                }
            }

            $json['succeeded'] = true;
            $json['retval']    = $vm;

            die(json_encode($json));
        }

        static function get_adjusted_package_status($package)
        {
            /* @var $package DUP_PRO_Package */
            $estimated_progress = ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec) ||
                ($package->ziparchive_mode == DUP_PRO_ZipArchive_Mode::SingleThread);

            /* @var $package DUP_PRO_Package */
            if (($package->Status == DUP_PRO_PackageStatus::ARCSTART) && $estimated_progress) {
                // Amount of time passing before we give them a 1%
                $time_per_percent       = 11;
                $thread_age             = time() - $package->build_progress->thread_start_time;
                $total_percentage_delta = DUP_PRO_PackageStatus::ARCDONE - DUP_PRO_PackageStatus::ARCSTART;

                if ($thread_age > ($total_percentage_delta * $time_per_percent)) {
                    // It's maxed out so just give them the done condition for the rest of the time
                    return DUP_PRO_PackageStatus::ARCDONE;
                } else {
                    $percentage_delta = (int) ($thread_age / $time_per_percent);

                    return DUP_PRO_PackageStatus::ARCSTART + $percentage_delta;
                }
            } else {
                return $package->Status;
            }
        }

        function get_package_statii()
        {
            DUP_PRO_U::hasCapability('export');
            $request        = stripslashes_deep($_REQUEST);
            $packages       = DUP_PRO_Package::get_all();
            $package_statii = array();

            foreach ($packages as $package) {
                /* @var $package DUP_PRO_Package */
                $package_status = new stdClass();

                $package_status->ID = $package->ID;

                $package_status->status					 = self::get_adjusted_package_status($package);
                //$package_status->status = $package->Status;
                $package_status->status_progress		 = $package->get_status_progress();
                $package_status->size					 = $package->get_display_size();

                //TODO active storage
                $active_storage = $package->get_active_storage();

                if ($active_storage != null) {
                    $package_status->status_progress_text = $active_storage->get_action_text();
                } else {
                    $package_status->status_progress_text = '';
                }
           
                array_push($package_statii, $package_status);
            }
            die(json_encode($package_statii));
        }

        function add_class_action($tag, $method_name)
        {
            return add_action($tag, array($this, $method_name));
        }

        function get_dropbox_auth_url()
        {
            $response           = array();
            $response['status'] = -1;

            $dropbox_client = DUP_PRO_Storage_Entity::get_raw_dropbox_client(false);

            $response['dropbox_auth_url'] = $dropbox_client->createAuthUrl();
            $response['status']           = 0;

            $json_response = json_encode($response);

            die($json_response);
        }

        function get_onedrive_auth_url()
        {
            $response = array();
            $response['status'] = -1;
            $onedrive_storage_type = DUP_PRO_Storage_Types::OneDrive;
            DUP_PRO_Log::trace($_REQUEST['business']);
            $auth_arr = DUP_PRO_Onedrive_U::get_onedrive_auth_url_and_client($_REQUEST['business']);

            $response['onedrive_auth_url'] = $auth_arr["url"];;
            $response['status'] = 0;

            $json_response = json_encode($response);

            die($json_response);
        }

        function get_onedrive_logout_url()
        {
            $response = array();
            $response['status'] = -1;
            $storage_id = (isset($_REQUEST['storage_id'])) ? "&storage_id=".$_REQUEST['storage_id'] : '';
            $callback_uri = urlencode(self_admin_url("admin.php?page=duplicator-pro-storage&tab=storage"
                ."&inner_page=edit&onedrive_action=onedrive-revoke-access$storage_id"));

            $response['onedrive_logout_url'] = DUP_PRO_Onedrive_U::get_onedrive_logout_url($callback_uri);
            $response['status'] = 0;

            $json_response = json_encode($response);

            die($json_response);
        }

        function duplicator_pro_onedrive_send_file_test()
        {
            DUP_PRO_U::hasCapability('export');

            try{
                $response = array();
                $response["started"] = true;
                $storage_id     = $_REQUEST['storage_id'];

                $storage = DUP_PRO_Storage_Entity::get_by_id($storage_id);

                $source_filepath = tempnam(sys_get_temp_dir(), 'DUP');
                $file_name = basename($source_filepath);
                DUP_PRO_LOG::trace("Created temp file $source_filepath");
                $source_handle = fopen($source_filepath, 'rw+b');
                $rnd           = rand();
                fwrite($source_handle, "$rnd");
                if(!rewind($source_handle)){
                    $response['error'] = "Couldn't rewind handle.";
                }
                DUP_PRO_LOG::trace("Wrote $rnd to $source_filepath");

                $parent = $storage->get_onedrive_storage_folder();

                if($parent !== null){
                    $response['parent ID'] = $parent->getId();
                    $onedrive = $storage->get_onedrive_client();
                    $test_file = $parent->createFile($file_name,$source_handle);
                    try{
                        if($test_file->sha1CheckSum($source_filepath)){
                            $response['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
                            $onedrive->deleteDriveItem($test_file->getId());
                        }else{
                            $response['error'] = DUP_PRO_U::__('There was a problem storing or retrieving the temporary file on this account.');
                        }
                    }catch (Exception $exception){
                        if($exception->getCode() == 404 && $onedrive->isBusiness()){
                            $response['success'] = DUP_PRO_U::__('Successfully stored and retrieved file');
                            $onedrive->deleteDriveItem($test_file->getId());
                        }else{
                            $response['error'] = DUP_PRO_U::__('An error happened. Error message: '.$exception->getMessage());
                        }

                    }

                }
                fclose($source_handle);
                unlink($source_filepath);
                die(json_encode($response));
            }catch(Exception $e){
                $response['error'] = "{$e}";
                die(json_encode($response));
            }
        }

        function get_gdrive_auth_url()
        {
            $response           = array();
            $response['status'] = -1;

            if (DUP_PRO_U::PHP53()) {
                $google_client = DUP_PRO_GDrive_U::get_raw_google_client();

                $response['gdrive_auth_url'] = $google_client->createAuthUrl();
                $response['status']          = 0;
            } else {
                DUP_PRO_LOG::trace("Attempt to call a google client method when server is not PHP 5.3!");
                $response['status'] = -2;
            }

            $json_response = json_encode($response);

            die($json_response);
        }

        function try_to_lock_test_file()
        {
            $test_file_path = DUPLICATOR_PRO_SSDIR_PATH_TMP.'/lock_test.txt';
            $fp = fopen($test_file_path, "w+");

            if(!flock($fp,LOCK_EX|LOCK_NB,$eWouldBlock) || $eWouldBlock){
                echo DUP_PRO_File_Lock_Check::Flock_Fail;
            }else{
                echo DUP_PRO_File_Lock_Check::Flock_Success;
            }

            fclose($fp);

            die();
        }
    }
}
// </editor-fold>
//DO NOT ADD A CARRIAGE RETURN BEYOND THIS POINT (headers issue)!!
