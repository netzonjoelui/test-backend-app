<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Header;

class To extends AbstractAddressList
{
    protected $fieldName = 'To';
    protected static $type = 'to';
}
