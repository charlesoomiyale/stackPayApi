<?php

namespace App\Http\Controllers;

use App\Mail\AccountRegistered;
use App\Mail\ForGotPassword;
use App\Mail\PasswordReset;
use App\Traits\AuthValidation;
use App\Transformers\Auth\UserTransformer;
use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
//use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
//use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTAuth;
use League\Fractal;

class AuthController extends ApiController {

    use AuthValidation;

    protected $jwt;
    public function __construct(JWTAuth $jwt)
    {
        $this->middleware('auth:api', ['except' => ['login','register','guard','forgotPassword','resetPassword']]);
        $this->jwt = $jwt;
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'status' => 200
        ]);
    }

    protected function guard()
    {
        return Auth::guard();
    }


    public function login(Request $request)
    {
//        Log::info($request->all());

        try {
            $this->validate($request, [
                'email' => 'required|email|max:255',
                'password' => 'required'
            ]);
        } catch (ValidationException $e) {
        }
        $credentials_basic = ['email' => $request->email, 'password' => $request->password];

        if (! $token = $this->jwt->attempt($credentials_basic)) {
            return response()->json(['message' => 'Invalid Username/Password', 'status' => 'fail'], 404);
        }

        $resource = new Fractal\Resource\Item($request->user(),new UserTransformer);
        $user = $this->apiResponse()->createData($resource)->toArray();

        User::where('email',$credentials_basic['email'])->increment('login_count', 1,[
            'last_login_at'=> Carbon::now()
        ]);

        $u = User::where('email',$credentials_basic['email'])->first();


        $user['data']['device'] = $u->device() ? $u->device : null;

        return response()->json([
            'token' => $token,
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'status' => 'success',
            'user' =>$user['data'],
            'message' => 'Successfully logged In'
        ]);
    }

    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out', 'status' => '200'], 200);
    }

    public function refresh(Request $request)
    {
//        $this->respondWithToken($this->guard()->refresh());

        $resource = new Fractal\Resource\Item($request->user(),new UserTransformer);
        $user = $this->apiResponse()->createData($resource)->toArray();

        $newToken = Auth::refresh(true, true);

        return response()->json([
            'token' => $newToken,
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'status' => 'success',
            'user' =>$user['data'],
            'message' => 'Successfully logged In'
        ]);
    }

    public function register(Request $request)
    {
        $valid = $this->validateRegister($request->toArray());
        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }
        $credentials = [
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'firstname' =>$request->firstname,
            'lastname' =>$request->lastname,
            'phone' =>$request->phone,
        ];

        $credentials_basic = ['email' => $request->email, 'password' => $request->password];

        $user = User::create($credentials);

        $resource = new Fractal\Resource\Item($user,new UserTransformer);

        $user = $this->apiResponse()->createData($resource)->toArray();

        $token = $this->jwt->attempt($credentials_basic);

