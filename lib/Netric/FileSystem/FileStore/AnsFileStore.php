<?php
/**
 * Store files in the ANS storage server
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore;

use Netric\Error;
use Netric\Entity\ObjType\FileEntity;
use Netric\Entity\DataMapperInterface;
use Netric\FileSystem\FileStore\exception;

/**
 * Get and set files in ANS
 */
class AnsFileStore implements FileStoreInterface
{
    /**
     * Version of ANS API
     */
    const ANS_API_VER = 2;

    /**
     * ANS Server
     *
     * @var string
     */
    private $ansServer = "";

    /**
     * ANS account
     *
     * @var string
     */
    private $ansAccount = "";

    /**
     * ANS password
     *
     * @var string
     */
    private $ansPassword = "";

    /**
     * Array of errors encountered
     *
     * @var Error\Error[]
     */
    private $errors = array();

    /**
     * Account/tennant ID
     *
     * @var int
     */
    private $accountId = null;

    /**
     * Entity DataMapper for pulling revision data
     *
     * @var DataMapperInterface
     */
    private $entityDataMapper = null;

    /**
     * URL to the server
     *
     * @var string
     */
    private $requestUrl = null;

    /**
     * ANS folder or bucket
     *
     * We currently store everything in / sincer there's no reason to segment
     *
     * @var string
     */
    private $ansFolder = "/";

    /**
     * Temporary folder path for processing remote files locally
     *
     * @var string
     */
    private $tmpPath = null;

    /**
     * Class constructor
     *
     * @param string $accountId The unique id of the tennant's account
     * @param DataMapperInterface $dataMapper An entity DataMapper for saving entities
     * @param string $ansServer The server URL
     * @param string $ansAccount The ANS account to store files under
     * @param string $ansPassword The ANS account password
     * @param string $tmpPath The temp folder path
     */
    public function __construct(
        $accountId,
        DataMapperInterface $dataMapper,
        $ansServer,
        $ansAccount,
        $ansPassword,
        $tmpPath
    )
    {
        $this->accountId = $accountId;
        $this->entityDataMapper = $dataMapper;
        $this->ansServer = $ansServer;
        $this->ansAccount = $ansAccount;
        $this->ansPassword = $ansPassword;
        $this->tmpPath = $tmpPath;

        $this->requestUrl = "http://" . $this->ansServer . "/server.php?account=";
        $this->requestUrl .= $this->ansAccount . "&p=".md5($this->ansPassword);
        $this->requestUrl .= "&pver=" . self::ANS_API_VER;
    }

    /**
     * Read and return numBypes (or all) of a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param null $numBytes Number of bytes, if null then return while file
     * @param null $offset Starting offset, defaults to current pointer
     * @return mixed
     * @throws Exception\FileNotFoundException if we try to read a file not in the store
     */
    public function readFile(FileEntity $file, $numBytes = null, $offset = null)
    {
        $url = $this->requestUrl;
        $url .= "&function=file_download&folder=".rawurlencode($this->ansFolder);
        $url .= "&file=".rawurlencode($file->getValue("dat_ans_key"));

        // If file has not yet been opened, then open it
        if (!$file->getFileHandle())
        {
            if ($this->fileExists($file))
            {
                $file->setFileHandle(fopen($url, 'rb'));
            }
            else
            {
                throw new Exception\FileNotFoundException(
                    "Key '$url' is not in the ANS store: " .
                    $this->getLastError()->getMessage()
                );
            }
        }

        // If offset was not defined then get the whole file
        if (!$offset)
            $offset = -1;

        // If the user did not indicate the number of bytes to read then whole file
        if (!$numBytes)
        {
            $numBytes = $file->getValue("file_size");
        }

        if ($file->getFileHandle())
            return stream_get_contents($file->getFileHandle(), $numBytes, $offset);
        else
            return false;
    }

    /**
     * Write data to a file
     *
     * @param FileEntity $file The meta-data Entity for this file
     * @param $dataOrStream $data Binary data to write or a stream resource
     * @return int number of bytes written
     */
    public function writeFile(FileEntity $file, $dataOrStream)
    {
        // 1. Write to temp
        $tempName = $this->getTempName($file);
        $tempPath = $this->tmpPath . "/" . $tempName;
        $bytesWritten = file_put_contents($tempPath, $dataOrStream);

        // 2. Upload
        if ($this->uploadFile($file, $tempPath))
        {
            // Cleanup
            unlink($tempPath);

            return $bytesWritten;
        }
        else
        {
            // Cleanup
            @unlink($tempPath);

            // Return error, uploadFile should have set this->getLastError()
            return -1;
        }
    }

