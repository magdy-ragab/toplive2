<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use \App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

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
     * Store user data by phone number.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Exception if an error occurs while storing the image or creating the user
     */
    public function storeByPhone()
    {

        $validator = Validator::make(request()->all(), [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => 'required|unique:users,mobile',
            'pic' => 'nullable|image|max:2048', // max size of 2MB
            'password' => 'required|string|min:8',
            'country' => 'required|max:255',
            'gender' => 'required',
            "birth_date"=> ['required', 'date', 'before:today'],
            'otp' => 'required|integer|digits_between:3,8'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        try {
            $validatedData = $validator->validated();
            $validatedData['password'] = bcrypt($validatedData['password']);

            if (request()->hasFile('pic')) {
                $validatedData['pic'] = request()->file('pic')->store('public/users');
            }

            $user = User::create($validatedData);
            $this->generateOtp($user->id, request('otp'));
            $this->loginById($user->id);

            return response()->json([
                'token'     => $user->createToken('app')->plainTextToken ,
                'user_data' => User::find($user->id) ,
                'otp'       => (int) request('otp') ,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
# ##########################################################
    /**
     * Login or register a user using Facebook credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByFacebook(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email'],
            'mobile' => ['nullable', 'numeric'],
            'name' => ['required', 'string'],
            'pic' => ['nullable', 'string'],
        ]);

        // If neither email nor phone is provided, return an error response
        if (!$request->has('email') && !$request->has('mobile')) {
            return response()->json(['msg' => 'Either email or mobile is required'], 400);
        }
        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        // Find the user with the given email or mobile number
        $user = null;
        if ($request->has('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->has('mobile')) {
            $user = User::where('mobile', $request->mobile)->first();
        }

        // If the user doesn't exist, create a new user
        if (!$user) {
            $user = User::create([
                'name'                => $request->name,
                'email'               => $request->email,
                'mobile'              => $request->mobile,
                'pic'                 => $request->pic,
                "email_verified_at"   => now() ,
                'login_by'            => 'facebook',
            ]);
            $status = 201; // Created
        } else {
            $user->update([ "pic" => $request->pic] );
            $status = 200; // OK
        }

        // Generate a new token for the user
        $token = $user->createToken('app')->plainTextToken;

        // Return a success response with the user data and token
        return response()->json([
            'user' => $user,
            'token' => $token,
        ], $status);
    }
# ##########################################################
    /**
     * Login or register a user using Google credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByGoogle(Request $request)
    {
        // Validate the request parameters
        $validator = Validator::make($request->all(), [
            'email' => ['nullable', 'email'],
            'name' => ['required', 'string'],
            'pic' => ['nullable', 'string'],
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 400);
        }

        // Find the user with the given email
        $user = User::where('email', $request->email)->first();

        // If the user doesn't exist, create a new user
        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'pic' => $request->pic,
                'login_by' => 'google',
                'email_verified_at' => now(),
            ]);
            $status = 201; // Created
        } else {
            $status = 200; // OK
        }

        // Generate a new token for the user
        $token = $user->createToken('app')->plainTextToken;

        // Return a success response with the user data and token
        return response()->json([
            'user' => $user,
            'token' => $token,
        ], $status);
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
                $this->generateOtp($user->id);
            }
            $this->loginById($user->id);
            return response()->json(["user_data"=>$user,"header_code"=>201], 201);
        }
    }
# ##########################################################

/**
 * Update the authenticated user's data.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function updateUserData(Request $request)
{
    $valid = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'mobile' => 'required|string|max:255',
        'pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'password' => 'nullable|string|min:6',
        'country' => 'nullable|string|max:255',
        'gender' => 'nullable|string|max:255',
        'birth_date' => 'nullable|date',
    ]);

    if ($valid->fails()) {
        return response()->json([
            'errors' => $valid->messages(),
        ], 403);
    }

    $token = $this->getTokenFromRequest($request);

    if (!$token) {
        return response()->json([
            'msg' => 'Token not found in request',
        ], 401);
    }

    $user = PersonalAccessToken::findToken($token)->tokenable;

    if (!$user) {
        return response()->json([
            'msg' => 'Invalid token',
        ], 401);
    }

    $user->name = $request->input('name');
    $user->mobile = $request->input('mobile');
    $user->country = $request->input('country');
    $user->gender = $request->input('gender');
    $user->birth_date = $request->input('birth_date');

    if ($request->hasFile('pic')) {
        $pic = $request->file('pic');
        $filename = time() . '_' . $pic->getClientOriginalName();
        $pic->move(public_path('users'), $filename);
        $user->pic = $filename;
    } elseif (!$request->has('pic')) {
        $user->pic = $user->old_pic;
    }

    if ($request->has('password')) {
        $user->password = Hash::make($request->input('password'));
    }

    $user->save();

    return response()->json([
        'msg' => 'User data updated successfully',
        'user' => $user,
    ]);
}
# ##########################################################
    /**
     * Update the authenticated user's profile picture.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserImage(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'pic' => 'required|image|max:2048',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'errors' => $valid->messages(),
            ], 403);
        }

        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'msg' => 'Token not found in request',
            ], 401);
        }

        $user = PersonalAccessToken::findToken($token)->tokenable;

        if (!$user) {
            return response()->json([
                'msg' => 'Invalid token',
            ], 401);
        }


        $user->pic = request()->file('pic')->store('public/users');

        $user->save();

        return response()->json([
            'msg' => 'User image updated successfully',
            'user' => $user,
        ]);
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
    function getCurrentUser( Request $request ) {
        $token = $request->token;

    if (!$token) {
        return response()->json([
            'msg' => 'Token not found in request',
        ], 401);
    }

    $user = PersonalAccessToken::findToken($token)?->tokenable;

    if (!$user) {
        return response()->json([
            'msg' => 'Invalid token',
        ], 401);
    }

    return response()->json([
        'user' => $user,
    ]);
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
         * Authenticate a user using email and password.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Illuminate\Http\JsonResponse
         */
    public function loginEmail(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'email' => 'required|email|max:255|exists:users,email',
            'password' => 'required|string',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'errors' => $valid->messages(),
            ], 403);
        }

        $email = $request->input('email');
        $password = $request->input('password');

        $guard = Auth::guard('web');

        if ($guard->attempt(['email' => $email, 'password' => $password])) {
            $user = $guard->user();
            $token = $user->createToken('app')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } else {
            return response()->json([
                'msg' => 'Invalid email or password',
            ], 403);
        }
    }
# ##########################################################
    /**
     * Authenticate a user using mobile number and password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginByMobile(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'mobile' => 'required|string|max:255|exists:users,mobile',
            'password' => 'required|string',
        ]);

        if ($valid->fails()) {
            return response()->json([
                'errors' => $valid->messages(),
            ], 403);
        }

        $mobile = $request->input('mobile');
        $password = $request->input('password');

        $guard = Auth::guard('web');

        if ($guard->attempt(['mobile' => $mobile, 'password' => $password])) {
            $user = $guard->user();
            $token = $user->createToken('app')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } else {
            return response()->json([
                'msg' => 'Invalid mobile number or password',
            ], 403);
        }
    }
# ##########################################################
    /**
     * verify otp code
     *
     * @method POST
     * @param int mobile
     * @param int otp
     *
     * @return void
     */
    function verifyOtp(Request $request) {
        $mobile = $request->mobile;
        $otp = $request->otp;

        // Find the user with the given mobile number
        $user = User::where('mobile', $mobile)->first();

        // If the user doesn't exist, return an error response
        if (!$user) {
            return response()->json(['msg' => 'Invalid user'], 404);
        }

        // If the user has already been verified, return an error response
        if ($user->email_verified_at) {
            return response()->json(['msg' => 'User already verified'], 410);
        }

        // If the user doesn't have an OTP or the OTP value is incorrect, return an error response
        // return($user);
        if ( !$user->otp || ! $user->otp->otp ) {
            return response()->json(['msg' => 'Invalid OTP'], 404);
        }

        // Delete the OTP and update the user's email_verified_at field
        $user->otp->delete();
        $user->update(['email_verified_at' => now()]);

        // Return a success response with the user data and a token
        return response()->json([
            'msg' => 'OTP verified',
            'user' => $user,
            'token' => $user->createToken('app')->plainTextToken,
        ], 200);

    }
# ##########################################################
    /**
     * Generate a new OTP for the user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function newOtp()
    {
        // Validate the request parameters
        $validator = Validator::make(request()->all(), [
            'mobile' => ['required'],
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 404);
        } else {
            // Find the user with the given mobile number
            $user = User::where('mobile', request('mobile'))->first();

            // If the user doesn't exist, return an error response
            if (!$user) {
                return response()->json(['msg' => 'This user does not exist'], 404);
            } else {
                // Generate a new OTP for the user
                $otp = $this->generateOtp($user->id , request('otp')?request('otp'):0);

                // Return a success response with the OTP
                return response()->json(['otp' => $otp], 201);
            }
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

    /* **********************************************************
       * [PRIVATE METHODS ] **************************************
       ***********************************************************
    */
    private function loginById( int $id ) : bool {
        $user= User::find($id); if ( $user === null ) return false;
        Auth::loginUsingId($id);
        return Auth::check();
    }

    # ##########################################################

    private function generateOtp(int $user_id, int $otp=0) {
        Otp::where(["user_id"=>$user_id])->delete();
        $otp = ($otp !== 0 ? $otp : rand(999,9999)) ;
        return Otp::create(['user_id'=>$user_id, "otp"=>  $otp ]);
    }

    # ##########################################################
    private function getTokenFromRequest( ) {
        return request('token');
    }
}
