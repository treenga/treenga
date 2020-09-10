<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;

class ApiCustomException extends Exception
{
    protected $code = 500;

    protected $message = '';

    protected $errors = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        return response()->error($this->message, $this->code);
    }

    public function withCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function withMessage($message)
    {
        $this->message = __($message);
        return $this;
    }

    public function withError($message, $type)
    {
        request()->addError($message, $type);
        return $this;
    }
}
