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
		$plannedTask = task_persistentdocument_plannedtask::getInstanceById($this->getDocumentIdFromRequest($request));
		if ($request->hasParameter('resetTime'))
		{
			$plannedTask->reSchedule(date_Calendar::getInstance());
			$action = 'reset.plannedtask';
			UserActionLoggerService::getInstance()->addCurrentUserDocumentEntry($action, $plannedTask, array(), 'task');
		}
		
		if ($plannedTask->getExecutionStatus() === task_PlannedtaskService::STATUS_RUNNING)
		{
			$plannedTask->getDocumentService()->error($plannedTask, LocaleService::getInstance()->transBO('m.task.document.plannedtask.bo-cancel'));
		}
		else if ($plannedTask->getExecutionStatus() === task_PlannedtaskService::STATUS_LOCKED)
		{
			$plannedTask->getDocumentService()->unlock($plannedTask);
		}
		return $this->sendJSON(array('message' => 'UnlockPlannedTaskSuccess'));
	}
}