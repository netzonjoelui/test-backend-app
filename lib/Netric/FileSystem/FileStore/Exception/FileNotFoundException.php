<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\FileSystem\FileStore\Exception;

/**
 * Triggered when a datamapper tries to access a file that does not exist
 */
class FileNotFoundException extends \RuntimeException
{
}