<?php

namespace App\Services;

use App\Helper\AmoCrmHelper;
use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFields\TextCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;

class ContactService
{
    public function addContact($apiClient, $validatedFormData)
    {
        $contact = new ContactModel();
        $contact->setFirstName($validatedFormData['first_name']);
        $contact->setLastName($validatedFormData['second_name']);

        $customFieldsValues = new CustomFieldsValuesCollection();

        $phoneField = $apiClient->customFields(EntityTypesInterface::CONTACTS)->getBy('code', 'PHONE');
        $emailField = $apiClient->customFields(EntityTypesInterface::CONTACTS)->getBy('code', 'EMAIL');

        if ($phoneField !== null) {
            $this->addCustomFieldValue($customFieldsValues, $phoneField->getCode(), $validatedFormData['phone']);
        }

        if ($emailField !== null) {
            $this->addCustomFieldValue($customFieldsValues, $emailField->getCode(), $validatedFormData['email']);
        }

        $contact->setCustomFieldsValues($customFieldsValues);

        return $apiClient->contacts()->addOne($contact);
    }

    public function addCustomFieldValue($fieldCode, $value)
    {
        $customFieldsValues = new CustomFieldsValuesCollection();

        $fieldValue = (new MultitextCustomFieldValuesModel())
            ->setFieldCode($fieldCode)
            ->setValues(
                (new MultitextCustomFieldValueCollection())
                    ->add(
                        (new MultitextCustomFieldValueModel())
                            ->setEnum('WORK')
                            ->setValue($value)
                    )
            );

        $customFieldsValues->add($fieldValue);
    }

    public function leadsStatus($apiClient)
    {
        $leadsService = $apiClient->leads();

        try {
            $leadsCollection = $leadsService->get();
            $successfullyCompletedLeads = [];
        
            foreach ($leadsCollection as $lead) {
                $statusId = $lead->getStatusId();
        
                if ($statusId === 142) {
                    $successfullyCompletedLeads[] = $lead;
                }
            }
        
            foreach ($successfullyCompletedLeads as $lead) {
                
              
                $leadId = $lead->getId();
                $leadName = $lead->getName();
            }
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
    }

    public function checkContactPhoneNumber($apiClient, $inputPhone)
    {
        $contactsService    = $apiClient->contacts();
        $contactsCollection = $contactsService->get();
        
        $contactId = null;
        $phoneExists = false;

        foreach ($contactsCollection as $contact) {
            $phoneField = $contact->getCustomFieldsValues()->getBy('fieldCode', 'PHONE');
            
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
    
            foreach($leadIds as $leadid) {
               $hasSuccessLead = $this->checkLeadSuccessStatus($leadid);
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

    public function checkCustomFields($ageKey, $genderKey)
    {
        $apiClient = AmoCrmHelper::createApiClient();

          $customFields = $apiClient->customFields(EntityTypesInterface::CONTACTS);
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

    public function checkLeadSuccessStatus($leadId)
    {
        $apiClient = AmoCrmHelper::createApiClient();
        $leadsService = $apiClient->leads()->getOne($leadId);

        if($leadsService->getStatusId() === 142) {
            return true;
        }
        
        return false;
    }
}
