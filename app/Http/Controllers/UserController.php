<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use \App\Models\User;
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
            $user->password = $request->password ? Hash::make($request->password) : $user->password;
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
    function getCurrentUser() {
        return ["is_logged"=>Auth::check(), "user"=> Auth::user()];
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
    private function loginById( int $id ) {
        Auth::loginUsingId($id);
    }

}
