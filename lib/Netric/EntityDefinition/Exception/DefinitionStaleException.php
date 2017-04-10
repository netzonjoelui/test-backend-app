<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\EntityDefinition\Exception;

/**
 * This exception is triggered if the saved entity definition is behind
 * the code definition -- commone when the definition changes in the system
 */
class DefinitionStaleException extends \RuntimeException
{
}