<?php

namespace App\Http\Controllers;

use AmoCRM\Models\LeadModel;
use App\Helper\AmoCrmHelper;
use Illuminate\Http\Request;
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

    /**
     * Display the form for creating a new entity.
     *
     * @return \Illuminate\View\View
     */
    public function create() {
        return view('entities.create');
    }

    public function store(Request $request)
    {
        $validatedFormData = $request->validate([
            'first_name'  => 'required|string|max:155',
            'second_name' => 'required|string|max:255',
            'address'     => 'required|string|max:155',
            'phone'       => 'required|numeric',
            'email'       => ['required', 'email', 'max:255'],
            'age'         => 'required|numeric',
        ]);

        $apiClient = AmoCrmHelper::createApiClient();

        try {
            $contactsService = $apiClient->contacts();
            
            $contactModel = new ContactModel();
            $contactModel->setFirstName($request->input('first_name'));
            $contactModel->setLastName($request->input('second_name'));
        
            $contactsCollection = new ContactsCollection();
            $contactsCollection->add($contactModel);
        
            $contactsService->add($contactsCollection);
        } catch (\Throwable $th) {
            //throw $th;
        }

        session()->flash('success', 'New Warehouse created.');

        return redirect()->back();
    }
    
    public function createEntity()
    {
        $clientId = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
        $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
        $redirectUri = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';
        
        $accessToken = Cache::get('access_token');
        $refresh_token = Cache::get('refresh_token');
        $expires = Cache::get('expires');
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
                $contactsService = $apiClient->contacts();
            
                $contactModel = new ContactModel();
                $contactModel->setFirstName('Buso');
                $contactModel->setLastName('Sommit');
            
                $contactsCollection = new ContactsCollection();
                $contactsCollection->add($contactModel);
            
                $contactsService->add($contactsCollection);
            
            } catch (AmoCRMApiException $e) {
                // Обрабатываем исключение API
                echo $e->getMessage();
            }
      
    }
}
