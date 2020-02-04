<?php
/**
 * Classes for building the package database file
 *
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 */
defined("ABSPATH") or die("");
if (!defined('DUPLICATOR_PRO_VERSION'))
    exit; // Exit if accessed directly

require_once DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.global.entity.php';


/**
 * Class for gathering system information about a database
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 */
class DUP_PRO_DatabaseInfo
{
    /**
     * The SQL file was built with mysqldump or PHP
     */
    public $buildMode;

    /**
     * A unique list of all the collation table types used in the database
     */
    public $collationList;

    /**
     * Does any filtered table have an upper case character in it
     */
    public $isTablesUpperCase;

    /**
     * Does the database name have any filtered characters in it
     */
    public $isNameUpperCase;

    /**
     * The real name of the database
     */
    public $name;

    /**
     * The full count of all tables in the database
     */
    public $tablesBaseCount;

    /**
     * The count of tables after the tables filter has been applied
     */
    public $tablesFinalCount;

    /**
     * The number of rows from all filtered tables in the database
     */
    public $tablesRowCount;

    /**
     * The estimated data size on disk from all filtered tables in the database
     */
    public $tablesSizeOnDisk;

    /**
     * Gets the server variable lower_case_table_names
     *
     * 0 store=lowercase;	compare=sensitive	(works only on case sensitive file systems )
     * 1 store=lowercase;	compare=insensitive
     * 2 store=exact;		compare=insensitive	(works only on case INsensitive file systems )
     * default is 0/Linux ; 1/Windows
     */
    public $varLowerCaseTables;

    /**
     * The simple numeric version number of the database server
     * @exmaple: 5.5
     */
    public $version;

    /**
     * The full text version number of the database server
     * @exmaple: 10.2 mariadb.org binary distribution
     */
    public $versionComment;

    //CONSTRUCTOR
    function __construct()
    {
        $this->collationList = array();
    }
}

/**
 * Class used for determining the state of the Database build
 * This class is only used when PHP is in chunking mode
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 */
class DUP_PRO_DB_Build_Progress
{
    public $tableIndex = 0;
    public $tableOffset = 0;
    public $totalRowOffset = 0;
    public $doneInit = false;
    public $doneFiltering = false;
    public $doneCreates = false;
    public $completed = false;
    public $tablesToProcess = array();
    public $startTime;
    public $fileOffset = 0;
}

/**
 * Class used to do the actual working of building the database file
 * There are currently three modes: PHP, MYSQLDUMP, PHPCHUNKING
 * PHPCHUNKING and PHP will eventually be combined as one routine
 */
class DUP_PRO_Database
{
    //IDE HELPERS
    /* @var $global DUP_PRO_Global_Entity  */

    //PUBLIC
    public $info;
    //PUBLIC: Legacy Style
    public $Type	 = 'MySQL';
    public $Size;
    public $File;
    public $FilterTables;
    public $FilterOn;
    public $DBMode;
    public $Compatible;
    public $Comments = '';
    public $dbStorePathPublic;

    //PRIVATE
    private $endFileMarker;
    private $traceLogEnabled;
    private $Package;

    //CONSTRUCTOR
    function __construct($package)
    {
        global $wpdb;

        $this->Package = $package;
        $this->endFileMarker			 = '';
        $this->traceLogEnabled			 = DUP_PRO_Log::isTraceLogEnabled();
        $this->info						 = new DUP_PRO_DatabaseInfo();
        $this->info->varLowerCaseTables	 = DUP_PRO_U::isWindows() ? 1 : 0;
        $wpdb->query("SET SESSION wait_timeout = " . DUPLICATOR_PRO_DB_MAX_TIME);
    }

