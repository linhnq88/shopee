<?php
defined("ABSPATH") or die("");

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.base.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.scan.check.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/utilities/class.u.json.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/package/class.pack.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/entities/class.package.template.entity.php');

/**
 * Controller for Tools
 */
class DUP_PRO_CTRL_Package extends DUP_PRO_CTRL_Base
{
	/**
     *  Init this instance of the object
     */
	function __construct()
	{
		add_action('wp_ajax_DUP_PRO_CTRL_Package_addQuickFilters', array($this, 'addQuickFilters'));
		add_action('wp_ajax_DUP_PRO_CTRL_Package_getPackageFile', array($this, 'getPackageFile'));
		add_action('wp_ajax_DUP_PRO_CTRL_Package_switchDupArchiveNotice', array($this, 'switchDupArchiveNotice'));
        add_action('wp_ajax_DUP_PRO_CTRL_Package_toggleGiftFeatureButton', array($this, 'toggleGiftFeatureButton'));
	}


	/**
     * Removed all reserved installer files names
	 *
	 * @param string $_POST['dir_paths']		A semi-colon separated list of dir paths
	 * @param string $_POST['file_paths']		A semi-colon separated list of file paths
	 *
	 * @return string	Returns all of the active directory filters as a ";" separated string
     */
	public function addQuickFilters($post)
	{
		/* @var $template DUP_PRO_Package_Template_Entity*/

		$post = $this->postParamMerge($post);
		check_ajax_referer($post['action'], 'nonce');
		$result = new DUP_PRO_CTRL_Result($this);

		try {
			//CONTROLLER LOGIC

            // Need to update both the template and the temporary package because:
            // 1) We need to preserve preferences of this build for future manual builds - the manual template is used for this.
            // 2) Temporary package is used during this build - keeps all the settings/storage information.  Will be inserted into the package table after they ok the scan results.
			$template = DUP_PRO_Package_Template_Entity::get_manual_template();

			$template->archive_filter_dirs = DUP_PRO_Archive::parseDirectoryFilter("{$template->archive_filter_dirs};{$post['dir_paths']}");
			$template->archive_filter_files = DUP_PRO_Archive::parseFileFilter("{$template->archive_filter_files};{$post['file_paths']}");
            $template->archive_filter_on = 1;
		
            $template->save();

            /* @var $temporary_package DUP_PRO_Package */
            $temporary_package = DUP_PRO_Package::get_temporary_package();

            $temporary_package->Archive->FilterDirs = $template->archive_filter_dirs;
            $temporary_package->Archive->FilterFiles = $template->archive_filter_files;
            $temporary_package->Archive->FilterOn = 1;

            $temporary_package->set_temporary_package();

			//Result
			$payload['filter-dirs'] = $temporary_package->Archive->FilterDirs;
			$payload['filter-files'] = $temporary_package->Archive->FilterFiles;

            //RETURN RESULT
			//$test = ($success) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $test = DUP_PRO_CTRL_Status::SUCCESS;
			$result->process($payload, $test);

		} catch (Exception $exc) {
			$result->processError($exc);
		}
	}

