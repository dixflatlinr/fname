<?php

namespace DF\App\FName;

interface FNameValidatorInterface
{
    function validateLongExtension($longExtension);
    function validateBasename($filename);
    function validateExtension($extension);
    function validateBody($filebody);
    function validatePath($path);
}