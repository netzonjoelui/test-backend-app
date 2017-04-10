<?php
/*
 * Test service used in unit tests to make sure the factory is loading the right service
 *
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\ServiceManager\Test;

/**
 * Class to demonstrate simple service creation
 */
class Service
{
    public function getTestString()
    {
        return "TEST";
    }
}
