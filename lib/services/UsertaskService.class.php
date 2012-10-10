<?php
/**
 * @package modules.task
 * @method task_UserfolderService getInstance()
 */
class task_UsertaskService extends f_persistentdocument_DocumentService
{
	/**
	 * @return task_persistentdocument_usertask
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_task/usertask');
	}

	/**
	 * Create a query based on 'modules_task/usertask' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_task/usertask');
	}

	/**
	 * Send the creation notification.
	 * @param task_persistentdocument_usertask $usertask
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($usertask, $parentNodeId)
	{
		// Send the creation notification.
		$notification = $usertask->getCreationnotification();
		$this->sendNotification($usertask, $notification, 'creation');
	}

	/**
	 * Cancel the task.
	 * @param task_persistentdocument_usertask $usertask
	 */
	public function cancelUsertask($usertask)
	{
		$usertask->setPublicationstatus('TRASH');
		$usertask->save();

		// Send the cancellation notification.
		$notification = $usertask->getCancellationnotification();
		$this->sendNotification($usertask, $notification, 'cancellation');
	}

	/**
	 * Execute action defined for the task and close the task.
	 * @param task_persistentdocument_usertask $usertask
	 * @param string $decision
	 * @param string $commentary
	 * @return boolean
	 */
	public function execute($usertask, $decision, $commentary)
	{
		if (workflow_WorkflowEngineService::getInstance()->executeTask($usertask, $decision, $commentary))
		{
			$usertask->setCommentary($commentary);
			$usertask->setPublicationstatus('FILED');
			$usertask->save();

			// Cancel the other tasks for the workitem.
			$workitem = $usertask->getWorkitem();
			$query = $this->createQuery();
			$query->createCriteria('workitem')->add(Restrictions::eq('id', $workitem->getId()));
			$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLISHED')));
			$query->add(Restrictions::ne('id', $usertask->getId()));
			$tasksToCancel = $query->find();
			foreach ($tasksToCancel as $task)
			{
				$this->cancelUsertask($task);
			}

			// Send the termination notification.
			$notification = $usertask->getTerminationnotification();
			$this->sendNotification($usertask, $notification, 'termination');
			return true;
		}
		return false;
	}

	/**
	 * Set the notification.
	 * @param task_persistentdocument_usertask $usertask
	 * @param notification_persistentdocument_notification $notification
	 * @param string $notifType
	 * @return boolean
	 */
	private function sendNotification($usertask, $notification, $notifType)
	{
		if (!$notification)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' : No notification to send for task ' . $usertask->getId() . '.');
			}
			return false;
		}
		
		// Get the user.
		$user = $usertask->getUser();
		if ($user === null)
		{
			Framework::warn(__METHOD__ . ' : There is no user associated to the task ' . $usertask->getId() . '.');
			return false;
		}
					
		$codeName = $notification->getCodename();
		$websiteId = null;
		$lang = null;
		$suffix = null;
		$action = $usertask->getWorkitem()->getExecAction();
		if ($action !== null)
		{
			list($websiteId, $lang) = $action->getNotificationWebsiteIdAndLang($codeName);
			$method = 'get' . ucfirst($notifType) . 'NotifSuffix';
			if (f_util_ClassUtils::methodExists($action, $method))
			{
				$suffix = $action->{$method}($usertask);
			}
		}
		
		if ($suffix)
		{
			$notification = notification_NotificationService::getInstance()->getConfiguredByCodeNameAndSuffix($codeName, $suffix, $websiteId, $lang);
		}
		else
		{
			$notification = notification_NotificationService::getInstance()->getConfiguredByCodeName($codeName, $websiteId, $lang);
		}
	
		if ($notification === null)
		{
			if (Framework::isInfoEnabled())
			{
				Framework::info(__METHOD__ . ' : No published notification found for codeName = ' . $codeName);
			}
			return false;
		}
		$notification->setSendingModuleName('workflow');
		$callback = array($this, 'getNotificationParameters');
		$params = array('usertask' => $usertask, 'action' => $action, 'notifType' => $notifType);
		return $user->getDocumentService()->sendNotificationToUserCallback($notification, $user, $callback, $params);
	}
	
	/**
	 * @param task_persistentdocument_usertask $usertask
	 * @return array
	 */
	public function getNotificationParameters($params)
	{
		$usertask = $params['usertask'];
		$action = $params['action'];
		$method = 'get' . ucfirst($params['notifType']) . 'NotifParameters';
		$parameters = array();
		if ($action && f_util_ClassUtils::methodExists($action, $method))
		{
			$parameters = $action->{$method}($usertask);
		}
		$workItem = $usertask->getWorkitem();
		$document = DocumentHelper::getDocumentInstance($workItem->getDocumentid());
		$wes = workflow_WorkflowEngineService::getInstance();
		$defaultParameters = $wes->getDefaultNotificationParameters($document, $workItem, $usertask);
		$caseParameters = workflow_CaseService::getInstance()->getParametersArray($usertask->getWorkitem()->getCase());
		return array_merge($defaultParameters, $caseParameters, $parameters);
	}

	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param task_persistentdocument_usertask $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		
		// Affected user.
		$user = $document->getUser();
		if ($user !== null)
		{
			$data['properties']['affecteduser'] = $user->getLabel() . '  (' . LocaleService::getInstance()->trans($user->getPersistentModel()->getLabelKey()) . ')';
		}
		else 
		{
			$data['properties']['affecteduser'] = 'NO USER!';
		}
		
		// Task description.
		$workitem = $document->getWorkitem();
		$additionalInfo = $workitem->getLabel();
		try 
		{
			$document = DocumentHelper::getDocumentInstance($workitem->getDocumentid());
			$additionalInfo .= ' (' . $document->getLabel()  . ' -  ID ' . $document->getId() . ')';
		}
		catch (Exception $e)
		{
			$e; // Avoid warning in Eclipse.
			// Document doesn't exist any more.
		}
		$data['properties']['additionalinfo'] = $additionalInfo;
		return $data;
	}	
}