<?php

namespace App\Http\Requests\Admin\User;

use App\Http\Requests\ApiRequest;

class AddFounds extends ApiRequest
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
            'funds' => 'required|numeric|gt:0',
            'comment' => 'nullable|string',
        ];
    }
}
