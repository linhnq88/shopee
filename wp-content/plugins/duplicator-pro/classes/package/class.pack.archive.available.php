<?php

class DUP_PRO_Archive_Available{

    private $memory_usage = 1024; // Default on many PHP settings is 131072

    function __construct()
    {
        // Set PHP.INI max memory usage by default
        if(function_exists('memory_get_usage'))
            $this->memory_usage = memory_get_usage(true);
    }

    /*
    * CHECK ARCHIVE IS DUPARCHIVE
    * @param string $path the direct path for directory where can be located all archives
    * @return object
    */
	public static function get_list($path)
	{
        return self::provideArchiveList($path);
	}

    /*
    * CLEAN STRING TO GET REAL PATH
    * @param string $path the archive direct path
    * @return string
    */
    public static function realPath($path){
        $path = str_replace(array('\\','//'),'/',$path);
		$path = rtrim($path, '/');
        $path = trim($path);
        return $path;
    }

    /*
    * VALIDATE DUPARCHIVE FINDING RIGHT VERSION
    * @param string $path the archive direct path
    * @return false/version
    */
    public static function findVersion($path, $use_shell = false)
    {
        $zip_version = false;

        if($use_shell === true) // EXPERIMENTAL
        {
            $zip_encoded = shell_exec('unzip -p '.$path.' "dup-installer/archive.cfg"');
            if(!empty($zip_encoded))
            {
                $zip_decode = json_decode($zip_encoded);
                $zip_version = isset($zip_decode->version_dup) ? $zip_decode->version_dup : false;
            }
        }
        else
        {
            $zipUnpack = new ZipArchive;
            if ($zipUnpack->open($path) === true)
            {
                if(($zip_encoded = $zipUnpack->getFromName('dup-installer/archive.cfg')) !== false)
                {
                    $zip_decode = json_decode($zip_encoded);
                    $zip_version = isset($zip_decode->version_dup) ? $zip_decode->version_dup : false;
                    $zipUnpack->close();
                }
            }
        }

        return $zip_version;
    }

    /*
    * CHECK ARCHIVE IS DUPARCHIVE
    * @param string $path the archive direct path
    * @param int $size archive file size
    * @return bool
    */
    public static function isDuparchive($path, $size=0){
        if(preg_match("/^.*?_[0-9]+_archive.zip$/Ui",$path)) // Direct file searching (faster search)
        {
            $zip_version=self::findVersion($path);
            return (false !== $zip_version && version_compare($zip_version, DUPLICATOR_PRO_LIMIT_UPLOAD_VERSION) >= 0);
        }
        else if(preg_match("/^.*?.daf$/Ui",$path)) // Direct DAF searching
        {
            $class = get_class();
            $self = new $class();
            try
            {
                $read_part = fopen($path, 'rb');

                $length = $size > $self->memory_usage ? $self->memory_usage : ($size > 0 ? (int)$size : filesize($path));
                if(($get_part = fread($read_part, $length)) === false)
                {
                    $get_part = stream_get_contents($read_part, $length); // For case that fread() fail on non standard file type
                }

                fclose($read_part);

                if(preg_match("/\<V\>(.*?)\<\/V\>/Ui", $get_part, $matches))
                    return (version_compare($matches[1], DUPLICATOR_PRO_LIMIT_UPLOAD_VERSION) >= 0);

            }   catch (Exception $exc) {
                DUP_PRO_LOG::trace("EXCEPTION: " . $exc->getMessage());
                $result->processError($exc);
            }
        }
        else if(preg_match("/^.*?.zip$/Ui",$path)) // Search all other ZIP archive and unpack it to find right package (slower search)
        {
            $zip_version=self::findVersion($path);
            return (false !== $zip_version && version_compare($zip_version, DUPLICATOR_PRO_LIMIT_UPLOAD_VERSION) >= 0);
        }
        return false;
    }

