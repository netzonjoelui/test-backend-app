<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2016 Aereus
 */
namespace Netric\Crypt;

/**
 * Service for securely storing and retrieving shared secrets
 */
class VaultService
{
    /**
     * Get a secret for a given key
     *
     * @param string $key
     * @return string The shared secret
     */
    public function getSecret($key)
    {
        // TODO: Right now this will just return a single static key
        // TODO: but later this should be stored in a secure store somewhere
        return "fdsagfdaahah354h6gf4s3h2fgs65h46";
    }
}