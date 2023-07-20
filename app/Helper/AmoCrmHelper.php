<?php

namespace App\Helper;

use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Support\Facades\Cache;

class AmoCrmHelper
{
    /**
     * Create and configure an instance of the AmoCRMApiClient for API communication.
     *
     * @return \AmoCRM\Client\AmoCRMApiClient|null The configured API client instance, or null if the token validation fails.
     *
     * @throws \Exception If the required access token, refresh token, or expiration time is missing.
     */
    public static function createApiClient(): AmoCRMApiClient
    {
        $accessToken  = Cache::get('access_token');
        $refreshToken = Cache::get('refresh_token');
        $expires      = Cache::get('expires');
        $baseDomain   = Cache::get('baseDomain');

        if(empty($accessToken) || empty($refreshToken) || empty($expires)) {

            return response()->json(['message' => 'Something went wrong with token validation']);
        }

        $apiClient = new \AmoCRM\Client\AmoCRMApiClient(env('AMO_CLIENT_ID'), env('AMO_CLIENT_SECRET'), env('AMO_REDIRECT_URL'));

        $accessTokenObject = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires' => $expires,
            'baseDomain' => $baseDomain,
        ]);

        $apiClient->setAccessToken($accessTokenObject)
            ->setAccountBaseDomain($accessTokenObject->getValues()['baseDomain'])
            ->onAccessTokenRefresh(function (\League\OAuth2\Client\Token\AccessTokenInterface $accessTokenObject, string $baseDomain) {
                // Save the refreshed token
                saveToken([
                    'accessToken' => $accessTokenObject->getToken(),
                    'refreshToken' => $accessTokenObject->getRefreshToken(),
                    'expires' => $accessTokenObject->getExpires(),
                    'baseDomain' => $baseDomain,
                ]);
            });

        return $apiClient;
    }
}
