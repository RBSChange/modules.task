<?php
/**
 * task_persistentdocument_usertask
 * @package modules.task
 */
class task_persistentdocument_usertask extends task_persistentdocument_usertaskbase
{
	/**
	 * @return String
	 */
	public function getDialogName()
	{
		list(, $actionName) = explode('_', $this->getWorkitem()->getExecActionName());
		return str_replace('Workflowaction', '', str_replace('WorkflowAction', '', $actionName));
	}
	
	/**
	 * @return String
	 */
	public function getModule()
	{
		list($moduleName, ) = explode('_', $this->getWorkitem()->getExecActionName());
		return $moduleName;
	}
}