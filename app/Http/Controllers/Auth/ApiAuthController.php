<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\EventLogger;
class ApiAuthController extends Controller
{

    /**
     * Get all tokens for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTokens()
    {
        $user = Auth::user();
      //  $tokens = $user->tokens; // or PersonalAccessToken::where('tokenable_id', $user->id)->get();
        $tokens = $user->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'abilities' => $token->abilities,
                'plain_text_token' => $token->token, // Ensure this is available for demonstration, though not typically stored.
            ];
        });


        return response()->json(['tokens' => $tokens], 200);
    }

    /**
     * Revoke a specific token.
     *
     * @param  string  $tokenId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeToken($tokenId)
    {
        $user = Auth::user();
        $token = $user->tokens()->find($tokenId);

        if ($token) {
            $token->delete();
            return response()->json(['message' => 'Token revoked successfully.'], 200);
        }

        return response()->json(['message' => 'Token not found.'], 404);
    }

    /**
     * Revoke all tokens for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeAllTokens()
    {
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked successfully.'], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {

            $user = Auth::user()->load('roles');

            $token = $user->createToken('API Token')->plainTextToken;

            EventLogger::log($user, 'login', 'API Auth Token Created.', ['ip' => $request->ip(), 'token' => $token]);


            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        EventLogger::log($request->user(), 'login', 'User Logged Out.', ['ip' => $request->ip()]);

        return response()->json(['message' => 'Logout successful'], 200);
    }
}
