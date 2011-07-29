<?php
/**
 * task_BlockDashboardPlannedTasksInfosAction
 * @package modules.task.lib.blocks
 */
class task_BlockDashboardPlannedTasksInfosAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @see dashboard_BlockDashboardAction::setRequestContent()
	 *
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition) {return;}
		
		website_StyleService::getInstance()->registerStyle('modules.task.dashboard');
		$pts = task_PlannedtaskService::getInstance();
		$configuration = $this->getConfiguration();
		$request->setAttribute('errorTasks', $pts->getLockedTasks());
		$publishTasks = $pts->getBySystemtaskclassname('task_PublishTask');
		$request->setAttribute('monitoredTasks', array_merge(array($publishTasks[0]), $configuration->getTasksToMonitor()));
	}
}