    /**
     * Upload a file to the data store
     *
     * @param FileEntity $file Meta-data Entity for the file
     * @param string $localPath Path of a local file
     * @return true on success, false on failure
     */
    public function uploadFile(FileEntity $file, $localPath)
    {
        $retval = 0; // assume fail

        if (!file_exists($localPath))
            return false;

        // Close the file handle if open
        if ($file->getFileHandle())
        {
            fclose($file->getFileHandle());
            $file->setFileHandle(null);
        }

        $size = filesize($localPath);

        // Generate a unique id for the file
        $key = $this->accountId . "/" . $file->getId() . "/";
        $key .= $file->getValue("revision") . "/" . $file->getName();

        // Construct the full request with all params
        $url = $this->requestUrl;
        $url .= "&function=file_upload&folder=".rawurlencode($this->ansFolder);
        $url .= "&filename=".rawurlencode($key)."&size=$size";

        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_INFILESIZE, $size);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("file" => new \CurlFile($localPath)));
        $response = curl_exec($ch);
        //echo "<pre>RESP: $url\n\n$response</pre>";
        if (curl_errno($ch))
        {
            $this->errors[] = new Error\Error(curl_error($ch));
        }
        curl_close($ch);

        // Parse response
        if ($response)
        {
            $xml = simplexml_load_string($response);
            $retval = $xml->retval;

            // Stop if we had an error and stored the details in $this->errors
            if (1 != intval($xml->retval))
            {
                $this->errors[] = new Error\Error(($xml->message) ? $xml->message : $retval);
                return false;
            }
        }

        // Update file entity
        $file->setValue("file_size", $size);
        // If set, then clear. Path will remain in saved revision.
        $file->setValue("dat_local_path", "");
        $file->setValue("dat_ans_key", $key);
        $this->entityDataMapper->save($file);

        return true;
    }

    /**
     * Delete a file from the DataMapper
     *
     * @param FileEntity $file The file to purge data for
     * @param int $revision If set then only delete data for a specific revision
     * @return mixed
     */
    public function deleteFile(FileEntity $file, $revision = null)
    {
        // Assume failure until we succeed
        $result = false;

        // Setup the base URL for request to the ans server
        $baseUrl = $this->requestUrl;
        $baseUrl .= "&function=file_delete&folder=".rawurlencode($this->ansFolder);

        // Delete the current file
        $deleteUrl = $baseUrl . "&file=".rawurlencode($file->getValue("dat_ans_key"));
        $response = file_get_contents($deleteUrl);

        if ($response)
        {
            $xml = simplexml_load_string($response);
            if (1 === intval($xml->retval))
            {
                $result = true;
            }
            else
            {
                // Not a success, get the error
                $this->errors[] = new Error\Error($xml->message);
            }
        }

        // Delete all past revisions
        $revisions = $this->entityDataMapper->getRevisions("file", $file->getId());
        foreach ($revisions as $fileRev)
        {
            if ($fileRev->getValue("dat_ans_key"))
            {
                try {
                    $deleteUrl = $baseUrl . "&file=" . rawurlencode($fileRev->getValue("dat_ans_key"));
                    $response = file_get_contents($deleteUrl);

                    if ($response)
                    {
                        $xml = simplexml_load_string($response);
                        if (1 != intval($xml->retval))
                        {
                            // Not a success, get the error
                            $this->errors[] = new Error\Error($xml->message);
                        }

                    }
                } catch (Exception\FileNotFoundException $ex) {
                    $this->errors[] = new  Error\Error($ex->getMessage());
                }
            }
        }

        return $result;
    }

    /**
     * Check to see if a file exists in the storeexit
     *
     * @param FileEntity $file The file to purge data for
     * @return bool true if it exists, otherwise false
     */
    public function fileExists(FileEntity $file)
    {
        // If we are missing a key then we know for sure it does not exist in the store
        if (!$file->getValue('dat_ans_key'))
            return false;

        // Construct the full request with all params
        $url = $this->requestUrl;
        $url .= "&function=file_exists&folder=".rawurlencode($this->ansFolder);
        $url .= "&file=".rawurlencode($file->getValue('dat_ans_key'));

        $response = file_get_contents($url);

        if ($response)
        {
            $xml = simplexml_load_string($response);
            if (rawurldecode($xml->retval) == "1")
            {
                return true;
            }
            else
            {
                $this->errors[] = new Error\Error(rawurldecode($xml->message));
                return false;
            }
        }

        return false;
    }

    /**
     * Get the last error thrown in an object or module
     *
     * @return Error\Error
     */
    public function getLastError()
    {
        return (count($this->errors)) ? $this->errors[count($this->errors) - 1] : null;
    }

    /**
     * Get all errors
     *
     * @return Error\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Generate a temp name for this file so we can work with it autonomously in the tmp dir
     *
     * @param FileEntity $file
     * @return string unique temp name
     */
    private function getTempName(FileEntity $file)
    {
        return "file-" . $this->accountId . "-" . $file->getId() . "-" . $file->getValue('revision');
    }
}