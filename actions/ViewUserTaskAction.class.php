<?php
class task_ViewUserTaskAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
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
			Framework::error(__METHOD__ . ' : the current user ' . $userId . ' is different of the task one ' . $task->getUser()->getId());
			return change_View::NONE;
		}

		$workItem = $task->getWorkitem();
		try
		{
			DocumentHelper::getDocumentInstance($workItem->getDocumentid());
		}
		catch (Exception $e)
		{
			Framework::error(__METHOD__ . ' EXCEPTION: ' . $e->getMessage());
			return change_View::NONE;
		}

		$request->setAttribute('task', $task);

		list($moduleName, $actionName) = explode('_', $workItem->getExecActionName());
		$actionName = str_replace('Workflowaction', '', $actionName);

		return array($moduleName, $actionName);
	}
}