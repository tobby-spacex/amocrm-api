<?php

namespace App\Traits;

use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TaskModel;
use App\Helper\AmoCrmHelper;
use AmoCRM\Models\ContactModel;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\Leads\LeadsCollection;

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
        // $apiClient = AmoCrmHelper::createApiClient();
        $leadsService = $apiClient->leads();
        $tasksService = $apiClient->tasks();

        $this->lead->setName('Auto deal')
            ->setPrice(54321)
            ->setContacts(
                ($this->contactsCollection)
                    ->add(
                        ($this->contactModel)
                            ->setId($contactId)
                    )
            );

        
        $this->leadsCollection->add($this->lead);
        $leadsService->add($this->leadsCollection);

        try {
            $this->taskModel->setTaskTypeId(TaskModel::TASK_TYPE_ID_FOLLOW_UP)
                ->setText('Task to do')
                ->setCompleteTill(strtotime('+4 days'))
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
}
