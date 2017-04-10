<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Header;

class From extends AbstractAddressList
{
    protected $fieldName = 'From';
    protected static $type = 'from';
}
