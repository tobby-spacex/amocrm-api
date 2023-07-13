<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    public function test()
    {
        $token = [];
        $token['access_token'] = Cache::get('access_token');
        $token['refresh_token'] = Cache::get('refresh_token');

        return env('AMO_CLIENT_ID');
    }

    public function test2()
    {
        $subdomain = 'afayziev';
        $link = 'https://' . $subdomain . '.amocrm.ru/api/v4/leads'; 
        /** Получаем access_token из вашего хранилища */
        $access_token = Cache::get('access_token');
        /** Формируем заголовки */
        $headers = [
            'Authorization: Bearer ' . $access_token
        ];
        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
         */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
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
        } catch(\Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        return json_decode($out, true);
    }

    public function test3()
    {
        $name = 'Имя клиента123';
        $phone = '+380123456789';
        $email = 'email@gmail.com';
        $target = 'Цель';
        $company = 'Название компании';

        $custom_field_id = 454021;
        $custom_field_value = 'тест';

        $ip = '1.2.3.4';
        $domain = 'site.ua';
        $price = 0;
        $pipeline_id = 7007090;
        $user_amo = 0;

        $utm_source   = '';
        $utm_content  = '';
        $utm_medium   = '';
        $utm_campaign = '';
        $utm_term     = '';
        $utm_referrer = '';

$data = [
    [
        "name" => $phone,
        "price" => $price,
        "responsible_user_id" => (int) $user_amo,
        "pipeline_id" => (int) $pipeline_id,
        "_embedded" => [
            "metadata" => [
                "category" => "forms",
                "form_id" => 1,
                "form_name" => "Форма на сайте",
                "form_page" => $target,
                "form_sent_at" => strtotime(date("Y-m-d H:i:s")),
                "ip" => $ip,
                "referer" => $domain
            ],
            "contacts" => [
                [
                    "first_name" => $name,
                ]
            ],
            "companies" => [
                [
                    "name" => $company
                ]
            ]
        ],
    ]
];

    $access_token = Cache::get('access_token');

        $method = "/api/v4/leads/complex";

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token,
        ];
        $subdomain = 'afayziev';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($curl, CURLOPT_URL, "https://$subdomain.amocrm.ru".$method);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_COOKIEFILE, 'amo/cookie.txt');
        curl_setopt($curl, CURLOPT_COOKIEJAR, 'amo/cookie.txt');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $code = (int) $code;
        $errors = [
            301 => 'Moved permanently.',
            400 => 'Wrong structure of the array of transmitted data, or invalid identifiers of custom fields.',
            401 => 'Not Authorized. There is no account information on the server. You need to make a request to another server on the transmitted IP.',
            403 => 'The account is blocked, for repeatedly exceeding the number of requests per second.',
            404 => 'Not found.',
            500 => 'Internal server error.',
            502 => 'Bad gateway.',
            503 => 'Service unavailable.'
        ];

        if ($code < 200 || $code > 204) die( "Error $code. " . (isset($errors[$code]) ? $errors[$code] : 'Undefined error') );


        $Response = json_decode($out, true);
        // $Response = $Response['_embedded']['items'];
        // $output = 'ID добавленных элементов списков:' . PHP_EOL;
        // foreach ($Response as $v)
        //     if (is_array($v))
        //         $output .= $v['id'] . PHP_EOL;
        // return $output;
    }
}