	/**
     * Download the requested package file
	 *
	 * @param string $_POST['which']
	 * @param string $_POST['package_id']
	 *
	 * @return downloadable file
     */
	public function getPackageFile($custom_params)
	{
		$params = $this->postParamMerge($custom_params);
		$params = $this->getParamMerge($params);
		check_ajax_referer($params['action'], 'nonce');
		$result = new DUP_PRO_CTRL_Result($this);

		try {
			//CONTROLLER LOGIC

            DUP_PRO_U::hasCapability('export');

            $global		= DUP_PRO_Global_Entity::get_instance();
            $request    = stripslashes_deep($_REQUEST);
            $which      = (int) $request['which'];
            $package_id = (int) $request['package_id'];
            $package	= DUP_PRO_Package::get_by_id($package_id);
            $is_binary	= ($which != DUP_PRO_Package_File_Type::Log);
            $file_path	= $package->get_local_package_file($which);

			//OUTPUT: Installer, Archive, SQL File
            if ($is_binary) {
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private", false);
                header("Content-Transfer-Encoding: binary");

                if ($file_path != null) {
                    $fp = fopen($file_path, 'rb');
                    if ($fp !== false) {

                        if ($which == DUP_PRO_Package_File_Type::Installer) {
                            $file_name = $global->installer_base_name;
                        } else {
                            $file_name = basename($file_path);
                        }

                        header("Content-Type: application/octet-stream");
						header("Content-Disposition: attachment; filename=\"{$file_name}\";");

                        @ob_end_clean(); // required or large files wont work
                        DUP_PRO_LOG::trace("streaming $file_path");

                        if (fpassthru($fp) === false) {
							DUP_PRO_LOG::trace("Error with fpassthru for {$file_path}");
                        }
                        fclose($fp);
						die(); //Supress additional ouput
                    } else {
                        header("Content-Type: text/plain");
                        header("Content-Disposition: attachment; filename=\"error.txt\";");
                        $message = "Couldn't open $file_path.";
                        DUP_PRO_LOG::trace($message);
                        echo $message;
                    }
                } else {
                    $message = DUP_PRO_U::__("Couldn't find a local copy of the file requested.");

                    header("Content-Type: text/plain");
                    header("Content-Disposition: attachment; filename=\"error.txt\";");

                    // Report that we couldn't find the file
                    DUP_PRO_LOG::trace($message);
                    echo $message;
                }

			//OUTPUT: Log File
            } else {
                if ($file_path != null) {
					header("Content-Type: text/plain");
                    $text = file_get_contents($file_path);
                    //echo nl2br($text);
					die($text);
                } else {
                    $message = DUP_PRO_U::__("Couldn't find a local copy of the file requested.");
                    echo $message;
                }
            }

			//RETURN RESULT
			//This controller should not return a JSON response because its forcing a file download.
			//$payload['message'] = $message;
			//$payload['status'] = true;
			//$test = ($success) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            //$test = DUP_PRO_CTRL_Status::SUCCESS;
			//$result->process($payload, $test);

		} catch (Exception $exc) {
			$result->processError($exc);
		}
	}

	/**
     * Enables the DupArchive setting and hides the notice box on package build step 1
	 *
	 * @param string $_POST['enable_duparchive']	A bool to enable DA
	 *
	 * @return bool		Returns true if the DupArchive flag is set to hide
     */
	public function switchDupArchiveNotice()
	{
		DUP_PRO_LOG::trace("switch duparchive notice");
		$post = $this->postParamMerge($post);
		check_ajax_referer($post['action'], 'nonce');
		$result = new DUP_PRO_CTRL_Result($this);

		try {
			//CONTROLLER LOGIC

			$global = DUP_PRO_Global_Entity::get_instance();

			$global->notices->dupArchiveSwitch = false;

			if ($post['enable_duparchive'] == 'true') {
				
				$global->archive_build_mode = DUP_PRO_Archive_Build_Mode::DupArchive;
				$global->archive_compression = true;
			}

			$global->save();
			
            //RETURN RESULT
			$status = ($success) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $status = DUP_PRO_CTRL_Status::SUCCESS;
			$result->process(null, $status);
			
		} catch (Exception $exc) {
			$result->processError($exc);
		}
	}

	/**
     * Toggles the feature gift icon on the packages page.  This should only show for new features and
	 * once its clicked should hide.
	 *
	 * @param string $_POST['hide_gift_btn']	A bool to hide the gift button
	 *
	 * @return bool		Returns true if the Button is set to be hidden
     */
	public function toggleGiftFeatureButton($post)
	{
		DUP_PRO_LOG::trace("switch duparchive notice");
		$post = $this->postParamMerge($post);
		check_ajax_referer($post['action'], 'nonce');
		$result = new DUP_PRO_CTRL_Result($this);

		try {
			//CONTROLLER LOGIC
			$global = DUP_PRO_Global_Entity::get_instance();
			$global->notices->dupArchiveSwitch = false;

			if ($post['hide_gift_btn'] == 'true') {
				$global->dupHidePackagesGiftFeatures = true;
			}

			$success = $global->save();

            //RETURN RESULT
			$status = ($success) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $status = DUP_PRO_CTRL_Status::SUCCESS;
			$result->process(null, $status);

		} catch (Exception $exc) {
			$result->processError($exc);
		}
	}


}
