<?php
/**
 * @date Thu, 21 Aug 2008 08:47:34 +0000
 * @author intstaufl
 * @package modules.task
 */
class task_ActionBase extends f_action_BaseAction
{
	
	/**
	 * Returns the task_UsertaskService to handle documents of type "modules_task/usertask".
	 *
	 * @return task_UsertaskService
	 */
	public function getUsertaskService()
	{
		return task_UsertaskService::getInstance();
	}
	
	/**
	 * Returns the task_PlannedtaskService to handle documents of type "modules_task/plannedtask".
	 *
	 * @return task_PlannedtaskService
	 */
	public function getPlannedtaskService()
	{
		return task_PlannedtaskService::getInstance();
	}
	
}