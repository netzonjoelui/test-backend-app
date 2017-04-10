<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2014 Aereus
 */
namespace Netric\Application\Response;

/**
 * Defines an interface for responses from controllers
 */
class HttpResponse implements ResponseInterface
{
    /**
     * Set the mime content type of this response
     *
     * @var string
     */
    private $contentType = self::TYPE_TEXT_HTML;
    const TYPE_TEXT_PLAIN = 'text/plain';
    const TYPE_TEXT_HTML = 'text/html';
    const TYPE_IMAGE_GIF = 'image/gif';
    const TYPE_IMAGE_JPEG = 'image/jpeg';
    const TYPE_IMAGE_PNG = 'image/png';
    const TYPE_IMAGE_BMP = 'image/bmp';
    const TYPE_JSON = 'application/json';
    const TYPE_BINARY = 'application/octet-stream';

    /**
     * Headers to send to the client
     *
     * @var array
     */
    private $headers = [];

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
        // TODO: Right now we do not enforce types, but we should
        $this->contentType = $contentType;
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
        $this->headers[strtolower($header)] = $value;
    }
}
