<?php


namespace App\Http\Controllers;

use App\Constant\TablesName;
use App\Constant\UserRolesFixedData;
use App\Models\User\User;
use App\Models\User\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function login(){
        return view('login');
    }
    public function checkLogin(Request $request): RedirectResponse
    {
        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->route('index');
        }else{
            return redirect()->back()->with('errors',["Không thể đăng nhập"])->withInput();
        }
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        return redirect()->route('login');
    }
    public function getFormUser(): \Illuminate\Contracts\View\View
    {
        $strRole='select id as roleId, roleName from ' . TablesName::Roles;
        $roles=DB::select($strRole);
        return View::make('user.formUser')->with('roles',$roles);
    }

    public function getFormChangePwd(){
        return view('user.changePassword');
    }

    public function changePwd(Request  $request){
        $user=Auth::user()['username'];
        logger($request['password']);
        $validationArray=[
            'password' =>  [
                'required'
            ],
            'newPassword' => 'min:3|required_with:rePassword|same:rePassword',
            'rePassword' => 'required'
        ];

        $dbPassword=DB::table(TablesName::Users)->where('username','=',$user)->get("password")[0]->password;
        $result=Hash::check($request['password'],$dbPassword);
        if (!$result){
            return redirect()->back()->with('errors',['Sai mật khẩu']);
        }else{
            $validator = Validator::make($request->all(),$validationArray);
            if ($validator->fails()) {
                $response =$validator->messages()->all();
                return redirect()->back()->with('errors',$response)->withInput();
            }else{
                DB::table(TablesName::Users)->where('username','=',$user)
                    ->update(['password'=>Hash::make($request['newPassword'])]);
                return redirect('/');
            }
        }
    }
    public function changeRole(Request $request){
        $strRole='select id as roleId, roleName from ' . TablesName::Roles;
        $roles=DB::select($strRole);
        $urDB=DB::table(TablesName::Users,'u')->join(TablesName::User_Role . ' as ur','u.id','=','ur.userId')
            ->where('u.username','=',$request['u'])->get('ur.roleId');
        $ur=[];
        foreach ($urDB as $item){
            array_push($ur,$item->roleId);
        }
        return View::make('user.changeRole')->with('roles',$roles)->with('userRoles',$ur);
    }

    public function changeRoleResult(Request $request){
        $validationArray=[
            'roles'=>'required'
        ];
        $customMessages=[
            'roles.required' =>'Chọn ít nhất 1 vai trò'
        ];
        $validator = Validator::make($request->all(),$validationArray,$customMessages);
        if ($validator->fails()) {
            $response =$validator->messages()->all();
            return redirect()->back()->with('errors',$response)->withInput();
        }else{
            $user=User::all()->where('username','=',$request['u']);
            $user=array_values($user->toArray());
            if(count($user)>0){
                $id=$user[0]['id'];
                //delete
                DB::table(TablesName::User_Role)->where('userId','=',$id)->delete();
                //insert

                $roles=$request->input('roles');
                foreach ($roles as $role){
                    $ur=new UserRole([
                        'userId'=>$id,
                        'roleId'=>$role
                    ]);
                    $ur->save();
                }
            }
            return redirect(route('userList'));
        }
    }

    public function deleteUser(Request $request){{
        $user=$request['u'];
        $loginUser=Auth::user()['username'];
        if (Home::minOrMod($loginUser)){
            DB::table(TablesName::Users)->where('username','=',$user)->delete();
            return redirect(route('userList'));
        }else{
            return redirect('/');
        }
    }}

    public function registerUser(Request $request){
        $validationArray=[
            'username' =>  [
                'required',
                'min:2',
                Rule::unique(TablesName::Users, 'username')
                    ->where('username', $request->username),
            ],
            'password' => 'min:3|required_with:r_pwd|same:r_pwd',
            'r_pwd' => 'required',
            'roles'=>'required'
        ];
        $customMessages=[
//            'required' => 'Trường :attribute không được trống.',
            'username.min' => 'Tên tài khoản phải có ít nhất 2 ký tự',
            'username.unique' => 'Tên tài khoản đã được đăng ký, hãy chọn tên khác',
            'password.min' => 'Mật khẩu phải tối thiểu 3 ký tự',
            'password.same' => 'Mật khẩu xác nhận lại không đúng',
            'username.required' => 'Trường Tên tài khoản không được để trống',
            'password.required' => 'Trường mật khẩu không được để trống',
            'r_pwd.required' => 'Trường nhập lại mật khẩu không được để trống',
            'roles.required' =>'Chọn ít nhất 1 vai trò'
        ];

        $validator = Validator::make($request->all(),$validationArray,$customMessages);
        if ($validator->fails()) {
            $response =$validator->messages()->all();
            return redirect()->back()->with('errors',$response)->withInput();
        }else{
            $user = new User();
            $user->fill([
                'username' => $request->username,
                'password' => Hash::make($request->password)
            ]);
            $user->save();

            $user=User::all()->where('username','=',$request->username);
            if(count($user)>0){

                $user=array_values($user->toArray());
                $id=$user[0]['id'];
                $roles=$request->input('roles');
                foreach ($roles as $role){
                    $ur=new UserRole([
                        'userId'=>$id,
                        'roleId'=>$role
                    ]);
                    $ur->save();
                }
            }
            return response("Đã tạo thành công " . $request->username . "<br><a href='" . route('user_register_form') . "'>Đăng ký tài khoản</a>"
                . "<br><a href=' ". route('index') . "'>Quay về trang chủ</a>")->setStatusCode(200);
        }
    }
    public function getUserList(): \Illuminate\Contracts\View\View
    {
        $strUsers='select username from ' . TablesName::Users;
        $users=DB::select($strUsers);
        return View::make('user.userList')->with('users',$users);
    }
}
