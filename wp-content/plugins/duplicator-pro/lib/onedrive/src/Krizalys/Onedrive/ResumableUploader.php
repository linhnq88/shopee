<?php

namespace Krizalys\Onedrive;

class ResumableUploader
{
    /**
     * @var Client An instance of the OneDrive Client
     */
    private $_client;

    /**
     * @var string This sessions upload url
     */
    private $_uploadUrl;

    /**
     * @var int Expiration time of session
     */
    private $_expirationTime;

    /**
     * @var int Chunk size
     */
    private $_chunkSize = 327680 * 5;

    /**
     * @var int Offset to start uploading next chunk from
     */
    private $_fileOffset = 0;

    /**
     * @var string Path to file that is being uploaded
     */
    private $_sourcePath;

    /**
     * @var null The upload error message
     */
    private $_error = null;

    /**
     * @var bool The chunk upload success status
     */
    private $_success = false;

    /**
     * @var bool Is file uploaded completely
     */
    private $_completed = false;

    /**
     * @var File The uploaded file
     */
    private $_file = null;

    /**
     * ResumableUploader constructor.
     * @param Client $client An instance of the OneDrive Client
     * @param string $sourcePath Path to file that is being uploaded
     * @param object $resumable An object which contains the uploadUrl and Expiration Time
     *
     */
    public function __construct(Client $client, $sourcePath, $resumable = null)
    {
        $this->_client = $client;
        $this->_sourcePath = $sourcePath;
        if ($resumable != null && property_exists($resumable, "uploadUrl")) {
            $this->_uploadUrl = $resumable->uploadUrl;
            $this->_expirationTime = $resumable->expirationTime;
        }
    }

    /**
     * @param object $resumable An object which contains the uploadUrl and Expiration Time
     */
    public function setFromData($resumable)
    {
        $this->_uploadUrl = $resumable->uploadUrl;
        $this->_expirationTime = $resumable->expirationDateTime;
    }

    /**
     * @param string $filename The name the file will have in OneDrive
     * @param string $destPath The path tp the destination Folder, default is root
     *
     * @throws \Exception
     */
    public function obtainResumableUploadUrl($path)
    {
        if($this->_client->isBusiness()){
            $path = "drive/special/approot:/" . $path . ":/upload.createSession";
        }else{
            $path = "drive/special/approot:/" . $path . ":/upload.createSession";
        }


        $resumable = $this->_client->apiPost($path, []);
        if (property_exists($resumable, "uploadUrl")) {
            $this->_uploadUrl = $resumable->uploadUrl;
            $this->_expirationTime = strtotime($resumable->expirationDateTime);
        } else {
            throw new \Exception("Couldn't obtain resumable upload URL");
        }
    }

    /**
     * @return string The upload url
     */
    public function getUploadUrl()
    {
        return $this->_uploadUrl;
    }

    /**
     * @return int The upload session expiration time
     *
     */
    public function getExpirationTime()
    {
        return $this->_expirationTime;
    }

    /**
     * @return object An object which contains the expected ranges
     */
    public function getUploadStatus()
    {
        return $this->_client->apiGet($this->_uploadUrl);
    }

    /**
     * @return int Where to start the upload from
     */
    public function getUploadOffset()
    {
        if(!$this->_completed){
            return $this->_fileOffset;
        }
        return filesize($this->_sourcePath);
    }

    public function setUploadOffset($offset){
        $this->_fileOffset = $offset;
    }

    /**
     * @return int The next chunk size to be uploaded
     */
    public function getChunkSize()
    {
        $filesize = filesize($this->_sourcePath);
        return ($filesize - $this->_fileOffset > $this->_chunkSize) ? $this->_chunkSize : $filesize - $this->_fileOffset;
    }

    /**
     * @return array The headers for the upload
     */
    public function getHeaders()
    {
        $filesize = filesize($this->_sourcePath);
        $chunkSize = $this->getChunkSize();
        $this->_fileOffset = $this->getUploadOffset();
        $headers = [
            "Content-Length: " . $chunkSize,
            "Content-Range: bytes " . $this->_fileOffset . "-" . ($this->_fileOffset + $chunkSize - 1) . "/" . $filesize
        ];

        return $headers;
    }

    /**
     * @return string Path to file that is being uploaded
     */
    public function getSourcePath()
    {
        return $this->_sourcePath;
    }

    /**
     * @return object The resumable object
     */
    public function getResumable()
    {
        return (object)[
            "uploadUrl" => $this->_uploadUrl,
            "expirationTime" => $this->_expirationTime
        ];
    }

    /**
     * @return string|null The error message
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return bool The upload success state
     */
    public function success()
    {
        return $this->_success;
    }

    /**
     * @param File $file Sets the completed file
     */
    public function setFile(File $file)
    {
        $this->_file = $file;
    }

    public function completed()
    {
        return $this->_completed;
    }

    public function sha1CheckSum($file)
    {
        return $this->_file->sha1CheckSum($file);
    }

    /**
     * @param resource $stream Stream of the chunk being uploaded
     * @return object The upload status
     */
    public function uploadChunk($stream)
    {
        $headers = $this->getHeaders();
		\DUP_PRO_Log::trace("Headers of chunk ".print_r($headers,true));
        if ($this->_uploadUrl !== null) {
            try {
                $result = $this->_client->apiPut($this->_uploadUrl, $stream, $headers);
                $this->_success = true;
                if (property_exists($result, "name")) {
                    $this->_completed = true;
                    $file = new File($this->_client, $result->id, $result);
                    $this->_file = $file;
                }
            } catch (\Exception $exception) {
                $this->_success = false;
                $this->_error = $exception->getMessage();
            }
        } else {
            $this->_success = false;
            $this->_error = "You have to have set _uploadUrl to make an upload";
        }

    }

}