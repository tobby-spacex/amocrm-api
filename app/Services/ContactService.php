<?php

namespace App\Services;

use App\Helper\AmoCrmHelper;
use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Models\CustomFields\TextCustomFieldModel;

class ContactService
{
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
     * @param \AmoCRM\Client\AmoCRMApiClient $apiClient The AMOCRM API client.
     * @param string $inputPhone The phone number to check.
     *
     * @return bool Returns true if a new customer is created, false otherwise.
     */
    public function checkContactPhoneNumber($apiClient, $inputPhone)
    {
        $contactsService    = $apiClient->contacts();
        $contactsCollection = $contactsService->get();
        
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
                    
                    if ($phone === $inputPhone) {
                        $phoneExists = true;
                        $contactId   = $contact->getId();
                        break;
                    }
                }
            }
        }
        
        if ($phoneExists) {
            $contact = $apiClient->contacts()->getOne($contactId, [ContactModel::LEADS]);
            $contactLeads = $contact->getLeads();

            foreach ($contactLeads as $lead) {
                $leadIds[] = $lead->getId();
            }
    
            foreach($leadIds as $leadId) {
                if ($this->checkLeadSuccessStatus($leadId)) {
                    $hasSuccessLead = true;
                    break;
                }
            }

            if($hasSuccessLead) {
                $customersService = $apiClient->customers();
          
                $customer = new CustomerModel();
                $customer->setName($contact->name);
                $customer->setNextDate(strtotime('+2 weeks'));
                
                try {
                    $customer = $customersService->addOne($customer);
                    return true; 
                } catch (AmoCRMApiException $e) {
                    printError($e);
                    die;
                }
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
}
