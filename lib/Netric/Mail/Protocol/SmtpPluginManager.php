<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail\Protocol;

use Netric\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager implementation for SMTP extensions.
 *
 * Enforces that SMTP extensions retrieved are instances of Smtp. Additionally,
 * it registers a number of default extensions available.
 */
class SmtpPluginManager extends AbstractPluginManager
{
    /**
     * Default set of extensions
     *
     * @var array
     */
    protected $invokableClasses = [
        'crammd5' => 'Netric\Mail\Protocol\Smtp\Auth\Crammd5',
        'login'   => 'Netric\Mail\Protocol\Smtp\Auth\Login',
        'plain'   => 'Netric\Mail\Protocol\Smtp\Auth\Plain',
        'smtp'    => 'Netric\Mail\Protocol\Smtp',
    ];

    /**
     * Validate the plugin
     *
     * Checks that the extension loaded is an instance of Smtp.
     *
     * @param  mixed $plugin
     * @return void
     * @throws Exception\InvalidArgumentException if invalid
     */
    public function validatePlugin($plugin)
    {
        if ($plugin instanceof Smtp) {
            // we're okay
            return;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Plugin of type %s is invalid; must extend %s\Smtp',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            __NAMESPACE__
        ));
    }
}
