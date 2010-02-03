<?php
/**
 * task_UnlockPlannedTaskAction
 * @package modules.task.actions
 */
class task_UnlockPlannedTaskAction extends task_Action
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
			$plannedTask->save();
		}
		return self::getSuccessView();
	}
}