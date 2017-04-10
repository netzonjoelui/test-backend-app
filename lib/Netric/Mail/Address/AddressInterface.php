<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Address;

interface AddressInterface
{
    public function getEmail();
    public function getName();
    public function toString();
}
