<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Auth;
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
            $tenantId = config('services.teams.tenant_id');
            $clientId = config('services.teams.app_id');
            $domain   = config('services.teams.domain');

            // Step 1: Microsoft public keys
            $keysUrl      = "https://login.microsoftonline.com/{$tenantId}/discovery/v2.0/keys";
            $keysResponse = Http::get($keysUrl);
            $jwks         = $keysResponse->json();

            // Step 2: Token decode
            $keySet  = JWK::parseKeySet($jwks, 'RS256');
            $decoded = JWT::decode($token, $keySet);

            // Step 3: Validate tenant
            if ($decoded->tid !== $tenantId) {
                Log::warning('Teams auth: wrong tenant', ['tid' => $decoded->tid]);
                return response()->json(['error' => 'Invalid tenant'], 401);
            }

            // Step 4: Validate audience
            $expectedAud = "api://{$domain}/{$clientId}";
            if ($decoded->aud !== $clientId && $decoded->aud !== $expectedAud) {
                Log::warning('Teams auth: wrong audience', ['aud' => $decoded->aud]);
                return response()->json(['error' => 'Invalid audience'], 401);
            }

            // Step 5: Validate issuer
            $validIssuers = [
                "https://sts.windows.net/{$tenantId}/",
                "https://login.microsoftonline.com/{$tenantId}/v2.0",
            ];
            if (!in_array($decoded->iss, $validIssuers)) {
                Log::warning('Teams auth: wrong issuer', ['iss' => $decoded->iss]);
                return response()->json(['error' => 'Invalid issuer'], 401);
            }

            // Step 6: Extract user info
            $oid   = $decoded->oid;
            $email = $decoded->preferred_username ?? $decoded->upn ?? null;
            $name  = $decoded->name ?? 'Unknown';

            // Step 7: Domain check
            if (!$email || !str_ends_with($email, '@snbllc.org')) {
                Log::warning('Teams auth: rejected domain', ['email' => $email]);
                return response()->json(['error' => 'Unauthorized domain'], 403);
            }

            // Step 8: Find or link user
            $user = User::where('entra_oid', $oid)->first();

            if (!$user) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update(['entra_oid' => $oid]);
                } else {
                    $user = User::create([
                        'name'      => $name,
                        'email'     => $email,
                        'entra_oid' => $oid,
                        'role'      => 'Staff',
                        'password'  => bcrypt(str()->random(32)),
                    ]);
                }
            }

            Log::info('Teams auth: success', ['email' => $email]);

            // ✅ Step 9: Log into Laravel SESSION (not Sanctum token)
            // This sets the session cookie so the iframe "/" loads authenticated
            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return response()->json([
                'status' => 'ok',
                'user'   => [
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Teams auth: exception', ['message' => $e->getMessage()]);
            return response()->json([
                'error'  => 'Token validation failed',
                'detail' => $e->getMessage(),
            ], 401);
        }
    }
}