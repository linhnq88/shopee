<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FileOpsMoveU
{
    // Move $directories, $files, $excludedFiles to $destination directory. Throws exception if it can't do something and $exceptionOnFaiure is true
    // $exludedFiles can include * wildcard
    // returns: array with list of failures
    public static function move($directories, $files, $excludedFiles, $destination)
    {
        SnapLibLogger::logObject('directories', $directories);
        SnapLibLogger::logObject('files', $files);
        SnapLibLogger::logObject('excludedFiles', $excludedFiles);
        SnapLibLogger::logObject('destination', $destination);

        $failures = array();


        $directoryFailures = SnapLibIOU::massMove($directories, $destination, null, false);
        SnapLibLogger::log('done directories');
        $fileFailures = SnapLibIOU::massMove($files, $destination, $excludedFiles, false);
        SnapLibLogger::log('done files');
        return array_merge($directoryFailures, $fileFailures);
    }
}