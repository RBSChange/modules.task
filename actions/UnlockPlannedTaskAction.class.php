<?php
/**
 * task_UnlockPlannedTaskAction
 * @package modules.task.actions
 */
class task_UnlockPlannedTaskAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$plannedTask = $this->getDocumentInstanceFromRequest($request);
		if ($plannedTask !== null)
		{
			$plannedTask->setIsrunning(false);
			if ($request->hasParameter('resetTime'))
			{
				$plannedTask->setNextrundate(date_Calendar::now());
			}
			$plannedTask->save();
			$action = ($request->hasParameter('resetTime') ? 'reset' : 'unlock') . '.plannedtask';
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($action, $plannedTask, array(), 'task');
		}
		return $this->sendJSON(array('message' => 'UnlockPlannedTaskSuccess'));
	}
}