    /*
    * SPECIAL ORDER BY FOR MULTIDIMENSIONAL ARRAY
    * @param array $ary the array we want to sort
    * @param string $clause a string specifying how to sort the array similar to SQL ORDER BY clause
    * @param bool $ascending that default sorts fall back to when no direction is specified
    * @return null
    */
    public static function orderBy(&$ary, $clause, $ascending = true) {
        $clause = str_ireplace('order by', '', $clause);
        $clause = preg_replace('/\s+/', ' ', $clause);
        $keys = explode(',', $clause);
        $dirMap = array('desc' => 1, 'asc' => -1);
        $def = $ascending ? -1 : 1;

        $keyAry = array();
        $dirAry = array();
        foreach($keys as $key) {
            $key = explode(' ', trim($key));
            $keyAry[] = trim($key[0]);
            if(isset($key[1])) {
                $dir = strtolower(trim($key[1]));
                $dirAry[] = $dirMap[$dir] ? $dirMap[$dir] : $def;
            } else {
                $dirAry[] = $def;
            }
        }

        $fnBody = '';
        $ii = count($keyAry);
        for($i = $ii - 1; $i >= 0; $i--) {
            $k = $keyAry[$i];
            $t = $dirAry[$i];
            $f = -1 * $t;
            $aStr = '$a[\''.$k.'\']';
            $bStr = '$b[\''.$k.'\']';
            if(strpos($k, '(') !== false) {
                $aStr = '$a->'.$k;
                $bStr = '$b->'.$k;
            }

            if($fnBody == '') {
                $fnBody .= "if({$aStr} == {$bStr}) { return 0; }\n";
                $fnBody .= "return ({$aStr} < {$bStr}) ? {$t} : {$f};\n";
            } else {
                $fnBody = "if({$aStr} == {$bStr}) {\n" . $fnBody;
                $fnBody .= "}\n";
                $fnBody .= "return ({$aStr} < {$bStr}) ? {$t} : {$f};\n";
            }
        }

        if($fnBody) {
            $sortFn = create_function('$a,$b', $fnBody);
            usort($ary, $sortFn);
        }
    }

    
    /*=================================
    * LOOK INTO self::get_list($path);
    */
    private static function provideArchiveList($path){
        if(!file_exists($path) && !is_dir($path))
            return (object) array(
                'length' => 0,
                'list' => NULL
            );

        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $list = array();

        $path = self::realPath($path);

        /** USE PHP BUILTIN CLASSES TO GET PROPER DATA - FASTER SOLUTION **/
        if(class_exists('FilesystemIterator') || class_exists('DirectoryIterator'))
		{
			if(class_exists('FilesystemIterator'))
			{
				$it = new FilesystemIterator($path);
				$v = 1;
			}
			else if(class_exists('DirectoryIterator'))
			{
				$it = new DirectoryIterator($path);
				$v = 2;
			}

			foreach ($it as $fileinfo)
			{
				if(($v === 2 && $fileinfo->isDot())) continue;
                if($fileinfo->isDir()==1) continue;

                $extension = $fileinfo->getExtension();

                if(!in_array($extension, array('zip','daf'))) continue;

                $size = $fileinfo->getSize();
                $timestamp = $fileinfo->getMTime();
                $pathname = self::realPath($fileinfo->getPathname());

                if(self::isDuparchive($pathname, $size))
                {
                    $list[]= array(
                        'type' => $fileinfo->getType(),
                        'name' => $fileinfo->getFilename(),
                        'extension' => $extension,
                        'size' => $size,
                        'size_unit' => DUP_PRO_U::byteSize($size),
                        'date' => date("{$date_format} {$time_format}", $timestamp),
                        'timestamp' => $timestamp,
                        'path' => $pathname,
                    );
                }
			}
            unset($pathname);
            unset($timestamp);
            unset($date_format);
            unset($time_format);
            unset($extension);
            unset($size);
            unset($it);
		}
		else
		{
			/** WE MUST FIND SOLUTION WITH GLOB() - SLOWER SOLUTION **/
            $find_files = glob($path."/*.{zip,daf}", GLOB_BRACE);
            foreach ($find_files as $filename) {

                $filename = self::realPath($filename);
                $name = str_replace($path."/","",$filename);
                $size = filesize($filename);
                $timestamp = filemtime($filename);
                $ext = explode('.', $filename);

                if(self::isDuparchive($filename,$size))
                {
                    $list[]= array(
                        'type' => filetype($filename),
                        'name' => $name,
                        'extension' => end($ext),
                        'size' => $size,
                        'size_unit' => DUP_PRO_U::byteSize($size),
                        'date' => date("{$date_format} {$time_format}", $timestamp),
                        'timestamp' => $timestamp,
                        'path' => $filename,
                    );
                }
            }
            unset($filename);
            unset($timestamp);
            unset($date_format);
            unset($time_format);
            unset($ext);
            unset($name);
            unset($size);
            unset($find_files);
		}

        unset($path);

        self::orderBy($list, 'date DESC');

		return (object) array(
            'length' => count($list),
            'list' => json_decode(json_encode($list))
        );
    }
}