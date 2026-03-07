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
            $tenantId = config('services.teams.tenant_id');
            $clientId = config('services.teams.app_id');
            $domain   = config('services.teams.domain');

            // Step 1: Microsoft public keys fetch karo
            $keysUrl      = "https://login.microsoftonline.com/{$tenantId}/discovery/v2.0/keys";
            $keysResponse = Http::get($keysUrl);
            $jwks         = $keysResponse->json();

            // Step 2: Token decode karo
            $keySet  = JWK::parseKeySet($jwks, 'RS256');
            $decoded = JWT::decode($token, $keySet);

            // Step 3: Validate tenant (tid)
            if ($decoded->tid !== $tenantId) {
                Log::warning('Teams auth: wrong tenant', ['tid' => $decoded->tid]);
                return response()->json(['error' => 'Invalid tenant'], 401);
            }

            // Step 4: Validate audience (aud)
            $expectedAud = "api://{$domain}/{$clientId}";
            if ($decoded->aud !== $clientId && $decoded->aud !== $expectedAud) {
                Log::warning('Teams auth: wrong audience', [
                    'aud'      => $decoded->aud,
                    'expected' => $expectedAud,
                ]);
                return response()->json(['error' => 'Invalid audience'], 401);
            }

            // Step 5: Validate issuer (iss)
            $validIssuers = [
                "https://sts.windows.net/{$tenantId}/",
                "https://login.microsoftonline.com/{$tenantId}/v2.0",
            ];
            if (!in_array($decoded->iss, $validIssuers)) {
                Log::warning('Teams auth: wrong issuer', ['iss' => $decoded->iss]);
                return response()->json(['error' => 'Invalid issuer'], 401);
            }

            // Step 6: User info nikalo
            $oid   = $decoded->oid;
            $email = $decoded->preferred_username ?? $decoded->upn ?? null;
            $name  = $decoded->name ?? 'Unknown';

            // Step 7: Email domain check
            if (!$email || !str_ends_with($email, '@snbllc.org')) {
                Log::warning('Teams auth: rejected domain', ['email' => $email]);
                return response()->json(['error' => 'Unauthorized domain'], 403);
            }

            // Step 8: User find karo — pehle entra_oid se, phir email se
            $user = User::where('entra_oid', $oid)->first();

            if (!$user) {
                // Email se dhundo (existing user without entra_oid)
                $user = User::where('email', $email)->first();

                if ($user) {
                    // entra_oid update karo taake agle baar direct mile
                    $user->update(['entra_oid' => $oid]);
                    Log::info('Teams auth: entra_oid linked to existing user', [
                        'oid'   => $oid,
                        'email' => $email,
                    ]);
                } else {
                    // Bilkul naya user banao
                    $user = User::create([
                        'name'      => $name,
                        'email'     => $email,
                        'entra_oid' => $oid,
                        'role'      => 'Staff',
                        'password'  => bcrypt(str()->random(32)),
                    ]);
                    Log::info('Teams auth: new user created', [
                        'oid'   => $oid,
                        'email' => $email,
                    ]);
                }
            }

            Log::info('Teams auth: success', [
                'oid'   => $oid,
                'email' => $email,
                'ts'    => now(),
            ]);

            // Step 9: Sanctum token return karo
            $sanctumToken = $user->createToken('teams-tab')->plainTextToken;

            return response()->json([
                'status' => 'ok',
                'token'  => $sanctumToken,
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