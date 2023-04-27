<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use \App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    function __construct() {
        if ( $rst = $this->testHeaders() ) {
            return $rst;
        }
    }

# ##########################################################
    /**
     * test , playground, check is file has no errors
     *
     * @return  array
     */
    function test() {
        return ["hello from"=>"test function", "time now is"=>date('Y-m-d H:i:s')];
    }
# ##########################################################


    /**
     * list all users
     *
     * @return  array user list
     */
    public function index()
    {
        return User::get();
    }
# ##########################################################


    /**
     * store new user
     *
     * @return  array new created user
     */
    public function store()
    {
        // dd(request());
        // Validate the request data
        $valid = Validator::make(request()->all(),[
            'name' => ['required','string','max:255'],
            'mobile' => 'nullable',
            'email' => 'string|nullable|email|max:255|unique:users',
            'country' => 'required|max:255',
            'gender' => 'required',
            'password' => 'required|string|min:8',
        ]);
        if($valid->fails()) {
            return response()->json( ["errors"=>$valid->messages(),"header_code"=>403], 403);
        }else{
            $attr= request()->validate([
                'name' => ['required','string','max:255'],
                'mobile' => 'nullable',
                'email' => 'nullable|email|max:255|unique:users',
                'country' => 'required|max:255',
                'gender' => 'required',
                'password' => 'required|string|min:8',
            ]);
            $attr['password']= bcrypt ($attr['password']) ;
            // Create a new user instance
            $user= User::create($attr);
            // login user
            if ( $attr['mobile'] != null ) {
                Otp::create(['user_id'=>$user->id, "otp"=> rand(999,9999) ]);
            }
            $this->loginById($user->id);
            return response()->json(["user_data"=>$user,"header_code"=>201], 201);
        }
    }
# ##########################################################


    /**
     * update user data
     *
     * @param   Request  $request
     * @param   int   $id       user.id
     *
     * @return  array        success updated user | error message
     */
    public function update(Request $request, int $id)
    {
        // Find the user by id
        $user = User::find($id);

        // Check if the user exists
        if ($user) {
            // Validate the request data
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8',
            ]);

            // Update the user attributes
            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;
            $user->password = $request->password ? bcrypt($request->password) : $user->password;
            $user->save();

            // Return the updated user
            return response()->json($user, 200);
        } else {
            // Return an error message
            return response()->json(['message' => 'User not found'], 404);
        }
    }
# ##########################################################


    /**
     * destroy (remove) user by ID
     *
     * @param   int  $id  user.id
     *
     * @return  array    message
     */
    public function destroy(int $id)
    {
        // Find the user by id
        $user = User::find($id);

        // Check if the user exists
        if ($user) {
            // Delete the user
            $user->delete();

            // Return a success message
            return response()->json(['message' => 'User deleted'], 200);
        } else {
            // Return an error message
            return response()->json(['message' => 'User not found'], 404);
        }
    }
# ##########################################################


    /**
     * display user data
     *
     * @param   int  $id  user.id
     *
     * @return  object    user data
     */
    function show (int $id) {
        return User::findOrFail($id);
    }
# ##########################################################
    /**
     * get current loggedin user
     *
     * @return  array  userdata
     */
    function getCurrentUser() {
        return ["is_logged"=>Auth::check(), "user"=> Auth::user(), "header_code"=>200];
    }
# ##########################################################
    /**
     * login user by id, useful when login by facebook,twitter,appleID
     *
     *
     * @method POST
     * @param int id user.id
     * @param string _token
     *
     */
    public function appLoginById()
    {
        $id= request('id');
        if ( ! $id ) return response()->json(["msg"=> "missing id", "header_code"=>401], 401);
        if ( $this->loginById($id) ) {
            return $this->getCurrentUser();
        }
        return response()->json(["msg"=> "not logged in.invalid id", "header_code"=>401], 401);
    }
# ##########################################################
    /**
     * login user by email and password
     *
     * @method POST
     * @param string $email user email
     * @param string password user password
     * @param string _token
     *
     */
    public function loginEmail()
    {
        $email= request('email');
        $password= request('password');
        $valid = Validator::make(request()->all(),[
            'email' => 'required|email|max:255|exists:users,email',
            'password' => 'required|string',
        ]);
        if($valid->fails()) {
            return response()->json( ["errors"=>$valid->messages(),"header_code"=>403], 403);
        }else{
            $ret = Auth::attempt(['email'=>$email,"password"=>$password]);
            if ( $ret ) return $this->getCurrentUser();
            else return response()->json( ["msg"=>"invalid email or password","header_code"=>403], 403);
        }
    }
# ##########################################################
    /**
     * verify otp code
     *
     * @method POST
     * @param int mobile
     * @param int otp
     * @param string _token
     *
     * @return void
     */
    function verifyOtp() {
        $mobile= request('mobile');
        $otp= request('otp');
        $user= User::where(["mobile"=>$mobile])->first();
        if ( $user == null ){
            return response()->json( ["msg"=>"Not a valid user","header_code"=>404], 404);
        }else if ($user->otp == null) {
            return response()->json( ["msg"=>"Not a valid OTP","header_code"=>404], 404);
        }else if ( $user->otp?->otp != $otp ) {
            return response()->json( ["msg"=>"Wrong OTP value","header_code"=>404], 404);
        }else {
            Otp::where("id",$user->otp->id)->delete();
            User::where("id",$user->id)->update(["email_verified_at"=>Carbon::now()]);
            return response()->json( [
                "msg"=>"Otp verified" ,
                "user"=>User::where(["mobile"=>$mobile])->first() ,
                "header_code"=>200
            ], 200);
        }
    }
# ##########################################################



    /**
     * test headers
     *
     * @return  void
     */
    private function testHeaders() {
        $headers = request()->header();
        //406 Not Acceptable
            // The requested resource is capable of generating only content not acceptable according
            // to the Accept headers sent in the request
        if ( ! array_key_exists("app-name",$headers)  || ! array_key_exists("app-key",$headers) ) {
            header('Content-type: text/json');
            http_response_code(406);
            echo json_encode(["msg"=>"KEYS ARE MISSING","header_code"=>406]);
            die;
        }elseif(
            current($headers['app-name']) != env('APP_NAME') ||
            current($headers['app-key'])!= env('APP_KEY')
        ) {
            header('Content-type: text/json');
            http_response_code(406);
            echo json_encode(["msg"=>"WRONG KEY VALUES","header_code"=>406], 406);
            die;
        }
    }
# ##########################################################
    private function loginById( int $id ) : bool {
        $user= User::find($id); if ( $user === null ) return false;
        Auth::loginUsingId($id);
        return Auth::check();
    }

}
