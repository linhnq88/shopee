<?php
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_PRO_VERSION')) exit; // Exit if accessed directly

require_once (DUPLICATOR_PRO_PLUGIN_PATH.'classes/entities/class.system.global.entity.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH.'classes/utilities/class.u.shell.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH.'classes/class.archive.config.php');
require_once (DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.brand.entity.php');

class DUP_PRO_Installer
{
    public $File;
    public $Size = 0;
    //SETUP
    public $OptsSecureOn;
    public $OptsSecurePass;
    public $OptsSkipScan;
    //BASIC
    public $OptsDBHost;
    public $OptsDBName;
    public $OptsDBUser;
    //CPANEL
    public $OptsCPNLHost     = '';
    public $OptsCPNLUser     = '';
    public $OptsCPNLPass     = '';
    public $OptsCPNLEnable   = false;
    public $OptsCPNLConnect  = false;
    //CPANEL DB
    //1 = Create New, 2 = Connect Remove
    public $OptsCPNLDBAction = 'create';
    public $OptsCPNLDBHost   = '';
    public $OptsCPNLDBName   = '';
    public $OptsCPNLDBUser   = '';
    //ADVANCED OPTS
    public $OptsCacheWP;
    public $OptsCachePath;
    //PROTECTED
    protected $Package;

    public $numFilesAdded = 0;
    public $numDirsAdded = 0;

    //CONSTRUCTOR
    function __construct($package)
    {
        $this->Package = $package;
    }

    public function get_safe_filepath()
    {
        return DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH."/{$this->File}");
    }

    public function get_url()
    {
        return DUPLICATOR_PRO_SSDIR_URL."/{$this->File}";
    }

    public function build($package, $build_progress)
    {
        /* @var $package DUP_PRO_Package */
        DUP_PRO_LOG::trace("building installer");

        $this->Package = $package;
        $success       = false;

        if ($this->create_enhanced_installer_files()) {
            $success = $this->add_extra_files($package);
        }

        if ($success) {
            $build_progress->installer_built = true;
        } else {
            $build_progress->failed = true;
        }
    }

    private function create_enhanced_installer_files()
    {
        $success = false;

        if ($this->create_enhanced_installer()) {
            $success = $this->create_archive_config_file();
        }

        return $success;
    }

    private function create_enhanced_installer()
    {
        $global = DUP_PRO_Global_Entity::get_instance();

        $success = true;

		$archive_filepath        = DUP_PRO_U::safePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
        $installer_filepath     = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_{$global->installer_base_name}";
        $template_filepath      = DUPLICATOR_PRO_PLUGIN_PATH.'/installer/installer.tpl';
        $mini_expander_filepath = DUPLICATOR_PRO_PLUGIN_PATH.'/lib/dup_archive/classes/class.duparchive.mini.expander.php';

        // Replace the @@ARCHIVE@@ token
        $installer_contents = file_get_contents($template_filepath);

        if ($this->Package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::DupArchive) {
            $mini_expander_string = file_get_contents($mini_expander_filepath);

            if ($mini_expander_string === false) {
                DUP_PRO_Log::error(DUP_PRO_U::__('Error reading DupArchive mini expander'), DUP_PRO_U::__('Error reading DupArchive mini expander'), false);
                return false;
            }
        } else {
            $mini_expander_string = '';
        }

        $search_array  = array('@@ARCHIVE@@', '@@VERSION@@', '@@ARCHIVE_SIZE@@', '@@DUPARCHIVE_MINI_EXPANDER@@');
        $replace_array = array($this->Package->Archive->File, DUPLICATOR_PRO_VERSION, @filesize($archive_filepath), $mini_expander_string);

        $installer_contents = str_replace($search_array, $replace_array, $installer_contents);

        if (@file_put_contents($installer_filepath, $installer_contents) === false) {
            DUP_PRO_Log::error(DUP_PRO_U::__('Error writing installer contents'), DUP_PRO_U::__("Couldn't write to $installer_filepath"), false);
            $success = false;
        }

        if ($success) {
            $storePath  = "{$this->Package->StorePath}/{$this->File}";
            $this->Size = @filesize($storePath);
        }

        return $success;
    }

    /* Create archive.cfg file */
    private function create_archive_config_file()
    {
        global $wpdb;

        $global                  = DUP_PRO_Global_Entity::get_instance();
        $success                 = true;
        $archive_config_filepath = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_archive.cfg";
        $ac                      = new DUP_PRO_Archive_Config();
        $extension               = strtolower($this->Package->Archive->Format);

        //READ-ONLY: COMPARE VALUES
        $ac->created     = $this->Package->Created;
        $ac->version_dup = DUPLICATOR_PRO_VERSION;
        $ac->version_wp  = $this->Package->VersionWP;
        $ac->version_db  = $this->Package->VersionDB;
        $ac->version_php = $this->Package->VersionPHP;
        $ac->version_os  = $this->Package->VersionOS;
        $ac->dbInfo      = $this->Package->Database->info;

        //READ-ONLY: GENERAL
        $ac->installer_base_name  = $global->installer_base_name;
        $ac->package_name         = "{$this->Package->NameHash}_archive.{$extension}";
        $ac->package_notes        = $this->Package->Notes;
        $ac->url_old              = get_option('siteurl');
        $ac->opts_delete          = json_encode($GLOBALS['DUPLICATOR_PRO_OPTS_DELETE']);
        $ac->blogname             = esc_html(get_option('blogname'));
        $ac->wproot               = DUPLICATOR_PRO_WPROOTPATH;
        $ac->relative_content_dir = str_replace(ABSPATH, '', WP_CONTENT_DIR);
		$ac->exportOnlyDB		  = $this->Package->Archive->ExportOnlyDB;
		$ac->wplogin_url		  = wp_login_url();

        //PRE-FILLED: GENERAL
        $ac->secure_on   = $this->Package->Installer->OptsSecureOn;
        $ac->secure_pass = DUP_PRO_Crypt::scramble(base64_decode($this->Package->Installer->OptsSecurePass));
        $ac->skipscan    = $this->Package->Installer->OptsSkipScan;
        $ac->dbhost      = $this->Package->Installer->OptsDBHost;
        $ac->dbname      = $this->Package->Installer->OptsDBName;
        $ac->dbuser      = $this->Package->Installer->OptsDBUser;
        $ac->dbpass      = '';
        $ac->cache_wp    = $this->Package->Installer->OptsCacheWP;
        $ac->cache_path  = $this->Package->Installer->OptsCachePath;

        //PRE-FILLED: CPANEL
        $ac->cpnl_host     = $this->Package->Installer->OptsCPNLHost;
        $ac->cpnl_user     = $this->Package->Installer->OptsCPNLUser;
        $ac->cpnl_pass     = $this->Package->Installer->OptsCPNLPass;
        $ac->cpnl_enable   = $this->Package->Installer->OptsCPNLEnable;
        $ac->cpnl_connect  = $this->Package->Installer->OptsCPNLConnect;
        $ac->cpnl_dbaction = $this->Package->Installer->OptsCPNLDBAction;
        $ac->cpnl_dbhost   = $this->Package->Installer->OptsCPNLDBHost;
        $ac->cpnl_dbname   = $this->Package->Installer->OptsCPNLDBName;
        $ac->cpnl_dbuser   = $this->Package->Installer->OptsCPNLDBUser;

        //MULTISITE
        $ac->mu_mode = DUP_PRO_MU::getMode();
        if ($ac->mu_mode == 0) {
            $ac->wp_tableprefix = $wpdb->base_prefix;
        } else {
            $ac->wp_tableprefix = $wpdb->base_prefix;
        }

        $ac->mu_generation = DUP_PRO_MU::getGeneration();
        $ac->mu_is_filtered = !empty($this->Package->Multisite->FilterSites) ? true : false;

        $ac->subsites = DUP_PRO_MU::getSubsites($this->Package->Multisite->FilterSites);
        if ($ac->subsites === false) {
            $success = false;
        }

        //BRAND
        $ac->brand   = $this->the_brand_setup($this->Package->Brand_ID);

        //LICENSING
        $ac->license_limit = $global->license_limit;
        
        $json = json_encode($ac);

        DUP_PRO_LOG::traceObject('json', $json);

        if (file_put_contents($archive_config_filepath, $json) === false) {
            DUP_PRO_Log::error("Error writing archive config", "Couldn't write archive config at $archive_config_filepath", false);
            $success = false;
        }

        return $success;
    }

    private function the_brand_setup($id)
    {
        // initialize brand
        $brand = DUP_PRO_Brand_Entity::get_by_id((int)$id);

        // Prepare default fields
        $brand_property_default = array(
            'logo' => '',
            'enabled' => false,
            'style' => array()
        );

        // Returns property
        $brand_property = array();

        // Set logo and hosted images path
        if(isset($brand->logo)){
            $brand_property['logo'] = $brand->logo;
            // Find images
            preg_match_all('/<img.*?src="([^"]+)".*?>/', $brand->logo, $arr_img, PREG_PATTERN_ORDER); // https://regex101.com/r/eEyf5S/2
            // Fix hosted image url path
            if( isset($arr_img[1]) && count($brand->attachments) > 0 && count($arr_img[1]) === count($brand->attachments) )
            {
                foreach($arr_img[1] as $i=>$find)
                {
                    $brand_property['logo'] = str_replace($find, 'assets/images/brand'.$brand->attachments[$i], $brand_property['logo']);
                }
            }
            $brand_property['logo'] = stripslashes($brand_property['logo']);
        }

        // Set is enabled
        if(!empty($brand_property['logo']) && isset($brand->active) && $brand->active)
            $brand_property['enabled'] = true;

        // Let's include style
        if(isset($brand->style)){
            $brand_property['style'] = $brand->style;
        }

        // Merge data properly
        if(function_exists("array_replace") && version_compare(phpversion(), '5.3.0', '>='))
			$brand_property = array_replace($brand_property_default, $brand_property); // (PHP 5 >= 5.3.0)
		else
			$brand_property = array_merge($brand_property_default, $brand_property); // (PHP 5 < 5.3.0)

        return $brand_property;
    }

    /**
     *  createZipBackup
     *  Puts an installer zip file in the archive for backup purposes.
     */
    private function add_extra_files($package)
    {
        $success                 = false;
        $global                  = DUP_PRO_Global_Entity::get_instance();
        $installer_filepath      = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_{$global->installer_base_name}";
        $scan_filepath           = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_scan.json";
        $sql_filepath            = DUP_PRO_U::safePath("{$this->Package->StorePath}/{$this->Package->Database->File}");
        $archive_filepath        = DUP_PRO_U::safePath("{$this->Package->StorePath}/{$this->Package->Archive->File}");
        $archive_config_filepath = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP)."/{$this->Package->NameHash}_archive.cfg";

        if (file_exists($installer_filepath) == false) {
            DUP_PRO_Log::error("Installer $installer_filepath not present", '', false);
            return false;
        }

        if (file_exists($sql_filepath) == false) {
            DUP_PRO_Log::error("Database SQL file $sql_filepath not present", '', false);
            return false;
        }

        if (file_exists($archive_config_filepath) == false) {
            DUP_PRO_Log::error("Archive configuration file $archive_config_filepath not present", '', false);
            return false;
        }

        if ($package->Archive->file_count != 2) {
            DUP_PRO_LOG::trace("Doing archive file check");
            // Only way it's 2 is if the root was part of the filter in which case the archive won't be there
            if (file_exists($archive_filepath) == false) {
                $error_text = DUP_PRO_U::__("Zip archive {$archive_filepath} not present.");
                //$fix_text   = DUP_PRO_U::__("Go to: Settings > Packages Tab > Set Archive Engine to ZipArchive.");
                $fix_text   = DUP_PRO_U::__("Click on button to set archive engine to DupArchive.");

                DUP_PRO_Log::error("$error_text. **RECOMMENDATION: $fix_text", '', false);

                $system_global = DUP_PRO_System_Global_Entity::get_instance();
                //$system_global->add_recommended_text_fix($error_text, $fix_text);
                $system_global->add_recommended_quick_fix($error_text, $fix_text, 'global : {archive_build_mode:3}');
                $system_global->save();

                return false;
            }
        }

        DUP_PRO_LOG::trace("Add extra files: Current build mode = ".$package->build_progress->current_build_mode);

        if ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::ZipArchive) {
            $success = $this->add_extra_files_using_ziparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath, $package->build_progress->current_build_compression);
        } else if ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec) {
            $success = $this->add_extra_files_using_shellexec($archive_filepath, $installer_filepath, $scan_filepath, $sql_filepath, $archive_config_filepath, $package->build_progress->current_build_compression);
            // Adding the shellexec fail text fix
            if(!$success) {
                $error_text = DUP_PRO_U::__("Problem adding installer to archive");
                $fix_text   = DUP_PRO_U::__("Click on button to set archive engine to DupArchive.");
                
                $system_global = DUP_PRO_System_Global_Entity::get_instance();            
                $system_global->add_recommended_quick_fix($error_text, $fix_text, 'global : {archive_build_mode:3}');
                $system_global->save();
            }
        } else if ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::DupArchive) {
            $success = $this->add_extra_files_using_duparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath);
        }

        // No sense keeping the archive config around
        @unlink($archive_config_filepath);

        $package->Archive->Size = @filesize($archive_filepath);

        return $success;
    }

    private function add_extra_files_using_duparchive($installer_filepath, $scan_filepath, $sql_filepath, $archive_filepath, $archive_config_filepath)
    {
        $success = false;

        try {
			$htaccess_filepath = DUPLICATOR_PRO_WPROOTPATH . '.htaccess';
			$wpconfig_filepath = DUPLICATOR_PRO_WPROOTPATH . 'wp-config.php';

            $logger = new DUP_PRO_Dup_Archive_Logger();

            DupArchiveEngine::init($logger, 'DUP_PRO_LOG::profile');

            DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $scan_filepath, DUPLICATOR_PRO_EMBEDDED_SCAN_FILENAME);
            $this->numFilesAdded++;

			if(file_exists($htaccess_filepath)) {
				try
				{
					DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $htaccess_filepath, DUPLICATOR_PRO_HTACCESS_ORIG_FILENAME);
					$this->numFilesAdded++;
				}
				catch (Exception $ex)
				{
					// Non critical so bury exception
				}
			}

			if(file_exists($wpconfig_filepath)) {
				DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $wpconfig_filepath, DUPLICATOR_PRO_WPCONFIG_ARK_FILENAME);
				$this->numFilesAdded++;
			}

            $this->add_installer_files_using_duparchive($archive_filepath, $installer_filepath, $archive_config_filepath);

            $success = true;
        } catch (Exception $ex) {
            DUP_PRO_Log::error("Error adding installer files to archive. ".$ex->getMessage());
        }

        return $success;
    }

    private function add_installer_files_using_duparchive($archive_filepath, $installer_filepath, $archive_config_filepath)
    {
        /* @var $global DUP_PRO_Global_Entity */
        $global                    = DUP_PRO_Global_Entity::get_instance();
        $installer_backup_filename = $global->get_installer_backup_filename();

		$installer_backup_filepath = dirname($installer_filepath) . "/{$installer_backup_filename}";

        DUP_PRO_LOG::trace('Adding enhanced installer files to archive using DupArchive');

		SnapLibIOU::copy($installer_filepath, $installer_backup_filepath);

		DupArchiveEngine::addFileToArchiveUsingBaseDirST($archive_filepath, dirname($installer_backup_filepath), $installer_backup_filepath);

		SnapLibIOU::rm($installer_backup_filepath);

        $this->numFilesAdded++;

        $base_installer_directory = DUPLICATOR_PRO_PLUGIN_PATH.'installer';
        $installer_directory      = "$base_installer_directory/dup-installer";

        $counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $installer_directory, $base_installer_directory, true);
        $this->numFilesAdded += $counts->numFilesAdded;
        $this->numDirsAdded += $counts->numDirsAdded;

        $archive_config_relative_path = 'dup-installer/archive.cfg';

        DupArchiveEngine::addRelativeFileToArchiveST($archive_filepath, $archive_config_filepath, $archive_config_relative_path);
        $this->numFilesAdded++;

        // Include dup archive
        $duparchive_lib_directory = DUPLICATOR_PRO_PLUGIN_PATH.'lib/dup_archive';
        $duparchive_lib_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $duparchive_lib_directory, DUPLICATOR_PRO_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $duparchive_lib_counts->numFilesAdded;
        $this->numDirsAdded += $duparchive_lib_counts->numDirsAdded;

        // Include snaplib
        $snaplib_directory = DUPLICATOR_PRO_PLUGIN_PATH.'lib/snaplib';
        $snaplib_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $snaplib_directory, DUPLICATOR_PRO_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $snaplib_counts->numFilesAdded;
        $this->numDirsAdded += $snaplib_counts->numDirsAdded;

        // Include fileops
        $fileops_directory = DUPLICATOR_PRO_PLUGIN_PATH.'lib/fileops';
        $fileops_counts = DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $fileops_directory, DUPLICATOR_PRO_PLUGIN_PATH, true, 'dup-installer/');
        $this->numFilesAdded += $fileops_counts->numFilesAdded;
        $this->numDirsAdded += $fileops_counts->numDirsAdded;
    }

    private function add_extra_files_using_ziparchive($installer_filepath, $scan_filepath, $sql_filepath, $zip_filepath, $archive_config_filepath, $is_compressed)
    {
		$htaccess_filepath = DUPLICATOR_PRO_WPROOTPATH . '.htaccess';
		$wpconfig_filepath = DUPLICATOR_PRO_WPROOTPATH . 'wp-config.php';

        $success = false;

        $zipArchive = new ZipArchive();

        if ($zipArchive->open($zip_filepath, ZIPARCHIVE::CREATE) === TRUE) {
            DUP_PRO_LOG::trace("Successfully opened zip $zip_filepath");

			if(file_exists($htaccess_filepath)) {
				DUP_PRO_Zip_U::addFileToZipArchive($zipArchive, $htaccess_filepath, DUPLICATOR_PRO_HTACCESS_ORIG_FILENAME, $is_compressed);
			}

			if(file_exists($wpconfig_filepath)) {
				DUP_PRO_Zip_U::addFileToZipArchive($zipArchive, $wpconfig_filepath, DUPLICATOR_PRO_WPCONFIG_ARK_FILENAME, $is_compressed);
			}

            //  if ($zipArchive->addFile($scan_filepath, DUPLICATOR_PRO_EMBEDDED_SCAN_FILENAME)) {
            if (DUP_PRO_Zip_U::addFileToZipArchive($zipArchive, $scan_filepath, DUPLICATOR_PRO_EMBEDDED_SCAN_FILENAME, $is_compressed)) {
                if ($this->add_installer_files_using_zip_archive($zipArchive, $installer_filepath, $archive_config_filepath, $is_compressed)) {
                    DUP_PRO_Log::info("Installer files added to archive");
                    DUP_PRO_LOG::trace("Added to archive");

                    $success = true;
                } else {
                    DUP_PRO_Log::error("Unable to add enhanced enhanced installer files to archive.", '', false);
                }
            } else {
                DUP_PRO_Log::error("Unable to add scan file to archive.", '', false);
            }

            if ($zipArchive->close() === false) {
                DUP_PRO_Log::error("Couldn't close archive when adding extra files.");
                $success = false;
            }

            DUP_PRO_LOG::trace('After ziparchive close when adding installer');
        }

        return $success;
    }

    private function add_extra_files_using_shellexec($zip_filepath, $installer_filepath, $scan_filepath, $sql_filepath, $archive_config_filepath, $is_compressed)
    {
        $success = false;
        $global  = DUP_PRO_Global_Entity::get_instance();

        $installer_source_directory      = DUPLICATOR_PRO_PLUGIN_PATH.'installer/';
        $installer_dpro_source_directory = "$installer_source_directory/dup-installer";
        $extras_directory                = DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH_TMP).'/extras';
        $extras_installer_directory      = $extras_directory.'/dup-installer';
        $extras_lib_directory            = $extras_installer_directory.'/lib';

        $snaplib_source_directory        = DUPLICATOR_PRO_LIB_PATH.'/snaplib';
        $fileops_source_directory        = DUPLICATOR_PRO_LIB_PATH.'/fileops';
        $extras_snaplib_directory        = $extras_installer_directory.'/lib/snaplib';
        $extras_fileops_directory        = $extras_installer_directory.'/lib/fileops';

        $installer_backup_filepath = "$extras_directory/".$global->get_installer_backup_filename();

        $dest_sql_filepath            = "$extras_directory/database.sql";
        $dest_archive_config_filepath = "$extras_installer_directory/archive.cfg";
        $dest_scan_filepath           = "$extras_directory/scan.json";

		$htaccess_filepath = DUPLICATOR_PRO_WPROOTPATH . '.htaccess';
		$dest_htaccess_orig_filepath  = "{$extras_directory}/" . DUPLICATOR_PRO_HTACCESS_ORIG_FILENAME;

		$wpconfig_filepath = DUPLICATOR_PRO_WPROOTPATH . 'wp-config.php';
		$dest_wpconfig_ark_filepath  = "{$extras_directory}/" . DUPLICATOR_PRO_WPCONFIG_ARK_FILENAME;

        if (file_exists($extras_directory)) {
            if (DUP_PRO_IO::deleteTree($extras_directory) === false) {
                DUP_PRO_Log::error("Error deleting $extras_directory", '', false);
                return false;
            }
        }

        if (!@mkdir($extras_directory)) {
            DUP_PRO_Log::error("Error creating extras directory", "Couldn't create $extras_directory", false);
            return false;
        }

        if (!@mkdir($extras_installer_directory)) {
            DUP_PRO_Log::error("Error creating extras directory", "Couldn't create $extras_installer_directory", false);
            return false;
        }

        if (@copy($installer_filepath, $installer_backup_filepath) === false) {
            DUP_PRO_Log::error("Error copying $installer_filepath to $installer_backup_filepath", '', false);
            return false;
        }

        if (@copy($sql_filepath, $dest_sql_filepath) === false) {
            DUP_PRO_Log::error("Error copying $sql_filepath to $dest_sql_filepath", '', false);
            return false;
        }

        if (@copy($archive_config_filepath, $dest_archive_config_filepath) === false) {
            DUP_PRO_Log::error("Error copying $archive_config_filepath to $dest_archive_config_filepath", '', false);
            return false;
        }

        if (@copy($scan_filepath, $dest_scan_filepath) === false) {
            DUP_PRO_Log::error("Error copying $scan_filepath to $dest_scan_filepath", '', false);
            return false;
        }

		if(file_exists($htaccess_filepath)) {
			DUP_PRO_LOG::trace("{$htaccess_filepath} exists so copying to {$dest_htaccess_orig_filepath}");
			@copy($htaccess_filepath, $dest_htaccess_orig_filepath);
		}

		if(file_exists($wpconfig_filepath)) {
			DUP_PRO_LOG::trace("{$wpconfig_filepath} exists so copying to {$dest_wpconfig_ark_filepath}");
			@copy($wpconfig_filepath, $dest_wpconfig_ark_filepath);
		}

        $one_stage_add = strtoupper($global->get_installer_extension()) == 'PHP';

        if ($one_stage_add) {

            if (!@mkdir($extras_snaplib_directory, 0755, true)) {
                DUP_PRO_Log::error("Error creating extras snaplib directory", "Couldn't create $extras_snaplib_directory", false);
                return false;
            }

            if (!@mkdir($extras_fileops_directory, 0755, true)) {
                DUP_PRO_Log::error("Error creating extras fileops directory", "Couldn't create $extras_fileops_directory", false);
                return false;
            }

            // If the installer has the PHP extension copy the installer files to add all extras in one shot since the server supports creation of PHP files
            if (DUP_PRO_IO::copyDir($installer_dpro_source_directory, $extras_installer_directory) === false) {
                DUP_PRO_Log::error("Error copying installer file directory to extras directory", "Couldn't copy $installer_dpro_source_directory to $extras_installer_directory", false);
                return false;
            }

            if (DUP_PRO_IO::copyDir($snaplib_source_directory, $extras_snaplib_directory) === false) {
                DUP_PRO_Log::error("Error copying installer snaplib directory to extras directory", "Couldn't copy $snaplib_source_directory to $extras_snaplib_directory", false);
                return false;
            }

            if (DUP_PRO_IO::copyDir($fileops_source_directory, $extras_fileops_directory) === false) {
                DUP_PRO_Log::error("Error copying installer fileops directory to extras directory", "Couldn't copy $fileops_source_directory to $extras_fileops_directory", false);
                return false;
            }
        }

        //-- STAGE 1 ADD
        $compression_parameter = DUP_PRO_Shell_U::getCompressionParam($is_compressed);

        $command = 'cd '.escapeshellarg(DUP_PRO_U::safePath($extras_directory));
        $command .= ' && '.escapeshellcmd(DUP_PRO_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
        $command .= escapeshellarg($zip_filepath).' ./*';

        DUP_PRO_LOG::trace("Executing Shell Exec Zip Stage 1 to add extras: $command");

        $stderr = shell_exec($command);

        //-- STAGE 2 ADD - old code until we can figure out how to add the snaplib library within dup-installer/lib/snaplib
        if ($stderr == '') {
            if (!$one_stage_add) {
                // Since we didn't bundle the installer files in the earlier stage we have to zip things up right from the plugin source area
                $command = 'cd '.escapeshellarg($installer_source_directory);
                $command .= ' && '.escapeshellcmd(DUP_PRO_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
                $command .= escapeshellarg($zip_filepath).' dup-installer/*';

                DUP_PRO_LOG::trace("Executing Shell Exec Zip Stage 2 to add installer files: $command");
                $stderr = shell_exec($command);

                $command = 'cd '.escapeshellarg(DUPLICATOR_PRO_LIB_PATH);
                $command .= ' && '.escapeshellcmd(DUP_PRO_Zip_U::getShellExecZipPath())." $compression_parameter".' -g -rq ';
                $command .= escapeshellarg($zip_filepath).' snaplib/* fileops/*';

                DUP_PRO_LOG::trace("Executing Shell Exec Zip Stage 2 to add installer files: $command");
                $stderr = shell_exec($command);
            }
        }

  //rsr temp      DUP_PRO_IO::deleteTree($extras_directory);

        if ($stderr == '') {
            if (DUP_PRO_U::getExeFilepath('unzip') != NULL) {
                $installer_backup_filename = basename($installer_backup_filepath);

                // Verify the essential extras got in there
                $extra_count_string = "unzip -Z1 '$zip_filepath' | grep '$installer_backup_filename\|scan.json\|database.sql\|archive.cfg' | wc -l";

                DUP_PRO_LOG::trace("Executing extra count string $extra_count_string");

                $extra_count = DUP_PRO_Shell_U::runAndGetResponse($extra_count_string, 1);

                if (is_numeric($extra_count)) {
                    // Accounting for the sql and installer back files
                    if ($extra_count >= 4) {
                        // Since there could be files with same name accept when there are m
                        DUP_PRO_LOG::trace("Core extra files confirmed to be in the archive");
                        $success = true;
                    } else {
                        DUP_PRO_Log::error("Tried to verify core extra files but one or more were missing. Count = $extra_count", '', false);
                    }
                } else {
                    DUP_PRO_LOG::trace("Executed extra count string of $extra_count_string");
                    DUP_PRO_Log::error("Error retrieving extra count in shell zip ".$extra_count, '', false);
                }
            } else {
                DUP_PRO_LOG::trace("unzip doesn't exist so not doing the extra file check");
                $success = true;
            }
        }

		if(file_exists($extras_directory)) {
			try
			{
				SnapLibIOU::rrmdir($extras_directory);
			}
			catch(Exception $ex)
			{
				DUP_PRO_LOG::trace("Couldn't recursively delete {$extras_directory}");
			}
		}

        return $success;
    }

    // Add installer directory to the archive and the archive.cfg
    private function add_installer_files_using_zip_archive(&$zip_archive, $installer_filepath, $archive_config_filepath, $is_compressed)
    {
        $success                   = false;
        /* @var $global DUP_PRO_Global_Entity */
        $global                    = DUP_PRO_Global_Entity::get_instance();
        $installer_backup_filename = $global->get_installer_backup_filename();

        DUP_PRO_LOG::trace('Adding enhanced installer files to archive using ZipArchive');

        //   if ($zip_archive->addFile($installer_filepath, $installer_backup_filename)) {
        if (DUP_PRO_Zip_U::addFileToZipArchive($zip_archive, $installer_filepath, $installer_backup_filename, $is_compressed)) {
            DUPLICATOR_PRO_PLUGIN_PATH.'installer/';

            $installer_directory = DUPLICATOR_PRO_PLUGIN_PATH.'installer/dup-installer';


            if (DUP_PRO_Zip_U::addDirWithZipArchive($zip_archive, $installer_directory, true, '', $is_compressed)) {
                $archive_config_local_name = 'dup-installer/archive.cfg';

                // if ($zip_archive->addFile($archive_config_filepath, $archive_config_local_name)) {
                if (DUP_PRO_Zip_U::addFileToZipArchive($zip_archive, $archive_config_filepath, $archive_config_local_name, $is_compressed)) {

                    $snaplib_directory = DUPLICATOR_PRO_PLUGIN_PATH . 'lib/snaplib';
                    $fileops_directory = DUPLICATOR_PRO_PLUGIN_PATH . 'lib/fileops';

                    //DupArchiveEngine::addDirectoryToArchiveST($archive_filepath, $snaplib_directory, DUPLICATOR_PRO_PLUGIN_PATH, true, 'dup-installer/');
                    if (DUP_PRO_Zip_U::addDirWithZipArchive($zip_archive, $snaplib_directory, true, 'dup-installer/lib/', $is_compressed) &&
                        DUP_PRO_Zip_U::addDirWithZipArchive($zip_archive, $fileops_directory, true, 'dup-installer/lib/', $is_compressed)) {

                        $success = true;
                    } else {
                        DUP_PRO_Log::error("Error adding directory {$snaplib_directory} or {$fileops_directory} to zipArchive", '', false);
                    }
                } else {
                    DUP_PRO_Log::error("Error adding $archive_config_filepath to zipArchive", '', false);
                }
            } else {
                DUP_PRO_Log::error("Error adding directory $installer_directory to zipArchive", '', false);
            }
        } else {
            DUP_PRO_Log::error("Error adding backup installer file to zipArchive", '', false);
        }

        return $success;
    }
}