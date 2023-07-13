<?php

namespace App\Http\Controllers;

use AmoCRM\Models\LeadModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Filters\ContactsFilter;
use Illuminate\Support\Facades\Cache;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\ContactsCollection;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Collections\Leads\LeadsCollection;

class EntityCreateController extends Controller
{
    public function createEntity()
    {
        $clientId = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
        $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
        $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';
        
        $accessToken = Cache::get('access_token');
        $refresh_token = Cache::get('refresh_token');
        $expires = Cache::get('time');
        $apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);

        $accessTokenObject = new AccessToken([
            'access_token' => $accessToken,
            'refreshToken' => $refresh_token,
            'expires' => $expires,
            'baseDomain' => 'afayziev.amocrm.ru'
        ]);
        
        $apiClient->setAccessToken($accessTokenObject)
        ->setAccountBaseDomain($accessTokenObject->getValues()['baseDomain'])
        ->onAccessTokenRefresh(
            function (\League\OAuth2\Client\Token\AccessTokenInterface $accessTokenObject, string $baseDomain) {
                saveToken(
                    [
                        'accessToken' => $accessTokenObject->getToken(),
                        'refreshToken' => $accessTokenObject->getRefreshToken(),
                        'expires' => $accessTokenObject->getExpires(),
                        'baseDomain' =>  $baseDomain,
                    ]
                );
            });


            try {
                // Получаем сервис для работы с контактами
                $contactsService = $apiClient->contacts();
            
                // Создаем экземпляр модели контакта
                $contactModel = new ContactModel();
                $contactModel->setFirstName('Peter');
                $contactModel->setLastName('Bush');
            
                // Создаем коллекцию контактов
                $contactsCollection = new ContactsCollection();
                $contactsCollection->add($contactModel);
            
                // Создаем контакты на сервере
                $contactsService->add($contactsCollection);
            
                // Получаем идентификаторы созданных контактов
                // $createdContactIds = $contactsCollection->getIds();
            
                // // Обрабатываем созданные контакты
                // foreach ($createdContactIds as $contactId) {
                //     // Обработка созданных контактов
                //     echo "Создан контакт с ID: $contactId<br>";
                // }
            } catch (AmoCRMApiException $e) {
                // Обрабатываем исключение API
                echo $e->getMessage();
            }
      
    }
}
