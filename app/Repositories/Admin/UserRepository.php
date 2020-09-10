<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;
use App\User;

class UserRepository
{
    protected $mainModel;

    public function __construct()
    {
        $this->mainModel = new User;
    }

    public function getAll()
    {
        return $this->mainModel->where('role', User::USER_ROLE)->get();
    }

    public function getOne(User $user)
    {
        return $user;
    }

    public function itemUpdate(User $user, array $data)
    {
        $user->fill($data);
        $user->save();
        return $user;
    }
}