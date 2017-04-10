<?php

namespace NetricTest\Mail\Fixture;

class StringSerializableObject
{
    public function __construct($message)
    {
        $this->message = $message;
    }

    public function __toString()
    {
        return $this->message;
    }
}
