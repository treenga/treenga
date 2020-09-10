<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class ChangePassword extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public $user;

    public function authorize()
    {
        $this->user = auth()->user();
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'new_password' => 'required',
            'old_password' => 'required',
        ];
    }
}
