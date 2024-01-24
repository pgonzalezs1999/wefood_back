<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Requests\PostStoreImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Auth;
use Validator;
use App\Models\User;
use App\Models\Business;
use App\Models\Favourite;
use App\Models\Comment;

class BusinessController extends Controller
{
    use SoftDeletes;
    
    public function __construct() {
        $this -> middleware('auth:api', ['except' => ['createBusiness']]);
    }

    public function createBusiness(Request $request) {
        $validator = Validator::make($request -> all(), [
            // Create linked user
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone_prefix' => 'required|integer',
            'phone' => 'required|integer|unique:users',
            // Create business
            'name' => 'required|string|max:255|regex:/^[^\d]+$/',
            'tax_id' => 'required|string|max:255|unique:businesses',
            'directions' => 'required|string|max:255',
            'logo_file' => 'required|file|max:2048|image',
        ]);
        if($validator -> fails()) {
            return response() -> json($validator -> errors() -> toJson(), 422);
        }
        $user = User::create([
            'username' => $request->email,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone_prefix' => $request->phone_prefix,
            'phone' => $request->phone,
        ]);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'tax_id' => $request->tax_id,
            'directions' => $request->directions,
        ]);
        try {
            $business_id = $business -> id;
            $image_path = "public/images/{$business_id}";
            $image_name = 'profile.' . $request -> file('logo_file') -> getClientOriginalExtension();
            Storage::disk('public') -> putFileAs(
                $image_path,
                $request -> file('logo_file'),
                $image_name,
            );
        } catch(\Exception $e) {
            $user->forceDelete();
            $business->forceDelete();
            print_r($e->getMessage());
            return response() -> json('Could not upload the image', 500);
        }
        return response()->json([
            'message' => 'Business created successfully. Waiting to be validated by an admin',
            'business' => $business,
            'user' => $user,
        ], 201);
    }

    public function getAllBusinesses() {
        $businesses = Business::all();
        return response() -> json($businesses);
    }

    public function deleteBusiness() {
        $business = Business::find($id);
        $business -> delete();
        return response() -> json('Business deleted successfully');
    }

    public function getSessionBusiness() {
        $id_business = Auth::user() -> id_business;
        if($id_business == null) {
            return response() -> json('No business found');
        } else {
            $business = Business::find($id_business);
            return response() -> json($business);
        }
    }
}