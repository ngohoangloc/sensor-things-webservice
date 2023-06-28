<?php

namespace App\Http\Middleware\CustomAuth;

use App\Constant\TablesName;
use App\Constant\UserRolesFixedData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

//xác thực đơn giản
class RestfulAuthenticate
{
    public function handle(Request $request, Closure $next,$role)
    {
        $user=$request->getUser();
        $password=$request->getPassword();
        if($this->validateDB($role,$user,$password)){
        return $next($request);
        }
        return response('You shall not pass!', 401, ['WWW-Authenticate' => 'Basic']);
    }

    //tìm tài khoản trong database
    private function validateDB(int $role,string $user=null, string $password=null):bool{
        if($user!=null){
            $u=DB::table(TablesName::Users)
                ->join(TablesName::User_Role,TablesName::User_Role . ".userId",'=',TablesName::Users . '.id')
                ->where(TablesName::Users . ".username",'=',$user)
                ->where(TablesName::User_Role . '.roleId','=',$role)
                ->get(TablesName::Users  . '.password');
            if(count($u)==1){
                if(Hash::check($password,$u[0]->password)){
                    return true;
                }
            }
        }
        return false;
    }
}
