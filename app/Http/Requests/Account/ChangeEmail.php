<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\ApiRequest;

class ChangeEmail extends ApiRequest
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'link' => 'required|url|regex:/__hash__/',
        ];
    }
}
