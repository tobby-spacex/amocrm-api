<?php

namespace App\Services;

use App\Traits\LeadTrait;
use App\Helper\AmoCrmHelper;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFields\TextCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;

class ContactService
{
    use LeadTrait;
    
    /**
     * The AMOCRM API client instance.
     *
     * @var \AmoCRM\Client\AmoCRMApiClient
     */
    private $apiClient;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->apiClient = AmoCrmHelper::createApiClient();
    }
    
    /**
     * Check the contact phone number and create a new customer if conditions are met.
     *
     * @param array $validatedFormData form input data.
     *
     *  @return \Illuminate\Http\JsonResponse
     */
    public function createNewContactEntity(array $validatedFormData): \Illuminate\Http\JsonResponse
    {

        $contactsService = $this->apiClient->contacts();
        
        try {
            $contactsCollection = $contactsService->get();
        } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
            // Handle the case where there are no contacts available
            $contactsCollection = [];
        }

        $checkCustomFields =  $this->checkCustomFields();

        $contactId = null;
        $phoneExists = false;
        $hasSuccessLead = false;
        $phoneField = null;
        
        foreach ($contactsCollection as $contact) {            
            if(!empty($contact->getCustomFieldsValues())) {
                $phoneField = $contact->getCustomFieldsValues()->getBy('fieldCode', 'PHONE');
            }
            
            if ($phoneField !== null) {
                $phoneValues = $phoneField->getValues();
                
                if (!empty($phoneValues)) {
                    $phone = $phoneValues[0]->getValue();
                    
                    if ($phone === $validatedFormData['phone']) {
                        $phoneExists = true;
                        $contactId   = $contact->getId();
                        break;
                    }
                }
            }
        }
        
        if ($phoneExists) {
            $contact = $this->apiClient->contacts()->getOne($contactId, [ContactModel::LEADS]);
            $contactLeads = $contact->getLeads();

            if(!empty($contactLeads)) {

                $leadIds = $contactLeads->pluck('id');

                foreach($leadIds as $leadId) {
                    if ($this->checkLeadSuccessStatus($leadId)) {
                        $hasSuccessLead = true;
                        break;
                    }
                }
    
            } else {
                  // Use lead create trait
                  $this->createNewLead($this->apiClient, $contactId);
                  return response()->json(['message' => 'Данному контакту было добавлено новая сделка.']); 
            }

            if($hasSuccessLead) {

                try {

                    return $this->createNewCustomerByContact($contactsService, $contactId, $contact->name);
                } catch (\Exception $e) {

                    return $e->getMessage();
                }

            } else {

                return response()->json(['message' => 'Контакт с таким номером уже существует']); 
            }
        } else {
            try {
                $contact = new ContactModel();
                $contact->setFirstName($validatedFormData['first_name']);
                $contact->setLastName($validatedFormData['second_name']);
     
                $customFields = $this->apiClient->customFields(EntityTypesInterface::CONTACTS)->get();
    
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
    
                $contactModel = $this->apiClient->contacts()->addOne($contact);
                
                $contact = $this->apiClient->contacts()->getOne($contactModel->getId());
      
                // Use lead create trait
                $this->createNewLead($this->apiClient, $contactModel->getId());
    
                return response()->json(['message' => 'Новый контакт со сделкой был создан.']); 
            } catch (AmoCRMApiException $e) {
                // Handle exceptions
                dd($e);
                die;
            }
        }

        return response()->json(['message' => 'Контакт не был создан создан.']); // No new customer created
    }

    /**
     * Check and create custom fields for age and gender if they do not exist.
     *
     * @return bool Returns true if both custom fields exist or are created successfully.
     */
    public function checkCustomFields(): bool
    {
        $customFields = $this->apiClient->customFields(EntityTypesInterface::CONTACTS);
        $ageField     = $customFields->get()->getBy('code', strtoupper('AGE'));
        $genderField  = $customFields->get()->getBy('code', strtoupper('GENDER'));
        
        if ($ageField === null) {
            $ageField = (new TextCustomFieldModel())
                ->setCode('AGE')
                ->setName('Возраст')
                ->setEntityType(EntityTypesInterface::CONTACTS);
            $customFields->addOne($ageField);
        }

        if ($genderField === null) {
        $genderField = (new TextCustomFieldModel())
            ->setCode('GENDER')
            ->setName('Пол')
            ->setEntityType(EntityTypesInterface::CONTACTS);
        $customFields->addOne($genderField);
        }

        return ($ageField !== null && $genderField !== null);
    }

    /**
     * Check the success status of a lead.
     *
     * @param int $leadId The ID of the lead to check.
     *
     * @return bool Returns true if the lead has a success status, false otherwise.
     */
    public function checkLeadSuccessStatus(int $leadId): bool
    {
        $lead = $this->apiClient->leads()->getOne($leadId);

        return $lead->getStatusId() === 142;
    }

    /**
     * Create new customer based on contact details
     *
     * @param mixed $contactsService $apiClient->contacts();
     *
     * @param int  $contactId contacnt id.
     * 
     * @param string $contactName contact name.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function createNewCustomerByContact(\AmoCRM\EntitiesServices\Contacts $contactsService, int $contactId, string $contactName): \Illuminate\Http\JsonResponse
    {
        $customersService = $this->apiClient->customers();
          
        $customer = new CustomerModel();
        $customer->setName($contactName);
        $customer->setNextDate(strtotime('+2 weeks'));
        
        try {
            $customer = $customersService->addOne($customer);
            $contact = $contactsService->getOne($contactId);
            $links = new LinksCollection();
            $links->add($contact);
            $customersService->link($customer, $links);

            return response()->json(['message' => 'Покупатель с данным контактом был создан.']); 
            
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
    }
}
