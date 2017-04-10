<?php
/**
 * @author joe <sky.stebnicki@aereus.com>
 * @copyright 2015 Aereus
 */

namespace Netric\Mail;

use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Validator\Hostname;

class Address implements Address\AddressInterface
{
    protected $email;
    protected $name;

    /**
     * Constructor
     *
     * @param  string $email
     * @param  null|string $name
     * @throws Exception\InvalidArgumentException
     * @return Address
     */
    public function __construct($email, $name = null)
    {
        $emailAddressValidator = new EmailAddressValidator(Hostname::ALLOW_LOCAL);
        if (! is_string($email) || empty($email)) {
            throw new Exception\InvalidArgumentException('Email must be a valid email address: ' . var_export($email, true));
        }

        if (preg_match("/[\r\n]/", $email)) {
            throw new Exception\InvalidArgumentException('CRLF injection detected');
        }

        if (! $emailAddressValidator->isValid($email)) {
            $invalidMessages = $emailAddressValidator->getMessages();
            throw new Exception\InvalidArgumentException($email . ":". array_shift($invalidMessages));
        }

        if (null !== $name) {
            if (! is_string($name)) {
                throw new Exception\InvalidArgumentException('Name must be a string');
            }

            if (preg_match("/[\r\n]/", $name)) {
                throw new Exception\InvalidArgumentException('CRLF injection detected');
            }

            $this->name = $name;
        }

        $this->email = $email;
    }

    /**
     * Retrieve email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Retrieve name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * String representation of address
     *
     * @return string
     */
    public function toString()
    {
        $string = '<' . $this->getEmail() . '>';
        $name   = $this->getName();
        if (null === $name) {
            return $string;
        }

        return $name . ' ' . $string;
    }
}
