<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FileOpsState
{
    public static $instance = null;

    private $workerTime;
    private $directories;
    private $throttleDelay;
    private $excludedDirectories;
    private $excludedFiles;
    private $working = false;

    const StateFilename = 'state.json';

    public static function getInstance($reset = false)
    {
        if ((self::$instance == null) && (!$reset)) {
            $stateFilepath = dirname(__FILE__).'/'.self::StateFilename;

            self::$instance = new FileOpsState();

            if (file_exists($stateFilepath)) {
                $stateHandle = SnapLibIOU::fopen($stateFilepath, 'rb');

                SnapLibIOU::flock($stateHandle, LOCK_EX);

                $stateString = fread($stateHandle, filesize($stateFilepath));

                $data = json_decode($stateString);

                self::$instance->setFromData($data);

              //  self::$instance->fileRenames = (array)(self::$instance->fileRenames);

                SnapLibIOU::flock($stateHandle, LOCK_UN);

                SnapLibIOU::fclose($stateHandle);
            } else {
                $reset = true;
            }
        }

        if ($reset) {
            self::$instance = new FileOpsState();

            self::$instance->reset();
        }

        return self::$instance;
    }

    private function setFromData($data)
    {
   //     $this->currentFileHeader     = $data->currentFileHeader;
    }

    public function reset()
    {
        $stateFilepath = dirname(__FILE__).'/'.self::StateFilename;

        $stateHandle = SnapLibIOU::fopen($stateFilepath, 'w');

        SnapLibIOU::flock($stateHandle, LOCK_EX);

        $this->initMembers();

        SnapLibIOU::fwrite($stateHandle, json_encode($this));

        SnapLibIOU::fclose($stateHandle);
    }

    public function save()
    {
        $stateFilepath = dirname(__FILE__).'/'.self::StateFilename;

        $stateHandle = SnapLibIOU::fopen($stateFilepath, 'w');

        SnapLibIOU::flock($stateHandle, LOCK_EX);

        DupArchiveUtil::tlog("saving state");
        SnapLibIOU::fwrite($stateHandle, json_encode($this));

        SnapLibIOU::fclose($stateHandle);
    }

    private function initMembers()
    {
    //    $this->currentFileHeader = null;

    }
}