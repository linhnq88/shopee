<?php
defined("ABSPATH") or die("");

require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/ctrls/ctrl.base.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH . '/classes/class.scan.check.php');

/**
 * Controller for Tools 
 */
class DUP_PRO_CTRL_Tools extends DUP_PRO_CTRL_Base
{

    /**
     *  Init this instance of the object
     */
    function __construct()
    {
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_runScanValidator', array($this, 'runScanValidator'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_deleteInstallerFiles', array($this, 'deleteInstallerFiles'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_migrationUploader', array($this, 'migrationUploader'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_prepareArchiveForImport', array($this, 'prepareArchiveForImport'));
        add_action('wp_ajax_nopriv_DUP_PRO_CTRL_Tools_prepareArchiveForImport', array($this, 'prepareArchiveForImport'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_runScanValidator', array($this, 'runScanValidator'));
        add_action('wp_ajax_DUP_PRO_CTRL_Tools_deleteExistingPackage', array($this, 'deleteExistingFile'));
    }

    /**
     * Calls the ScanValidator and returns a JSON result
     * 
     * @param string $_POST['scan-path']		The path to start scanning from, defaults to DUPLICATOR_WPROOTPATH
     * @param bool   $_POST['scan-recursive]	Recursively  search the path
     * 
     * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_PRO_CTRL_Tools_runScanValidator
     */
    public function runScanValidator($post)
    {
        //@set_time_limit(0);

        // Let's setup execution time on proper way (multiserver supported)
        try {
            if(function_exists('set_time_limit'))
                set_time_limit(0); // unlimited
            else
            {
                if(function_exists('ini_set'))
                    ini_set('max_execution_time', 0); // unlimited
            }

        // there is error inside PHP because of PHP versions and server setup,
        // let's try to made small hack and set some "normal" value if is possible
        } catch (Exception $ex) {
            if(function_exists('set_time_limit'))
                @set_time_limit(3600); // 60 minutes
            else
            {
                if(function_exists('ini_set'))
                    @ini_set('max_execution_time', 3600); //  60 minutes
            }
        }
        
        $post = $this->postParamMerge($post);
        check_ajax_referer($post['action'], 'nonce');

        $result = new DUP_PRO_CTRL_Result($this);

        try {
            //CONTROLLER LOGIC
            $path = isset($post['scan-path']) ? $post['scan-path'] : DUPLICATOR_PRO_WPROOTPATH;
            if (!is_dir($path)) {
                throw new Exception("Invalid directory provided '{$path}'!");
            }
            $scanner = new DUP_PRO_ScanValidator();
            $scanner->recursion = (isset($post['scan-recursive']) && $post['scan-recursive'] != 'false') ? true : false;
            $payload = $scanner->run($path);

            //RETURN RESULT
            $test = ($payload->fileCount > 0) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $result->process($payload, $test);
        } catch (Exception $exc) {
            $result->processError($exc);
        }
    }

    /**
     * Moves the specified archive to the root of the website and extracts the installer-backup.php file
     *
     * @param action $_POST["action"]		The action to use for this request
     * @param action $_POST["nonce"]		The param used for security
     * @param action $_POST["archive_filepath"]	Location of the archive
     * @param string $_FILES["file"]["name"]
     *
     * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_PRO_CTRL_Tools_migrationUploader
     */
    public function prepareArchiveForImport($post)
    {
        DUP_PRO_LOG::trace("prepare archive for import");
       // @set_time_limit(0);
        
        // Let's setup execution time on proper way (multiserver supported)
        try {
            if(function_exists('set_time_limit'))
                set_time_limit(0); // unlimited
            else
            {
                if(function_exists('ini_set'))
                    ini_set('max_execution_time', 0); // unlimited
            }
       
        // there is error inside PHP because of PHP versions and server setup,
        // let's try to made small hack and set some "normal" value if is possible
        } catch (Exception $ex) {
            if(function_exists('set_time_limit'))
                @set_time_limit(3600); // 60 minutes
            else
            {
                if(function_exists('ini_set'))
                    @ini_set('max_execution_time', 3600); //  60 minutes
            }
        }
		
        $post = $this->postParamMerge($post);
        //  check_ajax_referer($post['action'], 'nonce');

        DUP_PRO_LOG::trace("1");
        $result = new DUP_PRO_CTRL_Result($this);

        DUP_PRO_LOG::trace("2");
        $payload = array();

        try {
            DUP_PRO_LOG::trace("3");
            DUP_PRO_LOG::traceObject("post", $post);
            if(isset($post['archive-filename'])) {

                DUP_PRO_LOG::trace("4");
                // 1. Move the archive
                $archive_filepath = DUPLICATOR_PRO_SSDIR_PATH_IMPORTS . '/' . $post['archive-filename'];

                $newArchiveFilepath = DUPLICATOR_PRO_WPROOTPATH . basename($archive_filepath);

                if(!file_exists(DUPLICATOR_PRO_WPROOTPATH . $post['archive-filename']))
                {
                    SnapLibIOU::rename($archive_filepath, $newArchiveFilepath, true);
                }

				DUP_PRO_LOG::trace("4b");
                // 2. Extract the installer
                /*
				if(strpos($post['archive-filename'], '.zip') !== false) {
					$installer_name = str_replace('_archive.zip', '_installer.php', $post['archive-filename']);
				} else {
					$installer_name = str_replace('_archive.daf', '_installer.php', $post['archive-filename']);
				}*/
				$installer_name = 'installer-backup.php';
	            //$extracted_installer_filepath = DUPLICATOR_PRO_WPROOTPATH . '/installer-backup.php';
				$extracted_installer_filepath = rtrim(DUPLICATOR_PRO_WPROOTPATH,'/') . "/{$installer_name}";

                $relativeFilepaths = array();
                $relativeFilepaths[] = 'installer-backup.php';

				DUP_PRO_LOG::trace("4c");
                $fileExt = strtolower(pathinfo($newArchiveFilepath, PATHINFO_EXTENSION));

                if($fileExt == 'zip') {
                    /* @var $global DUP_PRO_Global_Entity */
                    $global = DUP_PRO_Global_Entity::get_instance();

                    // Assumption is that if shell exec zip works so does unzip
                 // RSR TODO: for now always use ziparchive   $useShellZip = ($global->get_auto_zip_mode() === DUP_PRO_Archive_Build_Mode::Shell_Exec);
                    $useShellZip = false;

                    DUP_PRO_Zip_U::extractFiles($newArchiveFilepath, $relativeFilepaths, DUPLICATOR_PRO_WPROOTPATH, $useShellZip);

                } else {
					DUP_PRO_LOG::trace("4d");
                    //DupArchiveEngine::init(new DUP_PRO_Dup_Archive_Logger());
                    //DupArchiveEngine::init(new DUP_PRO_Dup_Archive_Logger());

                    // TODO: DupArchive expand files
                    DupArchiveEngine::expandFiles($newArchiveFilepath, $relativeFilepaths, DUPLICATOR_PRO_WPROOTPATH);
					DUP_PRO_LOG::trace("4e");
                }
                
				DUP_PRO_LOG::trace("4f");
                if(!file_exists($extracted_installer_filepath)) {
                    throw new Exception(DUP_PRO_U::__("Couldn't extract backup installer {$extracted_installer_filepath} from archive!"));
                }

				DUP_PRO_LOG::trace("4g");
                //$final_installer_filepath= DUPLICATOR_PRO_WPROOTPATH . 'installer-'
                SnapLibIOU::rename($extracted_installer_filepath, DUPLICATOR_PRO_IMPORT_INSTALLER_FILEPATH);

				DUP_PRO_LOG::trace("4h");
            }
            else {
                throw new Exception("Archive filepath not set");
            }

            //RETURN RESULT
            $test = ($payload == true) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $result->process($payload);
        } catch (Exception $ex) {
            DUP_PRO_LOG::trace("EXCEPTION: " . $ex->getMessage());
            $result->processError($ex);
        }
    }

    /**
     * Performs the upload process for site migration import
     *
     * @param action $_POST["action"]		The action to use for this request
     * @param action $_POST["nonce"]		The param used for security
     * @param action $_POST["$chunk_size"]	The byte count to read
     * @param string $_FILES["file"]["name"]
     *
     * @notes: Testing = /wp-admin/admin-ajax.php?action=DUP_PRO_CTRL_Tools_migrationUploader
     */
    public function migrationUploader($post)
    {
        // @set_time_limit(0);

        // Let's setup execution time on proper way (multiserver supported)
        try {
            if(function_exists('set_time_limit'))
                set_time_limit(0); // unlimited
            else
            {
                if(function_exists('ini_set'))
                    ini_set('max_execution_time', 0); // unlimited
            }

        // there is error inside PHP because of PHP versions and server setup,
        // let's try to made small hack and set some "normal" value if is possible
        } catch (Exception $ex) {
            if(function_exists('set_time_limit'))
                @set_time_limit(3600); // 60 minutes
            else
            {
                if(function_exists('ini_set'))
                    @ini_set('max_execution_time', 3600); //  60 minutes
            }
        }

        $post = $this->postParamMerge($post);
        check_ajax_referer($post['action'], 'nonce');

        $result = new DUP_PRO_CTRL_Result($this);

        $out = array();

        try {
            if (!file_exists(DUPLICATOR_PRO_SSDIR_PATH_IMPORTS)) {
                SnapLibIOU::mkdir(DUPLICATOR_PRO_SSDIR_PATH_IMPORTS, 0755, true);
            }

            //CONTROLLER LOGIC
            $ext_types = array('daf', 'zip');
            $archive_filename = isset($_FILES["file"]["name"]) ? $_FILES["file"]["name"] : null;
            $temp_filename = isset($_FILES["file"]["tmp_name"]) ? $_FILES["file"]["tmp_name"] : null;
            $chunk_size = isset($_POST["chunk_size"]) ? $_POST["chunk_size"] : 2024;
            $chunk_mode = isset($_POST["chunk_mode"]) ? $_POST["chunk_mode"] : 'chunk';
            $file_ext = pathinfo($archive_filename, PATHINFO_EXTENSION);


            //	$ini_upload = ini_get('upload_max_filesize');
            //	$ini_post   = ini_get('post_max_size');
            //	$ini_upload = SnapLibUtil::convertToBytes($ini_upload);
            //	$ini_post	= SnapLibUtil::convertToBytes($ini_post);

            $chunk = $_POST["chunk"];
            $chunks = $_POST["chunks"];
            $archive_filepath = DUPLICATOR_PRO_SSDIR_PATH_IMPORTS . '/' . $_FILES["file"]["name"];

            //	$out['filename']	= $file_target;
            //	$out['chunk_mode']	= $chunk_mode;
            //	$out['ini_upload']	= $ini_upload;
            //	$out['ini_post']	= $ini_post;

            if (!in_array($file_ext, $ext_types)) {
                throw new Exception("Invalid file extention specified. Please use '.daf' or '.zip'!");
            }

            //CHUNK MODE
            if ($chunk_mode == 'chunked') {

                $archive_part_filepath = "{$archive_filepath}.part";
                $output = @fopen($archive_part_filepath, $chunks ? "ab" : "wb");
                $input = @fopen($temp_filename, "rb");

                if ($output === false) {
                    throw new Exception('Could not write output: ' . $archive_filepath);
                }

                if ($input === false) {
                    throw new Exception('Could not read input:' . $temp_filename);
                }

                while ($buffer = fread($input, $chunk_size)) {
                    fwrite($output, $buffer);
                }

                fclose($output);
                fclose($input);

                $out['mode'] = 'chunk';
                $out['status'] = 'chunking';


                if ($chunk == 0){
                    $read_part = @fopen($archive_part_filepath, 'r');
                    $get_part = fread($read_part, filesize($archive_part_filepath));
                    fclose($read_part);
                    
                    if(preg_match("/\<V\>(.*?)\<\/V\>/Ui", $get_part, $matches))
                        setcookie( 'wp_duplicator_pro_daf_version', $matches[1], (time() + (60*60*24)), COOKIEPATH, COOKIE_DOMAIN );
                }

                if ($chunk == $chunks - 1) {
                    rename($archive_part_filepath, $archive_filepath);
                    $out['status']   = 'chunk complete';
                }

                //DIRECT MODE
            } else {
                move_uploaded_file($temp_filename, $archive_filepath);
                $out['status'] = 'complete';
                $out['mode'] = 'direct';
            }

            // alternative for ZIP extract
            if($file_ext == 'zip')
            {
                $zipUnpack = new ZipArchive;
                if ($zipUnpack->open($archive_filepath) === true)
                {
                    $zip_decode = json_decode($zipUnpack->getFromName('dup-installer/archive.cfg'));
                    $out['zip_version'] = $zip_decode->version_dup;
                    $zipUnpack->close();
                }
            }

            $payload = $out;

            //RETURN RESULT
            $test = ($payload == true) ? DUP_PRO_CTRL_Status::SUCCESS : DUP_PRO_CTRL_Status::FAILED;
            $result->process($payload, $test);
        } catch (Exception $exc) {
            DUP_PRO_LOG::trace("EXCEPTION: " . $exc->getMessage());
            $result->processError($exc);
        }
    }


    public function deleteExistingFile($post){
        $post = $this->postParamMerge($post);
        if(file_exists($post['path']))
        {
            @unlink($post['path']);
        }
    }
}