    /**
     * Runs the build process for the database
     *
     * @param object $package A copy of the package object to be built
     *
     * @return null
     */
    public function build($package)
    {
        DUP_PRO_LOG::trace("Building database");
        try {
            $global = DUP_PRO_Global_Entity::get_instance();
            $time_start = DUP_PRO_U::getMicrotime();
            $package->set_status(DUP_PRO_PackageStatus::DBSTART);

            $this->dbStorePathPublic	 = "{$package->StorePath}/{$this->File}";
            $mysqlDumpPath		 = DUP_PRO_DB::getMySqlDumpPath();
            $mode				 = DUP_PRO_DB::getBuildMode(); //($mysqlDumpPath && $global->package_mysqldump) ? 'MYSQLDUMP' : 'PHP';

            $mysqlDumpSupport = ($mysqlDumpPath) ? 'Is Supported' : 'Not Supported';

            $log = "\n********************************************************************************\n";
            $log .= "DATABASE:\n";
            $log .= "********************************************************************************\n";
            $log .= "BUILD MODE:   {$mode} ";

            if (($mode == 'MYSQLDUMP') && strlen($this->Compatible)) {
                $log.= " (Legacy SQL)";
            }

            $log .= ($mode == 'PHP') ? "(query limit - {$global->package_phpdump_qrylimit})\n" : "\n";
            $log .= "MYSQLDUMP:    {$mysqlDumpSupport}\n";
            $log .= "MYSQLTIMEOUT: ".DUPLICATOR_PRO_DB_MAX_TIME;
            DUP_PRO_Log::info($log);
            $log = null;

            switch ($mode) {
                case 'MYSQLDUMP': $this->runMysqlDump($mysqlDumpPath);
                    break;
                case 'PHP' : $this->runPHPDump();
                    break;
            }

            DUP_PRO_Log::info("SQL CREATED: {$this->File}");
            $time_end	 = DUP_PRO_U::getMicrotime();
            $time_sum	 = DUP_PRO_U::elapsedTime($time_end, $time_start);

            $sql_file_size = filesize($this->dbStorePathPublic);
            if ($sql_file_size <= 0) {
                DUP_PRO_Log::error("SQL file generated zero bytes.", "No data was written to the sql file.  Check permission on file and parent directory at [{$this->dbStorePathPublic}]");
            }
            DUP_PRO_Log::info("SQL FILE SIZE: ".DUP_PRO_U::byteSize($sql_file_size));
            DUP_PRO_Log::info("SQL FILE TIME: ".date("Y-m-d H:i:s"));
            DUP_PRO_Log::info("SQL RUNTIME: {$time_sum}");
            DUP_PRO_Log::info("MEMORY STACK: ".DUP_PRO_Server::getPHPMemory());

            $this->Size = @filesize($this->dbStorePathPublic);
            $package->set_status(DUP_PRO_PackageStatus::DBDONE);
        } catch (Exception $e) {
            DUP_PRO_Log::error("Runtime error in DUP_PRO_Database::Build", "Exception: {$e}");
        }

        DUP_PRO_LOG::trace("Done building database");
    }

    /**
     * Gets the database.sql file path and name
     *
     * @return string	Returns the full file path and file name of the database.sql file
     */
    public function getSafeFilePath()
    {
        return DUP_PRO_U::safePath(DUPLICATOR_PRO_SSDIR_PATH."/{$this->File}");
    }

