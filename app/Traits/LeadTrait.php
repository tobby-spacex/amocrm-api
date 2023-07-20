<?php

namespace App\Traits;

use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TaskModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\CatalogElementsCollection;

trait LeadTrait
{
    /**
     * Create a new lead for a given contact using the AmoCRM API client.
     *
     * @param \AmoCRM\Client\AmoCRMApiClient $apiClient The AmoCRM API client.
     * @param int $contactId The ID of the contact associated with the new lead.
     * 
     * @return void
     */
    public function createNewLead(\AmoCRM\Client\AmoCRMApiClient $apiClient, $contactId): void
    {
        $lead = new LeadModel();
        $usersCollection = $apiClient->users()->get();

        $usersCollection = $apiClient->users()->get();
        $userIds = $usersCollection->pluck('id');

        $randomKey = array_rand($userIds);
        $randomUserId = $userIds[$randomKey];

        $lead->setName('Auto deal')
            ->setContacts(
                (new ContactsCollection())
                    ->add(
                        (new ContactModel)
                            ->setId($contactId)
                    )
            )
            ->setResponsibleUserId($randomUserId);
            
        $leadsCollection = new LeadsCollection();
        $leadsCollection->add($lead);

        try {
            $leadsCollection = $apiClient->leads()->add($leadsCollection);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }

        // Assign elements to a lead
        $this->addCatalogElements($apiClient, $lead->getId());    
        try {
            $taskModel = new TaskModel();
            $tasksCollection = new TasksCollection();

            $completeDate = strtotime('+4 weekdays');
            $completeDateTime = strtotime(date('Y-m-d', $completeDate) . ' 09:00:00');

            $taskModel->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
                ->setText('Task to do')
                ->setCompleteTill($completeDateTime)
                ->setDuration(9 * 3600) 
                ->setEntityType(EntityTypesInterface::LEADS)
                ->setEntityId($lead->getId());
            $tasksCollection->add($taskModel);
            
            try {
                $tasksCollection = $apiClient->tasks()->add($tasksCollection);
            } catch (AmoCRMApiException $e) {
                print_r($e);
                die;
            }

        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
    }

    /**
     * Add catalog elements (products) to a lead using the AmoCRM API client.
     *
     * @param \AmoCRM\Client\AmoCRMApiClient $apiClient The AmoCRM API client.
     * @param int $leadId The ID of the lead to which the catalog elements will be added.
     * 
     * @return void
     */
    public function addCatalogElements(\AmoCRM\Client\AmoCRMApiClient $apiClient, int $leadId): void
    {
        $catalogsCollection = $apiClient->catalogs()->get();
        $catalog = $catalogsCollection->getBy('id', 10701);

        $catalogElementsCollection = new CatalogElementsCollection();
        $catalogElementsService    = $apiClient->catalogElements($catalog->id);
        $catalogElementsFilter     = new CatalogElementsFilter();
        $catalogElementsFilter->setQuery('Product');
        try {
            $catalogElementsCollection = $catalogElementsService->get($catalogElementsFilter);
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
        
        $capElement = $catalogElementsCollection->getBy('name', 'Product Cap');
        $shirtElement = $catalogElementsCollection->getBy('name', 'Product T-Shirt');

        if ($capElement &&  $shirtElement) {
            $capElement->setQuantity(10);
            $shirtElement ->setQuantity(5);
            try {
                $lead = $apiClient->leads()->getOne($leadId);
            } catch (AmoCRMApiException $e) {
                printError($e);
                die;
            }
        
            $links = new LinksCollection();
            $links->add($capElement);
            $links->add($shirtElement);
            try {
                $apiClient->leads()->link($lead, $links);
            } catch (AmoCRMApiException $e) {
                printError($e);
                die;
            }
        }
    }
}
