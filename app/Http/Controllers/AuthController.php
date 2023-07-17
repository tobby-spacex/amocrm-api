<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
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
     * Authenticate the user and obtain access token.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authUser()
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
                
                return redirect()->away($authorizationUrl);
                die;
            }
        } elseif (!isset($_GET['from_widget']) && (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state']))) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        }
    }

    /**
     * Handle the callback after user authorization and obtain access token.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     * @throws \Exception
     */
    public function authCallback(Request $request)
    {
        $code = $request->query('code');

        $link = 'https://' . $this->amoSubdomain . '.amocrm.ru/oauth2/access_token';

        /** Соберем данные для запроса */
        $data = [
            'client_id' => $this->amoClientId,
            'client_secret' => $this->amoClientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->amoRedirectUri,
        ];

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

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

        $response = json_decode($out, true);

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $token_type = $response['token_type'];
        $expires_in = $response['expires_in'];

        Cache::put('access_token', $access_token, $expires_in);
        Cache::put('refresh_token', $refresh_token);
        Cache::put('expires', $expires_in);
    }
}
