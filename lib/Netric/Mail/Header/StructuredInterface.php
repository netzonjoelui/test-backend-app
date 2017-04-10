<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Header;

interface StructuredInterface extends HeaderInterface
{
    /**
     * Return the delimiter at which a header line should be wrapped
     *
     * @return string
     */
    public function getDelimiter();
}
