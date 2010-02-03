<?php
class task_ViewUserTaskAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		// Get the parameters.
		$task = $this->getDocumentInstanceFromRequest($request);

		// Check if the current user is the user associated to this task.
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		$userId = ($user !== null) ? $user->getId() : 0;
		
		if ($userId != $task->getUser()->getId())
		{
			if (Framework::isDebugEnabled())
			{
				Framework::debug(__METHOD__ . ' : the current user ' . $userId . ' is different of the task one ' . $task->getUser()->getId());
			}
			return self::getErrorView();
		}

		$workItem = $task->getWorkitem();
		try
		{
			$workDocument = DocumentHelper::getDocumentInstance($workItem->getDocumentid());
		}
		catch (Exception $e)
		{
			return View::ERROR;
		}

		$request->setAttribute('task', $task);

		list($moduleName, $actionName) = explode('_', $workItem->getExecActionName());
		$actionName = str_replace('Workflowaction', '', $actionName);

		return array($moduleName, $actionName);
	}
}