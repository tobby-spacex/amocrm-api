<?php

namespace App\Helper;

use Illuminate\Support\Facades\Cache;

class AmoCrmHelper
{
    public static function createApiClient()
    {
        $accessToken  = Cache::get('access_token');
        $refreshToken = Cache::get('refresh_token');
        $expires      = Cache::get('expires');

        if(empty($accessToken) || empty($refreshToken) || empty($expires)) {

            return response()->json(['message' => 'Something went wrong with token validation']);
        }

        $apiClient = new \AmoCRM\Client\AmoCRMApiClient(env('AMO_CLIENT_ID'), env('AMO_CLIENT_SECRET'), env('AMO_REDIRECT_URL'));

        $accessTokenObject = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires' => $expires,
            'baseDomain' => 'afayziev.amocrm.ru',
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