//        Queue::push(new SendOnboardEmail((object)[
//            'email'=>$request->email,
//            'victor'=>$request->firstname,
//        ]));

        Mail::to($request->email)
            ->send(new AccountRegistered($request->firstname));

        return response()->json([
            'token' => $token,
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'status' => 'success',
            'user' =>$user['data'],
            'message' => 'Registration Successful'
        ]);

    }

    public function me(Request $request)
    {
        $resource = new Fractal\Resource\Item($request->user(),new UserTransformer);
        $res= $this->apiResponse()->createData($resource)->toArray();
        $res['data']['device'] = $request->user()->device ? $request->user()->device : null;
        return response()->json($res);
    }

    public function linkDevice(Request $request){

        $valid =  Validator::make($request->all(),  [
                'device_id' => 'required',
                'fcm_token' => 'required'
            ]);
        if (count($valid->errors())){
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => $valid->errors()->first()
            ], 400);
        }

        $action = \App\Device::updateOrCreate(
            ['user_id' =>$request->user()->id],
            ['device_id' => $request->device_id, 'fcm_token' => $request->fcm_token, 'user_id' => $request->user()->id, 'active' => 1]
        );

        return response()->json([
            'status' => 'success',
            'user' =>$action,
            'message' => 'Successfully Saved device'
        ]);
    }

    public function notifications(Request $request){

        $data = \App\Notification::where('user_id',$request->user()->id)->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' =>$data,
            'message' => 'Successful'
        ]);
    }

    public function readNotification($id){

        $data = \App\Notification::where('id',$id)->first();

        \App\Notification::where('id','=',$id)->update(['read_at' => Carbon::now()]);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'message' => 'Successful'
        ]);
    }

    public function readNotifications( Request $request){
        \App\Notification::where('user_id',$request->user()->id)->update(['read_at' => Carbon::now()]);

        $data = \App\Notification::where('user_id',$request->user()->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'message' => 'Successful'
        ]);
    }

    public function update(Request $request){
        if (!$request->has('firstname')) {
            return response()->json([
                'message' => 'Validation error. Firstname is required.',
                'status' => '400'], 400);
        }
        if (!$request->has('lastname')) {
            return response()->json([
                'message' => 'Validation error. Lastname is required.',
                'status' => '400'], 400);
        }
        if (!$request->has('phone')) {
            return response()->json([
                'message' => 'Validation error. Phone is required.',
                'status' => '400'], 400);
        }


        try{
            User::where('id', $request->user()->id)->update([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phone' => $request->phone,
            ]);

            $resource = new Fractal\Resource\Item($request->user(),new UserTransformer);
            $res= $this->apiResponse()->createData($resource)->toArray();
            $res['data']['device'] = $request->user()->device ? $request->user()->device : null;

            return response()->json([
                'message' => 'Profile updated.',
                'data' => $res,
                'status' => '200'], 200);
        }catch (\Exception $e){
            return response()->json([
                'message' => 'Profile failed to update.'. $e->getMessage(),
                'status' => '400'], 400);
        }
    }

    public function forgotPassword(Request $request){
        $valid =  Validator::make($request->all(),  [
            'email' => 'required|email',
        ],[
            'email.required'=>'Email is required',
            'email.email'=>'A valid Email is required'
        ]);
        if (count($valid->errors())){
            return response()->json([
                'status' => 'fail',
                'message' => $valid->errors()->first()
            ], 400);
        }

        if (\App\User::where('email', '=', $request->email)->exists()){
            $otp = mt_rand(10000,99999);
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $otp,
                'created_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addMinutes(5)
            ]);

//            $this->closeConnection($otp);
            $user =  DB::table('users')->where('email', '=', $request->email);
            Mail::to($request->email)->send(new ForGotPassword($user->first()->firstname,$otp));

            return response()->json([
                'status' => 'success',
                'message' => 'Enter the OTP sent to your Email'
            ], 400);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'Account not found'
            ], 400);
        }
    }

    public function resetPassword(Request $request){
        $valid =  Validator::make($request->all(),  [
            'email' => 'required|email',
            'otp' => 'required',
            'new_password' => 'required|confirmed',
        ],[
            'email.required'=>'Email is required',
            'otp.required'=>'OTP is required',
            'email.email'=>'A valid Email is required',
        ]);

        if (count($valid->errors())){
            return response()->json([
                'status' => 'fail',
                'message' => $valid->errors()->first()
            ], 400);
        }



        if (\App\User::where('email', '=', $request->email)->exists()){
//            $user =  DB::table('users')->where('email', '=', $request->email)->first();
//            $password_correct = Hash::check($request->current_password, $user->password);
//            if (!$password_correct){
//                return response()->json([
//                    'status' => 'fail',
//                    'message' => 'Invalid Current Password'
//                ], 400);
//            }
        $reset = DB::table('password_resets')
            ->where('email', '=', $request->email)
            ->where('token', $request->otp)
            ->where('expired','=', '0');
//            ->whereDate('expires_at', '<=', Carbon::now())

        if ($reset->exists()){
            $expires_at = $reset->first()->expires_at;
            $expired = Carbon::now()->greaterThan($expires_at);
            if ($expired){
                return response()->json([
                    'status' => 'fail',
                    'message' => 'OTP has Expired'
                ], 400);
            }
            DB::table('password_resets')
                ->where('email', '=', $request->email)
                ->where('token','=', $request->otp)
                ->update(['expired'=> '1']);
            $user =  DB::table('users')->where('email', '=', $request->email);
            $user->update(['password' => Hash::make($request->new_password)]);
            Mail::to($request->email)->send(new PasswordReset($user->first()->firstname));

            return response()->json([
                'status' => 'success',
                'message' => 'Password Reset successful'
            ], 400);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'OTP has Expired'
            ], 400);
        }
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'Account not found'
            ], 400);
        }
    }

    protected function closeConnection($otp){
        // Buffer all upcoming output...
        ob_start();

        // Send your response.
        echo json_encode([
            'status' => 'success',
            'data' => $otp,
            'message' => 'Enter the OTP sent to your Email'
        ]);


        // Disable compression (in case content length is compressed).
        header("Content-Encoding: none");

        // Send JSON content type.
        header("Content-type: application/json");


        // Close the connection.
//        header("Connection: close");

        // Flush all output.
        ob_end_flush();
        ob_flush();
        flush();
    }
}
