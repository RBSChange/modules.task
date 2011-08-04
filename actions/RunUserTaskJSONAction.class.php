<?php
/**
 * This class is used to execute a user task.
 * It has to be called after a commentary/decision form submit to perform the task.
 * @package modules.task
 */
class task_RunUserTaskJSONAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		// Get the parameters.
		$task = $this->getDocumentInstanceFromRequest($request);
		$decision = $request->getParameter('decision');
		$commentary = $request->getParameter('commentary');

		// Check if the current user is the user associated to this task.
		$user = users_UserService::getInstance()->getCurrentBackEndUser();
		$userId = ($user !== null) ? $user->getId() : 0;
		if ($userId != $task->getUser()->getId())
		{
			throw new BaseException('the current user ' . $userId . ' is different of the task one ' . $task->getUser()->getId());
		}

		// Perform the task.
		if ($task)
		{
			TaskHelper::getUsertaskService()->execute($task, $decision, $commentary);
		}

		return $this->sendJSON(array());
	}
}