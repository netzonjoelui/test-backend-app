<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Header;

class Bcc extends AbstractAddressList
{
    protected $fieldName = 'Bcc';
    protected static $type = 'bcc';
}
