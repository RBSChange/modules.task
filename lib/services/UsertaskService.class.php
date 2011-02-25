<?php

class task_UsertaskService extends f_persistentdocument_DocumentService
{
	/**
	 * @var task_UsertaskService
	 */
	private static $instance;

	/**
	 * @return task_UsertaskService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

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
		return $this->pp->createQuery('modules_task/usertask');
	}

	/**
	 * Send the creation notification.
	 * @param task_persistentdocument_usertask $usertask
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function postInsert($usertask, $parentNodeId)
	{
		$notification = $usertask->getCreationnotification();
		$this->sendNotification($usertask, $notification);
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
		$this->sendNotification($usertask, $notification);
	}

	/**
	 * Execute action defined for the task and close the task.
	 * @param task_persistentdocument_usertask $usertask
	 * @param string $decision
	 * @param string $commentary
	 * @return Boolean
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
			$query->add(Restrictions::in('publicationstatus', array('ACTIVE', 'PUBLICATED')));
			$query->add(Restrictions::ne('id', $usertask->getId()));
			$tasksToCancel = $query->find();
			foreach ($tasksToCancel as $task)
			{
				$this->cancelUsertask($task);
			}

			// Get the decision label.
			$decision = f_Locale::translate('&modules.workflow.bo.general.decision-' . strtolower($decision) . ';');

			// Send the termination notification.
			$notification = $usertask->getTerminationnotification();
			$this->sendNotification($usertask, $notification, array('decision' => $decision));
			return true;
		}
		return false;
	}

	/**
	 * Set the notification.
	 * @param task_persistentdocument_usertask $usertask
	 * @param notification_persistentdocument_notification $notification
	 */
	private function sendNotification($usertask, $notification, $parameters = array())
	{
		if (!$notification)
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : No notification to send.');
			}
		}
		else if ($notification->getPublicationstatus() != 'ACTIVE' && !$notification->isPublicated())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : The notification is not active : ' . $notification->getPublicationstatus());
			}
		}
		else
		{
			// Get the user email.
			$user = $usertask->getUser();
			if (!$user)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' : There is no user associated to the task ' . $usertask->getId());
				}
				return;
			}

			$userEmail = $user->getEmail();
			if (!$userEmail)
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' : The user ' . $user->getId() . ' has no email');
				}
				return;
			}

			$receiver = sprintf('%s <%s>', f_util_StringUtils::strip_accents($user->getFullname()), $userEmail);

			$workItem = $usertask->getWorkitem();
			$documentId = $usertask->getWorkitem()->getDocumentid();
			$document = $this->pp->getDocumentInstance($documentId);
			
			// Complete parameters.
			$defaultParameters = workflow_WorkflowEngineService::getInstance()
				->getDefaultNotificationParameters($document, $workItem, $usertask);
			$caseParameters = workflow_CaseService::getInstance()->getParametersArray($usertask->getWorkitem()->getCase());
			$parameters = array_merge($defaultParameters, $caseParameters, $parameters);

			// Send the notification.
			TaskHelper::getNotificationService()->sendMail($notification, array($receiver), $parameters);
		}
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
			$data['properties']['affecteduser'] = $user->getFullName() . '  (' . f_Locale::translate($user->getPersistentModel()->getLabel()) . ')';
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