    /**
     *  Gets all the scanner information about the database
     *
     * 	@return array Returns an array of information about the database
     */
    public function getScanData()
    {
        global $wpdb;
        $filterTables	 = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
        $tblBaseCount	 = 0;
        $tblFinalCount	 = 0;

        $tables						 = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
        $info						 = array();
        $info['Status']['Success']	 = is_null($tables) ? false : true;
        $info['Status']['Size']		 = 'Good';
        $info['Status']['Rows']		 = 'Good';

        $info['Size']		 = 0;
        $info['Rows']		 = 0;
        $info['TableCount']	 = 0;
        $info['TableList']	 = array();
        $tblCaseFound		 = 0;

        //Only return what we really need
        foreach ($tables as $table) {

            $tblBaseCount++;
            $name = $table["Name"];
            if ($this->FilterOn && is_array($filterTables)) {
                if (in_array($name, $filterTables)) {
                    continue;
                }
            }

            if (in_array($name, $this->Package->Multisite->getTablesToFilter())) {
                continue;
            }

            $size = ($table["Data_length"] + $table["Index_length"]);

            $info['Size'] += $size;
            $info['Rows'] += ($table["Rows"]);
            $info['TableList'][$name]['Case']	 = preg_match('/[A-Z]/', $name) ? 1 : 0;
            $info['TableList'][$name]['Rows']	 = empty($table["Rows"]) ? '0' : number_format($table["Rows"]);
            $info['TableList'][$name]['Size']	 = DUP_PRO_U::byteSize($size);
            $info['TableList'][$name]['USize']	 = $size;
            $tblFinalCount++;

            //Table Uppercase
            if ($info['TableList'][$name]['Case']) {
                if (!$tblCaseFound) {
                    $tblCaseFound = 1;
                }
            }
        }

        $info['Status']['Size']	 = ($info['Size'] > DUPLICATOR_PRO_SCAN_DB_ALL_SIZE) ? 'Warn' : 'Good';
        $info['Status']['Rows']	 = ($info['Rows'] > DUPLICATOR_PRO_SCAN_DB_ALL_ROWS) ? 'Warn' : 'Good';
        $info['TableCount']		 = $tblFinalCount;

        $this->info->name				 = $wpdb->dbname;
        $this->info->isNameUpperCase	 = preg_match('/[A-Z]/', $wpdb->dbname) ? 1 : 0;
        $this->info->isTablesUpperCase	 = $tblCaseFound;
        $this->info->tablesBaseCount	 = $tblBaseCount;
        $this->info->tablesFinalCount	 = $tblFinalCount;
        $this->info->tablesRowCount		 = $info['Rows'];
        $this->info->tablesSizeOnDisk	 = $info['Size'];
        $this->info->version			 = DUP_PRO_DB::getVersion();
        $this->info->versionComment		 = DUP_PRO_DB::getVariable('version_comment');
        $this->info->varLowerCaseTables	 = DUP_PRO_DB::getVariable('lower_case_table_names');
        $this->info->collationList		 = DUP_PRO_DB::getTableCollationList($filterTables);
        $this->info->buildMode           = DUP_PRO_DB::getBuildMode();

        return $info;
    }

    /**
     * Runs the mysqldump process to build the database.sql script
     *
     * @param string $exePath The path to the mysqldump executable
     *
     * @return bool	Returns true if the mysqldump process ran without issues
     */
    private function runMysqlDump($exePath)
    {
        global $wpdb;

        $host			 = explode(':', DB_HOST);
        $host			 = reset($host);
        $port			 = strpos(DB_HOST, ':') ? end(explode(':', DB_HOST)) : '';
        $name			 = DB_NAME;
        $mysqlcompat_on	 = isset($this->Compatible) && strlen($this->Compatible);

        //Build command
        $cmd = escapeshellarg($exePath);
        $cmd .= ' --no-create-db';
        $cmd .= ' --single-transaction';
        $cmd .= ' --hex-blob';
        $cmd .= ' --skip-add-drop-table';
        $cmd .= ' --routines';

        //Compatibility mode
        if ($mysqlcompat_on) {
            DUP_PRO_Log::info("COMPATIBLE: [{$this->Compatible}]");
            $cmd .= " --compatible={$this->Compatible}";
        }

        //Filter tables
        $tables			 = $wpdb->get_col('SHOW TABLES');
        $filterTables	 = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
        $mu_filter_tables = $this->Package->Multisite->getTablesToFilter();
        $tblAllCount	 = count($tables);

        //Filtering manually selected tables by user
        if (is_array($filterTables) && $this->FilterOn) {
            foreach ($tables as $key => $val) {
                if (in_array($tables[$key], $filterTables)) {
                    $cmd .= " --ignore-table={$name}.{$tables[$key]} ";
                    unset($tables[$key]);
                }
            }
        }

        //Filtering tables associated with subsite filtering
        if (!empty($mu_filter_tables)) {
            foreach ($tables as $key => $val) {
                if (in_array($tables[$key], $mu_filter_tables)) {
                    $cmd .= " --ignore-table={$name}.{$tables[$key]} ";
                    unset($tables[$key]);
                }
            }
        }

        $tblCreateCount	 = count($tables);
        $tblFilterCount	 = $tblAllCount - $tblCreateCount;

        $cmd .= ' -u '.escapeshellarg(DB_USER);
        $cmd .= (DB_PASSWORD) ?
            ' -p'.DUP_PRO_Shell_U::escapeshellargWindowsSupport(DB_PASSWORD) : '';
        $cmd .= ' -h '.escapeshellarg($host);
        $cmd .= (!empty($port) && is_numeric($port) ) ?
            ' -P '.$port : '';
        $cmd .= ' -r '.escapeshellarg($this->dbStorePathPublic);
        $cmd .= ' '.escapeshellarg(DB_NAME);
        $cmd .= ' 2>&1';

        DUP_PRO_LOG::trace("Executing mysql dump command $cmd");
        $output = shell_exec($cmd);

        // Password bug > 5.6 (@see http://bugs.mysql.com/bug.php?id=66546)
        if (trim($output) === 'Warning: Using a password on the command line interface can be insecure.') {
            $output = '';
        }
        $output = (strlen($output)) ? $output : "Ran from {$exePath}";

        DUP_PRO_Log::info("TABLES: total:{$tblAllCount} | filtered:{$tblFilterCount} | create:{$tblCreateCount}");
        DUP_PRO_Log::info("FILTERED: [{$this->FilterTables}]");
        DUP_PRO_Log::info("RESPONSE: {$output}");

        $sql_footer = "\n\n/* Duplicator WordPress Timestamp: ".date("Y-m-d H:i:s")."*/\n";
        $sql_footer .= "/* ".DUPLICATOR_PRO_DB_EOF_MARKER." */\n";
        file_put_contents($this->dbStorePathPublic, $sql_footer, FILE_APPEND);

        return ($output) ? false : true;
    }

