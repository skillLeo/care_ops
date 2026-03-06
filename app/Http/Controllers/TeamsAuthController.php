<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TeamsAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json(['error' => 'Token missing'], 400);
        }

        try {
            // Step 1: Microsoft ke public keys fetch karo
            $tenantId = config('services.teams.tenant_id');
            $clientId = config('services.teams.app_id');

            $keysUrl = "https://login.microsoftonline.com/{$tenantId}/discovery/v2.0/keys";
            $keysResponse = Http::get($keysUrl);
            $jwks = $keysResponse->json();

            // Step 2: Token decode karo
            $decoded = JWT::decode($token, JWK::parseKeySet($jwks));

            // Step 3: Validate karo
            if ($decoded->tid !== $tenantId) {
                Log::warning('Teams auth: wrong tenant', ['tid' => $decoded->tid]);
                return response()->json(['error' => 'Invalid tenant'], 401);
            }

            if ($decoded->aud !== $clientId) {
                Log::warning('Teams auth: wrong audience', ['aud' => $decoded->aud]);
                return response()->json(['error' => 'Invalid audience'], 401);
            }

            // Step 4: User info nikalo
            $oid   = $decoded->oid;
            $email = $decoded->preferred_username ?? $decoded->upn ?? null;
            $name  = $decoded->name ?? 'Unknown';

            // Step 5: Email domain check karo
            if (!$email || !str_ends_with($email, '@snbllc.org')) {
                Log::warning('Teams auth: rejected domain', ['email' => $email]);
                return response()->json(['error' => 'Unauthorized domain'], 403);
            }

            // Step 6: User find karo ya banao
            $user = User::where('entra_oid', $oid)->first();

            if (!$user) {
                $user = User::create([
                    'name'      => $name,
                    'email'     => $email,
                    'entra_oid' => $oid,
                    'role'      => 'Staff',
                    'password'  => bcrypt(str()->random(32)),
                ]);
                Log::info('Teams auth: new user created', ['oid' => $oid, 'email' => $email]);
            }

            Log::info('Teams auth: success', ['oid' => $oid, 'email' => $email]);

            // Step 7: Token return karo
            $sanctumToken = $user->createToken('teams-tab')->plainTextToken;

            return response()->json([
                'status' => 'ok',
                'token'  => $sanctumToken,
                'user'   => [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Teams auth: exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token validation failed', 'detail' => $e->getMessage()], 401);
        }
    }
}