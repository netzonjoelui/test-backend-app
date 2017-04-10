<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Application\Response;

/**
 * Defines an interface for responses from controllers
 */
interface ResponseInterface
{
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
    public function setContentType($contentType);

    /**
     * Set a header for the response
     *
     * This may or may not be supported by the specific response, like
     * a console response will just ignore the header completely.
     *
     * @param string $header The name of the header to set
     * @param string|int $value The value to set the header to
     */
    public function setHeader($header, $value);

    /**
     * Set a stream for a response
     *
     * @param resource $stream
     */
    //public function setStream($stream);

}