    /**
     * Creates the database.sql script using PHP code
     *
     * @return null
     */
    private function runPHPDump()
    {
        global $wpdb;
        $global = DUP_PRO_Global_Entity::get_instance();

        $wpdb->query("SET session wait_timeout = ".DUPLICATOR_PRO_DB_MAX_TIME);
        $handle	 = fopen($this->dbStorePathPublic, 'w+');
        $tables	 = $wpdb->get_col("SHOW FULL TABLES WHERE Table_Type != 'VIEW'");

        $filterTables	 = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
        $mu_filter_tables = $this->Package->Multisite->getTablesToFilter();
        $tblAllCount	 = count($tables);

        //Filtering manually selected tables by user
        if (is_array($filterTables) && $this->FilterOn) {
            foreach ($tables as $key => $val) {
                if (in_array($tables[$key], $filterTables)) {
                    unset($tables[$key]);
                }
            }
        }

        //Filtering tables associated with subsite filtering
        if (!empty($mu_filter_tables)) {
            foreach ($tables as $key => $val) {
                if (in_array($tables[$key], $mu_filter_tables)) {
                    unset($tables[$key]);
                }
            }
        }
        $tblCreateCount	 = count($tables);
        $tblFilterCount	 = $tblAllCount - $tblCreateCount;

        DUP_PRO_Log::info("TABLES: total:{$tblAllCount} | filtered:{$tblFilterCount} | create:{$tblCreateCount}");
        DUP_PRO_Log::info("FILTERED: [{$this->FilterTables}]");

        //Added 'NO_AUTO_VALUE_ON_ZERO' at plugin version 3.4.8 to fix :
        //**ERROR** database error write 'Invalid default value for for older mysql versions
        $sql_header  = "/* DUPLICATOR-PRO (PHP BUILD MODE) MYSQL SCRIPT CREATED ON : ".@date("Y-m-d H:i:s")." */\n\n";
        $sql_header .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n\n";
        $sql_header .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        fwrite($handle, $sql_header);

        //BUILD CREATES:
        //All creates must be created before inserts do to foreign key constraints
        foreach ($tables as $table) {
            $create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            @fwrite($handle, "{$create[1]};\n\n");
        }

        $procedures = $wpdb->get_col("SHOW PROCEDURE STATUS WHERE `Db` = '{$wpdb->dbname}'",1);
        if(count($procedures)){
            foreach ($procedures as $procedure){
                @fwrite($handle, "DELIMITER ;;\n");
                $create = $wpdb->get_row("SHOW CREATE PROCEDURE `{$procedure}`", ARRAY_N);
                @fwrite($handle, "{$create[2]} ;;\n");
                @fwrite($handle, "DELIMITER ;\n\n");
            }
        }

        $views = $wpdb->get_col("SHOW FULL TABLES WHERE Table_Type = 'VIEW'");
        if(count($views)){
            foreach ($views as $view){
                $create = $wpdb->get_row("SHOW CREATE VIEW `{$view}`", ARRAY_N);
                @fwrite($handle, "{$create[1]};\n\n");
            }
        }

        //BUILD INSERTS:
        //Create Insert in 100 to 2000 row increments to better handle memory
        foreach ($tables as $table) {

            $row_count = $wpdb->get_var("SELECT Count(*) FROM `{$table}`");

            if ($row_count > $global->package_phpdump_qrylimit) {
                $row_count = ceil($row_count / $global->package_phpdump_qrylimit);
            } else if ($row_count > 0) {
                $row_count = 1;
            }

            if ($row_count >= 1) {
                fwrite($handle, "\n/* INSERT TABLE DATA: {$table} */\n");
            }

            for ($i = 0; $i < $row_count; $i++) {
                $sql	 = "";
                $limit	 = $i * $global->package_phpdump_qrylimit;
                $query	 = "SELECT * FROM `{$table}` LIMIT {$limit}, {$global->package_phpdump_qrylimit}";
                $rows	 = $wpdb->get_results($query, ARRAY_A);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $sql .= "INSERT INTO `{$table}` VALUES(";
                        $num_values	 = count($row);
                        $num_counter = 1;
                        foreach ($row as $value) {
                            if (is_null($value) || !isset($value)) {
                                ($num_values == $num_counter) ? $sql .= 'NULL' : $sql .= 'NULL, ';
                            } else {
                                ($num_values == $num_counter)
                                    ? $sql .= '"' . DUP_PRO_DB::escSQL($value, true) . '"'
                                    : $sql .= '"' . DUP_PRO_DB::escSQL($value, true) . '", ';
                            }
                            $num_counter++;
                        }
                        $sql .= ");\n";
                    }
                    fwrite($handle, $sql);
                }
            }

