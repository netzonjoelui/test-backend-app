<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */
namespace Netric\WorkFlow\Action\Exception;

/**
 * Indicate that an action is referening itself in a child action which is circular
 */
class CircularChildActionsException extends \InvalidArgumentException
{
}