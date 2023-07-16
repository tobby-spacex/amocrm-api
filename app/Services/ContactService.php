<?php

namespace App\Services;

use AmoCRM\Models\ContactModel;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\Customers\CustomerModel;
use AmoCRM\Collections\CustomFieldsValuesCollection;
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
            $contact         = $contactsService->getOne($contactId);

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

        return false; // No new customer created
    }
}