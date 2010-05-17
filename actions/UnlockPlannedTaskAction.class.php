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
			if ($request->hasParameter('resetTime'))
			{
				$plannedTask->setNextrundate(date_Calendar::now());
			}
			$plannedTask->save();
		}
		
		return self::getSuccessView();
	}
}