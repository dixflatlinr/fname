<?php

namespace DF\App\FName;

class FName extends FNameBase
{

    function validateBasename($filename)
    {
        if ( preg_match('~[\x00\x2F]~', $filename) )
        {
            /*
             *
             * Under linux these characters are not allowed:
             *
             * NULL BYTE \x00
             * SLASH \x2F
             *
             */
            throw new \InvalidArgumentException('Null bytes and slashes are not allowed in a basename (pathless filename).');
        }
    }

    function validateLongExtension($longExtension)
    {
        /*
         * Under linux these characters are not allowed:
         *
         * NULL BYTE \x00
         * SLASH \x2F
         *
         */
        if ( preg_match('~[\x00\x2F]~', $longExtension) )
        {
            throw new \InvalidArgumentException('Null bytes, slashes are not allowed in a long extension.');
        }
    }

    function validateExtension($extension)
    {
        /*
         * Under linux these characters are not allowed:
         *
         * NULL BYTE \x00
         * SLASH \x2F
         *
         * Plus adding dots in an extension is pointless
         *
         * DOT . \x2E
         */
        if ( preg_match('~[\x00\x2F\x2E]~', $extension) )
        {
            throw new \InvalidArgumentException('Null bytes, slashes and dots are not allowed in a filename extension.');
        }
    }

    function validateBody($filebody)
    {
        /*
         *
         * Under linux these characters are not allowed:
         *
         * NULL BYTE \x00
         * SLASH \x2F
         *
         */
        if ( preg_match('~[\x00\x2F]~', $filebody) )
        {
            throw new \InvalidArgumentException('Null bytes and slashes are not allowed in a filename body.');
        }
    }

    function validatePath($path)
    {
        if (!$path)
            return;

        if ($path[-1] != '/')
            throw new \InvalidArgumentException('Path part must always end with a slash (/).');

        /*
         *
         * Under linux these characters are not allowed:
         *
         * NULL BYTE \x00
         *
         */
        if ( preg_match('~[\x00]~', $path) )
            throw new \InvalidArgumentException('Null bytes are not allowed in a path.');
    }
}
