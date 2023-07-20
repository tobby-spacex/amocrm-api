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
     * @param $validatedFormData form input data.
     *
     * @return bool Returns true if a new customer is created, false otherwise.
     */
    public function createNewContactEntity($validatedFormData)
    {
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
        
        $contactService  = new ContactService();
        $contactsService = $this->apiClient->contacts();
        
        try {
            $contactsCollection = $contactsService->get();
        } catch (\AmoCRM\Exceptions\AmoCRMApiNoContentException $e) {
            // Handle the case where there are no contacts available
            $contactsCollection = collect();
        }

        $checkCustomFields =  $contactService->checkCustomFields($ageKey, $genderKey);

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
                foreach ($contactLeads as $lead) {
                    $leadIds[] = $lead->getId();
                }
        
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

        return false; // No new customer created
    }

    /**
     * Check and create custom fields for age and gender if they do not exist.
     *
     * @param string $ageKey    The custom field code for age.
     * @param string $genderKey The custom field code for gender.
     *
     * @return bool Returns true if both custom fields exist or are created successfully.
     */
    public function checkCustomFields($ageKey, $genderKey)
    {
        $customFields = $this->apiClient->customFields(EntityTypesInterface::CONTACTS);
        $ageField     = $customFields->get()->getBy('code', strtoupper($ageKey));
        $genderField  = $customFields->get()->getBy('code', strtoupper($genderKey));
        
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
    public function checkLeadSuccessStatus($leadId)
    {
        $leadsService = $this->apiClient->leads()->getOne($leadId);

        if($leadsService->getStatusId() === 142) {
            return true;
        }
        
        return false;
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
     * @return json responce.
     */
    public function createNewCustomerByContact($contactsService, $contactId, $contactName)
    {
        $customersService = $this->apiClient->customers();
          
        $customer = new CustomerModel();
        $customer->setName($contactName);
        $customer->setNextDate(strtotime('+2 weeks'));
        
        try {
            $customer = $customersService->addOne($customer);
            $contact = $contactsService->getOne($contactId);
            $contact->setIsMain(false);
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
