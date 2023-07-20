<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class Autherization
{
    protected $amoClientId;
    protected $amoClientSecret;
    protected $amoRedirectUri;
    protected $amoSubdomain;

    public function __construct()
    {
        $this->amoClientId     = env('AMO_CLIENT_ID');
        $this->amoClientSecret = env('AMO_CLIENT_SECRET');
        $this->amoRedirectUri  = env('AMO_REDIRECT_URL');
        $this->amoSubdomain    = env('AMO_SUBDOMAIN');
    }

    /**
     * Authenticate and get user code
     */
    public function authorization(): void
    {
        $apiClient = new \AmoCRM\Client\AmoCRMApiClient(
            $this->amoClientId,
            $this->amoClientSecret,
            $this->amoRedirectUri
        );

        if (isset($_GET['referer'])) {
            $apiClient->setAccountBaseDomain($_GET['referer']);
        }

        if (!isset($_GET['code'])) {
            if (isset($_GET['button'])) {
                echo $apiClient->getOAuthClient()->getOAuthButton(
                    [
                        'title' => 'Установить интеграцию',
                        'compact' => true,
                        'class_name' => 'className',
                        'color' => 'default',
                        'error_callback' => 'handleOauthError',
                    ]
                );
                die;
            } else {
                $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
                    'mode' => 'post_message',
                ]);
                header('Location: ' . $authorizationUrl);
                die;
            }
        }

        /**
         * Ловим обратный код
         */
        try {
            $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

            if (!$accessToken->hasExpired()) {
                $this->saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $apiClient->getAccountBaseDomain(),
                ]);
            }
        } catch (\Exception $e) {
            die((string)$e);
        }
    }


    /**
     * Save token in cache
     */
    public function saveToken(array $accessToken): void
    {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            Cache::put('access_token', $accessToken['accessToken'], $accessToken['expires']);
            Cache::put('refresh_token', $accessToken['refreshToken']);
            Cache::put('expires', $accessToken['expires']);
            Cache::put('baseDomain', $accessToken['baseDomain']);
        }
    }
}