<?php

namespace DF\App\FName;

interface FNameValidatorInterface
{
    function validateExtension($extension);
    function validateBody($filebody);
    function validatePath($path);
}