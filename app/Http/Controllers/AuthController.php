<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
session_start();

class AuthController extends Controller
{
    public function authUser()
    {
        $clientId = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
        $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
        // $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app'; 
        $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';

        $apiClient = new \AmoCRM\Client\AmoCRMApiClient($clientId, $clientSecret, $redirectUri);


    if (isset($_GET['referer'])) {
        $apiClient->setAccountBaseDomain($_GET['referer']);
    }

    if (!isset($_GET['code'])) {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2state'] = $state;
        if (isset($_GET['button'])) {
            echo $apiClient->getOAuthClient()->getOAuthButton(
                [
                    'title' => 'Установить интеграцию',
                    'compact' => true,
                    'class_name' => 'className',
                    'color' => 'default',
                    'error_callback' => 'handleOauthError',
                    'state' => $state,
                ]
            );
            die;
        } else {
            $authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
                'state' => $state,
                'mode' => 'post_message',
            ]);
            header('Location: ' . $authorizationUrl);
            die;
        }
    } elseif (!isset($_GET['from_widget']) && (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state']))) {
        unset($_SESSION['oauth2state']);
        exit('Invalid state');
    }

    /**
     * Ловим обратный код
     */
    try {
        $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);
        
        if (!$accessToken->hasExpired()) {
            saveToken([
                'accessToken' => $accessToken->getToken(),
                'refreshToken' => $accessToken->getRefreshToken(),
                'expires' => $accessToken->getExpires(),
                'baseDomain' => $apiClient->getAccountBaseDomain(),
            ]);
        }
    } catch (\Exception $e) {
        die((string)$e);
    }

        $ownerDetails = $apiClient->getOAuthClient()->getResourceOwner($accessToken);

        printf('Hello, %s!', $ownerDetails->getName());

    }


    // public function authCallback(Request $request)
    // {
    //     $clientId = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
    //     $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
    //     $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';

    //     $apiClient = new \AmoCRM\Client\AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

    //     $code = $request->query('code');

    //     try {
    //         $accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($code);

    //         if (!$accessToken->hasExpired()) {
    //             saveToken([
    //                 'accessToken' => $accessToken->getToken(),
    //                 'refreshToken' => $accessToken->getRefreshToken(),
    //                 'expires' => $accessToken->getExpires(),
    //                 'baseDomain' => $apiClient->getAccountBaseDomain(),
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         return $e->getMessage();
    //     }

    //     $ownerDetails = $apiClient->getOAuthClient()->getResourceOwner($accessToken);

    //     printf('Hello, %s!', $ownerDetails->getName());
    // }

    public function authCallback(Request $request)
    {
        $clientId = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
        $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
        $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';

        // $apiClient = new \AmoCRM\Client\AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

        $code = $request->query('code');

        $subdomain = 'afayziev';
        $link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token';

        /** Соберем данные для запроса */
        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(\Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $response = json_decode($out, true);

        $access_token = $response['access_token']; //Access токен
        $refresh_token = $response['refresh_token']; //Refresh токен
        $token_type = $response['token_type']; //Тип токена
        $expires_in = $response['expires_in'];

        Cache::put('access_token', $access_token, $expires_in);
        Cache::put('refresh_token', $refresh_token);
        Cache::put('time', $expires_in);
    }
}