            $sql	 = null;
            $rows	 = null;
        }

        $sql_footer = "\nSET FOREIGN_KEY_CHECKS = 1; \n\n";
        $sql_footer .= "/* Duplicator WordPress Timestamp: ".date("Y-m-d H:i:s")."*/\n";
        $sql_footer .= "/* ".DUPLICATOR_PRO_DB_EOF_MARKER." */\n";
        fwrite($handle, $sql_footer);
        $wpdb->flush();
        fclose($handle);
    }

    /**
     * Uses PHP to build the SQL file in chunks over multiple http requests
     *
     * @param object $package The reference to the current package being built
     *
     * @return void
     */
    public function buildInChunks($package)
    {
        DUP_PRO_LOG::trace("Database: buildInChunks Start");

        if (!$package->db_build_progress->doneInit) {
            DUP_PRO_LOG::trace("Database: buildInChunks Init");
            $this->doInit($package);
            $package->db_build_progress->doneInit = true;
        } elseif (!$package->db_build_progress->doneFiltering) {
            DUP_PRO_LOG::trace("Database: buildInChunks Filtering");
            $this->doFiltering();
            $package->db_build_progress->doneFiltering = true;
        } elseif (!$package->db_build_progress->doneCreates) {
            DUP_PRO_LOG::trace("Database: buildInChunks WriteCreates");
            $this->writeCreates();
            $package->db_build_progress->fileOffset = filesize($this->dbStorePathPublic); // Set the offset pointer (presently only used in php chunking)
            // DUP_PRO_LOG::traceObject("#### db build progress offset", $this->Package->db_build_progress);
            $package->db_build_progress->doneCreates = true;
        } elseif (!$package->db_build_progress->completed) {
            DUP_PRO_LOG::trace("Database: buildInChunks WriteInsertChunk");
            $this->writeInsertChunk();
        }

        if ($this->Package->db_build_progress->completed) {
            DUP_PRO_LOG::trace("Database: buildInChunks completed");
            $package->build_progress->database_script_built = true;
            $this->doFinish($package);
        }

        DUP_PRO_LOG::trace("Database: buildInChunks End");
        $package->update();
    }

    /**
     * Used to initialize the PHP chunking logic
     *
     * @param object $package The reference to the current package being built
     *
     * @return void
     */
    public function doInit($package)
    {
        $global = DUP_PRO_Global_Entity::get_instance();

        $package->db_build_progress->startTime = DUP_PRO_U::getMicrotime();
        $package->set_status(DUP_PRO_PackageStatus::DBSTART);
        $this->dbStorePathPublic = "{$package->StorePath}/{$this->File}";

        $log = "\n********************************************************************************\n";
        $log .= "DATABASE:\n";
        $log .= "********************************************************************************\n";
        $log .= "BUILD MODE:   PHP + CHUNKING ";
        $log .= "(chunk size - {$global->package_phpdump_qrylimit} rows)\n";

        DUP_PRO_Log::info($log);
        $package->update();
    }

    public function doFiltering()
    {
        global $wpdb;

        $wpdb->query("SET session wait_timeout = ".DUPLICATOR_PRO_DB_MAX_TIME);
        $tables	 = $wpdb->get_col("SHOW FULL TABLES WHERE Table_Type != 'VIEW'");

        $filterTables	 = isset($this->FilterTables) ? explode(',', $this->FilterTables) : null;
        $mu_filter_tables = $this->Package->Multisite->getTablesToFilter();
        $tblAllCount	 = count($tables);

        //Filtering manually selected tables by user
        if (is_array($filterTables) && $this->FilterOn) {
            foreach ($tables as $key => $val) {
                if (!in_array($tables[$key], $filterTables)) {
                    $this->Package->db_build_progress->tablesToProcess[] = $val;
                }
            }
        } else if(!$this->FilterOn) {
            foreach ($tables as $key => $val) {
                $this->Package->db_build_progress->tablesToProcess[] = $val;
            }
        }

        //Filtering tables associated with subsite filtering
        if (!empty($mu_filter_tables)) {
            foreach ($tables as $key => $val) {
                if (!in_array($tables[$key], $mu_filter_tables)) {
                    $this->Package->db_build_progress->tablesToProcess[] = $val;
                }
            }
        }

        $tblCreateCount	 = count($tables);
        $tblFilterCount	 = $tblAllCount - $tblCreateCount;

        DUP_PRO_Log::info("TABLES: total:{$tblAllCount} | filtered:{$tblFilterCount} | create:{$tblCreateCount}");
        DUP_PRO_Log::info("FILTERED: [{$this->FilterTables}]");

        $this->Package->db_build_progress->doneFiltering = true;
        $this->Package->update();
    }

    public function writeCreates()
    {
        global $wpdb;

        $tables = $this->Package->db_build_progress->tablesToProcess;
        $handle	 = @fopen($this->dbStorePathPublic, 'w+');

        //Added 'NO_AUTO_VALUE_ON_ZERO' at plugin version 3.4.8 to fix :
        //**ERROR** database error write 'Invalid default value for for older mysql versions
        $sql_header  = "/* DUPLICATOR-PRO (PHP BUILD MODE) MYSQL SCRIPT CREATED ON : ".@date("Y-m-d H:i:s")." */\n\n";
        $sql_header .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n\n";
        $sql_header .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        fwrite($handle, $sql_header);

        //BUILD CREATES:
        //All creates must be created before inserts do to foreign key constraints
        foreach ($tables as $table) {
            $create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            @fwrite($handle, "{$create[1]};\n\n");
        }

        $procedures = $wpdb->get_col("SHOW PROCEDURE STATUS WHERE `Db` = '{$wpdb->dbname}'", 1);
        if (count($procedures)) {
            foreach ($procedures as $procedure) {
                @fwrite($handle, "DELIMITER ;;\n");
                $create = $wpdb->get_row("SHOW CREATE PROCEDURE `{$procedure}`", ARRAY_N);
                @fwrite($handle, "{$create[2]} ;;\n");
                @fwrite($handle, "DELIMITER ;\n\n");
            }
        }

        $views = $wpdb->get_col("SHOW FULL TABLES WHERE Table_Type = 'VIEW'");
        if (count($views)) {
            foreach ($views as $view) {
                $create = $wpdb->get_row("SHOW CREATE VIEW `{$view}`", ARRAY_N);
                @fwrite($handle, "{$create[1]};\n\n");
            }
        }

        fclose($handle);
        $this->Package->db_build_progress->doneCreates = true;
        $this->Package->update();
    }

    public function writeInsertChunk()
    {
        global $wpdb;
        $global = DUP_PRO_Global_Entity::get_instance();
        $server_load_delay = 0;

        if ($global->server_load_reduction != DUP_PRO_Server_Load_Reduction::None) {
            $server_load_delay = DUP_PRO_Server_Load_Reduction::microseconds_from_reduction($global->server_load_reduction);
        }

        $handle	= @fopen($this->dbStorePathPublic, 'r+');

        if ($handle === false) {
            $msg = print_r(error_get_last(), true);
            throw new Exception("FILE READ ERROR: Could not open file {$this->dbStorePathPublic} {$msg}");
        }

        DUP_PRO_LOG::trace("#### seeking to sql offset {$this->Package->db_build_progress->fileOffset}");

        if(fseek($handle, $this->Package->db_build_progress->fileOffset) == -1) {
            throw new Exception("FILE SEEK ERROR: Could not seek to file offset {$this->Package->db_build_progress->fileOffset}");
        }

        $worker_time	 = $global->php_max_worker_time_in_sec;
        $start_time		 = time();
        $elapsed_time	 = 0;
        $table_count	 = count($this->Package->db_build_progress->tablesToProcess);
        $current_index	 = $this->Package->db_build_progress->tableIndex;
        $tables			 = $this->Package->db_build_progress->tablesToProcess;
        $table			 = $tables[$current_index];
        $row_offset		 = $this->Package->db_build_progress->tableOffset;

        if (count($tables) > 0) {

            while (!$this->Package->db_build_progress->completed && $elapsed_time < $worker_time) {

                $chunk_size				 = $global->package_phpdump_qrylimit;
                $table					 = $tables[$current_index];
                $row_count				 = $wpdb->get_var("SELECT Count(*) FROM `{$table}`");
                $rows_left_to_process	 = 0;

                if ($row_count >= 1) {
                    $rows_left_to_process = $row_count - $row_offset;
                    if ($row_offset == 0) {
                        fwrite($handle, "\n/* INSERT TABLE DATA: {$table} */\n");
                    }
                }

                if ($rows_left_to_process < $chunk_size) {
                    $chunk_size = $rows_left_to_process;
                }

                if ($this->traceLogEnabled) {
                    DUP_PRO_Log::trace("------------ DB SCAN CHUNK LOOP ------------");
                    DUP_PRO_Log::trace("table: $table ($current_index of $table_count)");
                    DUP_PRO_Log::trace("rows_left_to_process: $rows_left_to_process");
                    DUP_PRO_Log::trace("worker_time: $worker_time");
                    DUP_PRO_Log::trace("row_offset: $row_offset");
                    DUP_PRO_Log::trace("chunk_size: $chunk_size");
                }

                $sql	 = '';
                $query	 = "SELECT * FROM `{$table}` LIMIT {$row_offset}, {$chunk_size}";
                $rows	 = $wpdb->get_results($query, ARRAY_A);
                $bulk_size = 20;
                $bulk_counter = 0;
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $bulk_counter = ($bulk_counter === $bulk_size) ? 0 : $bulk_counter;
                        if($server_load_delay !== 0) {
                            usleep($server_load_delay);
                        }

                        $sql		 .= $bulk_counter === 0 ? "INSERT INTO `{$table}` VALUES(" : "(";
                        $num_values	 = count($row);
                        $num_counter = 1;
                        foreach ($row as $value) {
                            if (is_null($value) || !isset($value)) {
                                ($num_values === $num_counter) ? $sql .= 'NULL' : $sql .= 'NULL, ';
                            } else {
                                ($num_values === $num_counter)
                                    ? $sql .= '"'.DUP_PRO_DB::escSQL($value, true).'"'
                                    : $sql .= '"'.DUP_PRO_DB::escSQL($value, true).'", ';
                            }
                            $num_counter++;
                        }

                        $row_offset++;
                        $bulk_counter++;
                        $this->Package->db_build_progress->totalRowOffset++;

                        $sql .= ($bulk_counter == $bulk_size || $row_offset == $row_count) ? ");\n" : "), ";
                    }
                    DUP_PRO_Log::trace("$row_offset of $row_count");
                    fwrite($handle, $sql);
                }

                if($server_load_delay != 0) {
                    usleep($server_load_delay);
                }

                $sql	= null;
                $rows	= null;
                $this->Package->db_build_progress->tableOffset	 = $row_offset;
                if (($row_offset == $row_count)) {
                    $row_offset										 = 0;
                    $this->Package->db_build_progress->tableOffset	 = $row_offset;
                    if ($table_count != $current_index + 1) {
                        $current_index++;
                        $this->Package->db_build_progress->tableIndex = $current_index;
                    } else {
                        $this->Package->db_build_progress->completed = true;
                        $this->writeSQLFooter($handle);
                    }
                }
                $elapsed_time = time() - $start_time;
            }
        } else {
            $this->Package->db_build_progress->completed = true;
            $this->writeSQLFooter($handle);
        }

        $wpdb->flush();
        $this->Package->db_build_progress->fileOffset = ftell($handle);

        if($this->Package->db_build_progress->fileOffset === false) {
            throw new Exception("Problem retrieving location pointer in SQL file");
        }

        DUP_PRO_LOG::trace("#### saving sql offset {$this->Package->db_build_progress->fileOffset}");

        fclose($handle);
        $this->Package->update();
    }

    private function writeSQLFooter($fileHandle)
    {
        $sql_footer = "\nSET FOREIGN_KEY_CHECKS = 1; \n\n";
        $sql_footer .= "/* Duplicator WordPress Timestamp: ".date("Y-m-d H:i:s")."*/\n";
        $sql_footer .= "/* ".DUPLICATOR_PRO_DB_EOF_MARKER." */\n";
        fwrite($fileHandle, $sql_footer);
    }

    public function doFinish($package)
    {
        DUP_PRO_Log::info("SQL CREATED: {$this->File}");
        $time_end	    = DUP_PRO_U::getMicrotime();
        $elapsed_time	= DUP_PRO_U::elapsedTime($time_end, $this->Package->db_build_progress->startTime);

        $sql_file_size = filesize($this->dbStorePathPublic);
        if ($sql_file_size <= 0) {
            DUP_PRO_Log::error("SQL file generated zero bytes.", "No data was written to the sql file.  Check permission on file and parent directory at [{$this->dbStorePathPublic}]");
        }
        DUP_PRO_Log::info("SQL FILE SIZE: ".DUP_PRO_U::byteSize($sql_file_size));
        DUP_PRO_Log::info("SQL FILE TIME: ".date("Y-m-d H:i:s"));
        DUP_PRO_Log::info("SQL RUNTIME: {$elapsed_time}");
        DUP_PRO_Log::info("MEMORY STACK: ".DUP_PRO_Server::getPHPMemory());

        $this->Size = @filesize($this->dbStorePathPublic);
        $package->set_status(DUP_PRO_PackageStatus::DBDONE);
        $package->update();
    }
}