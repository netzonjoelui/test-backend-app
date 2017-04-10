<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Console\Exception;

/**
 * This exception is thrown when a user tries to run a program without
 * a supporing script in /bin/scripts/*
 */
class ScriptNotFoundException extends \RuntimeException
{
}