<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\Application\Response;

/**
 * Response for a console
 */
class ConsoleResponse implements ResponseInterface
{
    /**
     * Buffer output
     *
     * @var string[]
     */
    private $outputBuffer = array();

    /**
     * Flag to suppress output
     *
     * @var bool
     */
    private $suppressOutput = false;

    /**
     * Set the mime content type of this response
     *
     * For console we only support plain text and json (beautified when pritned)
     *
     * @var string
     */
    private $contentType = self::TYPE_TEXT_PLAIN;
    const TYPE_TEXT_PLAIN = 'text/plain';
    const TYPE_JSON = 'application/json';

    /**
     * The return code of the script
     *
     * @var int
     */
    private $returnCode = 0;

    /**
     * Set the content type of this response
     *
     * If not set the response object will try to detect the content-type
     * from the returned value.
     *
     * @param $contentType
     * @return mixed
     * @throws Exception\ContentTypeNotSupportedException If invalid type used for this response
     */
    public function setContentType($contentType)
    {
        // Make sure we use only a supported type
        switch ($contentType)
        {
            case self::TYPE_TEXT_PLAIN:
            case self::TYPE_JSON:
                $this->contentType = $contentType;
                break;
            default:
                throw new Exception\ContentTypeNotSupportedException($contentType . " not supported");
        }
    }

    /**
     * Set a header for the response
     *
     * This may or may not be supported by the specific response, like
     * a console response will just ignore the header completely.
     *
     * @param string $header The name of the header to set
     * @param string|int $value The value to set the header to
     */
    public function setHeader($header, $value)
    {
        // Headers are not supported in the console
    }


    /**
     * Write text to the console
     *
     * @param string $text The text to write
     */
    public function write($text)
    {
        if ($this->suppressOutput) {
            $this->outputBuffer[] = $text;
        } else {
            echo $text;
        }
    }

    /**
     * Write text to the console and break with a new line
     *
     * @param string $text The text to write to a line
     */
    public function writeLine($text)
    {
        if ($this->suppressOutput) {
            $this->outputBuffer[] = $text;
        } else {
            echo $text . "\n";
        }
    }

    /**
     * Turn on or off output suppression
     *
     * @param $flag
     */
    public function suppressOutput($flag)
    {
        $this->suppressOutput = $flag;
    }

    /**
     * Get the buffer
     *
     * @return string[]
     */
    public function getOutputBuffer()
    {
        return $this->outputBuffer;
    }

    /**
     * Set the return code for the console response
     *
     * @param int $code
     */
    public function setReturnCode($code)
    {
        $this->returnCode = $code;
    }

    /**
     * Get the return code of the console response
     * 
     * @return int
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }
}
