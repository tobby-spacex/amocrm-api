<?php

namespace App\Http\Controllers;

use App\Traits\LeadTrait;
use App\Helper\AmoCrmHelper;
use Illuminate\Http\Request;
use AmoCRM\Models\ContactModel;
use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Support\Facades\Cache;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\ContactsCollection;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;

class EntityCreateController extends Controller
{
    use LeadTrait;
    
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
            'phone'       => 'required|numeric',
            'email'       => ['required', 'email', 'max:255'],
            'age'         => 'required|numeric',
        ]);

        $apiClient = AmoCrmHelper::createApiClient();

        try {
            $contact = new ContactModel();
            $contact->setFirstName($validatedFormData['first_name']);
            $contact->setLastName($validatedFormData['second_name']);
 
            $customFields = $apiClient->customFields(EntityTypesInterface::CONTACTS)->get();

            $customFieldsValues = new CustomFieldsValuesCollection();

            $phoneField = $customFields->getBy('code', 'PHONE');
            $emailField = $customFields->getBy('code', 'EMAIL');
            
            if ($phoneField !== null) {
                $phoneValue = (new MultitextCustomFieldValuesModel())
                    ->setFieldCode($phoneField->getCode())
                    ->setValues(
                        (new MultitextCustomFieldValueCollection())
                            ->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum('WORK')
                                    ->setValue($validatedFormData['phone'])
                            )
                    );
                $customFieldsValues->add($phoneValue);
            }
            
            if ($emailField !== null) {
                $emailValue = (new MultitextCustomFieldValuesModel())
                    ->setFieldCode($emailField->getCode())
                    ->setValues(
                        (new MultitextCustomFieldValueCollection())
                            ->add(
                                (new MultitextCustomFieldValueModel())
                                    ->setEnum('WORK')
                                    ->setValue($validatedFormData['email'])
                            )
                    );
                $customFieldsValues->add($emailValue);
            }

            $contact->setCustomFieldsValues($customFieldsValues);

            $contactModel = $apiClient->contacts()->addOne($contact);
            
            $contact = $apiClient->contacts()->getOne($contactModel->getId());
  
            // Use lead create trait
            $this->createNewLead($apiClient, $contactModel->getId());

        } catch (AmoCRMApiException $e) {
            // Handle exceptions
            printError($e);
            die;
        }

        
        session()->flash('success', 'New Warehouse created.');

        return redirect()->back();
    }
    
    public function createEntity()
    {
        $clientId     = 'f7ac7b4a-b70b-44d5-8659-5e10b3209dc5';
        $clientSecret = 'BSCMvnX0MZJBQ1FT3vKWNt12gDlrUcH34iTmCgbaTnibKDElwW0TmlFoOSqScuxH';
        $redirectUri  = 'https://f197-94-158-52-23.ngrok-free.app/auth-callback';
        
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
