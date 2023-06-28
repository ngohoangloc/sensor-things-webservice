<?php

namespace App\Models\User;
use App\Constant\TablesName;
use Illuminate\Foundation\Auth\User as Authenticatable;
class User extends Authenticatable
{
    protected $primaryKey='id';
    protected $fillable = [
        'username',
        'password',
    ];
    protected $hidden = [
        'password', 'remember_token',
    ];
    protected $table=TablesName::Users;
    public function roles(){
        return $this->belongsToMany('App\Models\Role',TablesName::User_Role,'userId','roleId');
    }
}
