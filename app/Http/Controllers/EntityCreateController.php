<?php

namespace App\Http\Controllers;

use App\Traits\LeadTrait;
use App\Helper\AmoCrmHelper;
use Illuminate\Http\Request;
use AmoCRM\Models\ContactModel;
use App\Services\ContactService;
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
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

    /**
     * Entity creating with the given params
     *
     * @param AmoCRM\Client\AmoCRMApiClient $apiClient  The AmoCRM API client.
     * @param Request $request
     * @return
     */
    public function store(Request $request)
    {
        $validatedFormData = $request->validate([
            'first_name'  => 'required|string|max:155',
            'second_name' => 'required|string|max:255',
            'phone'       => 'required|string',
            'email'       => ['required', 'email', 'max:255'],
            'age'         => 'required|numeric',
            'gender'      => 'required'  
        ]);

        $ageKey = null;
        $genderKey = null;
        foreach ($validatedFormData as $key => $value) {
            if ($key === 'age') {
                $ageKey = $key;
            }
            if ($key === 'gender') {
                $genderKey = $key;
                break;
            }
        }

        $apiClient = AmoCrmHelper::createApiClient();


        $contactService = new ContactService();
        $newCustomerCreated = $contactService->checkContactPhoneNumber($apiClient,  $validatedFormData['phone']);
        $checkCustomFields =  $contactService->checkCustomFields($ageKey, $genderKey);

        if ($newCustomerCreated) {
            return back()->with('message', 'New customer created successfully');
        }

        try {
            $contact = new ContactModel();
            $contact->setFirstName($validatedFormData['first_name']);
            $contact->setLastName($validatedFormData['second_name']);
 
            $customFields = $apiClient->customFields(EntityTypesInterface::CONTACTS)->get();

            $customFieldsValues = new CustomFieldsValuesCollection();

            $phoneField = $customFields->getBy('code', 'PHONE');
            $emailField = $customFields->getBy('code', 'EMAIL');
            $ageField = $customFields->getBy('code', 'AGE');
            $genderField = $customFields->getBy('code', 'GENDER');

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

            if($checkCustomFields) {
                $ageValue = (new TextCustomFieldValuesModel())
                    ->setFieldCode($ageField->getCode())
                    ->setValues(
                        (new TextCustomFieldValueCollection())
                            ->add(
                                (new TextCustomFieldValueModel())
                                    ->setValue($validatedFormData['age'])
                            )
                    );
                $customFieldsValues->add($ageValue);
    
                $genderValue = (new TextCustomFieldValuesModel())
                    ->setFieldCode($genderField->getCode())
                    ->setValues(
                        (new TextCustomFieldValueCollection())
                            ->add(
                                (new TextCustomFieldValueModel())
                                    ->setValue($validatedFormData['gender'])
                            )
                    );
                $customFieldsValues->add($genderValue);
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

        
        return back()->with('message', 'New contact with the deal successfully created');
    }
}
