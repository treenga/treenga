<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;

class ApiValidationException extends Exception
{
    protected $validate = '';

    protected $code = 422;

    protected $message = 'Validation failed';

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        return response()->error($this->message, $this->code, $this->validate);
    }

    public function withValidator(Validator $validator)
    {
        $this->validate = $validator->getMessageBag()->toArray();
        return $this;
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
}