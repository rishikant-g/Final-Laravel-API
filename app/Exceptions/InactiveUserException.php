<?php

namespace App\Exceptions;

use Exception;

class InactiveUserException extends Exception
{
    public function render($request)
    {
        return response()->json(['status' => false, 'message' => 'Inactive user']);
    }
}
