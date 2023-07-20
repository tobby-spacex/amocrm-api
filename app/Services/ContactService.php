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
     *  @return array
     */
    public function createNewContactEntity(array $validatedFormData): array
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
        $phoneField = null;
        
        foreach ($contactsCollection as $contact) {            
            if(!empty($contact->getCustomFieldsValues())) {
                $phoneField = $contact->getCustomFieldsValues()->getBy('fieldCode', 'PHONE');
            }
            
            if ($phoneField !== null) {
                $phoneValues = $phoneField->getValues();
                $phoneValue = $phoneValues->pluck('value');

                if (reset($phoneValue) === $validatedFormData['phone']) {
                    $phoneExists = true;
                    $contactId   = $contact->getId();
                    break;
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
    
                        try {

                            return $this->createNewCustomerByContact($contactId, $contact->name);
                        } catch (\Exception $e) {
        
                            dd($e->getMessage());
                        }
                    } else {

                        return ['message' => 'Контакт с таким номером уже существует']; 
                    }
                }
    
            } else {
                    // Use lead create trait
                  $this->createNewLead($this->apiClient, $contactId);
                  return ['message' => 'Данному контакту было добавлено новая сделка.']; 
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
    
                return ['message' => 'Новый контакт со сделкой был создан.'];
                
            } catch (AmoCRMApiException $e) {
                // Handle exceptions
                dd($e);
                die;
            }
        }

        return ['message' => 'Контакт не был создан создан.']; // No new customer created
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
     * @param int  $contactId contact id.
     * 
     * @param string $contactName contact name.
     * 
     * @return array 
     */
    public function createNewCustomerByContact(int $contactId, string $contactName): array
    {
        $customersService = $this->apiClient->customers();
        
        $customer = new CustomerModel();
        $customer->setName($contactName);
        $customer->setNextDate(strtotime('+2 weeks'));
        
        try {
            $customer = $this->apiClient->customers()->addOne($customer);
            $contact = $this->apiClient->contacts()->getOne($contactId);
            $links = new LinksCollection();
            $links->add($contact);
            $customersService->link($customer, $links);

            return ['message' => 'Покупатель с данным контактом был создан.']; 
            
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
    }
}
