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
    protected $lead;
    protected $contactsCollection;
    protected $contactModel;
    protected $leadsCollection;
    protected $tasksCollection;
    protected $taskModel;

    public function __construct(
        LeadModel $lead,
        ContactsCollection $contactsCollection,
        ContactModel $contactModel,
        LeadsCollection $leadsCollection,
        TasksCollection $tasksCollection,
        TaskModel $taskModel
    ) 
    {
       $this->lead = $lead;
       $this->contactsCollection = $contactsCollection;
       $this->contactModel = $contactModel;
       $this->leadsCollection = $leadsCollection;
       $this->tasksCollection = $tasksCollection;
       $this->taskModel = $taskModel;
    }

    public function createNewLead($apiClient, $contactId)
    {
        $leadsService = $apiClient->leads();
        $tasksService = $apiClient->tasks();

        $this->lead->setName('Auto deal')
            ->setContacts(
                ($this->contactsCollection)
                    ->add(
                        ($this->contactModel)
                            ->setId($contactId)
                    )
            );

        
        $this->leadsCollection->add($this->lead);
        $leadsService->add($this->leadsCollection);

        // Assign elements to a lead
        $this->addCatalogElements($apiClient, $this->lead->getId());    
        try {
            $completeDate = strtotime('+4 weekdays');
            $completeDateTime = strtotime(date('Y-m-d', $completeDate) . ' 09:00:00');

            $this->taskModel->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
                ->setText('Task to do')
                ->setCompleteTill($completeDateTime)
                ->setDuration(9 * 3600) 
                ->setEntityType(EntityTypesInterface::LEADS)
                ->setEntityId($this->lead->getId());
            $this->tasksCollection->add($this->taskModel);
            
            $tasksService = $apiClient->tasks();
            try {
                $tasksCollection = $tasksService->add($this->tasksCollection);
            } catch (AmoCRMApiException $e) {
                print_r($e);
                die;
            }

        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
    }

    public function addCatalogElements($apiClient, $leadId)
    {
        $catalogsCollection = $apiClient->catalogs()->get();
        $catalog = $catalogsCollection->getBy('name', 'Товары');

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
