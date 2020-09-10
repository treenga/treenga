<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class Reset extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'hash' => 'required|size:32',
            'password' => 'required',
        ];
    }
}
