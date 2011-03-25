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
		$params = array();
		if ($notification !== null && $notification->isPublished() && $usertask->getWorkitem() !== null)
		{
			$action = $usertask->getWorkitem()->getExecAction();
			if ($action !== null && f_util_ClassUtils::methodExists($action, "getCreationNotifParameters"))
			{
				$params = array_merge($params, $action->getCreationNotifParameters($usertask));
			}
		}
		$this->sendNotification($usertask, $notification, $params);
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
		$params = array();
		if ($notification !== null && $notification->isPublished() && $usertask->getWorkitem() !== null)
		{
			$action = $usertask->getWorkitem()->getExecAction();
			if ($action !== null && f_util_ClassUtils::methodExists($action, "getCancellationNotifParameters"))
			{
				$params = array_merge($params, $action->getCancellationNotifParameters($usertask));
			}
		}
		$this->sendNotification($usertask, $notification, $params);
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
			$params = array('decision' => $decision);
			$notification = $usertask->getTerminationnotification();
			if ($notification !== null && $notification->isPublished() && $usertask->getWorkitem() !== null)
			{
				$action = $workitem->getExecAction();
				if ($action !== null && f_util_ClassUtils::methodExists($action, "getTerminationNotifParameters"))
				{
					$params = array_merge($params, $action->getTerminationNotifParameters($usertask));
				}
			}
			$this->sendNotification($usertask, $notification, $params);
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
		else if ($notification->getPublicationstatus() != 'ACTIVE' && !$notification->isPublished())
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
			
			
			$rc = RequestContext::getInstance();
			$ws = website_WebsiteModuleService::getInstance();
			$websiteId = null;
			$oldWebsiteId = $ws->getCurrentWebsite()->getId();
			$lang = $rc->getLang();
			$classname = $workItem->getExecActionName();
			if (!empty($classname) && f_util_ClassUtils::classExists($classname))
			{
				$action = new $classname();
				$action->initialize($workItem);
				list($websiteId, $lang) = $action->getNotificationWebsiteIdAndLang($notification->getCodename());
			}
			if ($websiteId !== null)
			{
				$ws->setCurrentWebsiteId($websiteId);
			}
			try
			{
				$rc->beginI18nWork($lang);
				$documentId = $workItem->getDocumentid();
				$document = $this->pp->getDocumentInstance($documentId);
				
				// Complete parameters.
				$defaultParameters = workflow_WorkflowEngineService::getInstance()
					->getDefaultNotificationParameters($document, $workItem, $usertask);
				$caseParameters = workflow_CaseService::getInstance()->getParametersArray($workItem->getCase());
				$parameters = array_merge($defaultParameters, $caseParameters, $parameters);
	
				// Send the notification.
				TaskHelper::getNotificationService()->sendMail($notification, array($receiver), $parameters);
				$ws->setCurrentWebsiteId($oldWebsiteId);
				$rc->endI18nWork();
			}
			catch (Exception $e)
			{
				$ws->setCurrentWebsiteId($oldWebsiteId);
				$rc->endI18nWork($e);
			}
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
			$data['properties']['affecteduser'] = $user->getLabel() . '  (' . f_Locale::translate($user->getPersistentModel()->getLabel()) . ')';
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