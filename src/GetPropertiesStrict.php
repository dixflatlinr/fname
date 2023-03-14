<?php

namespace DF\App\FName;

trait GetPropertiesStrict
{
    public function __get($name)
    {
        if ( !property_exists($this, $name) )
            throw new \InvalidArgumentException("$name property not exists in " . get_class($this) . "object!");

        return $this->$name;
    }

    function __isset($name)
    {
        if (property_exists($this, $name))
            return true;

        return false;
    }
}
