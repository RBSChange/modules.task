<?php
/**
 * task_patch_0300
 * @package modules.task
 */
class task_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->addCronTask();
	}

	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'task';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
	
	
	private function addCronTask()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_HourChangeTask');
		$task->setMinute(0);
		$task->setLabel('task_HourChangeTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
		
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_DayChangeTask');
		$task->setHour(0);
		$task->setMinute(5);
		$task->setLabel('task_DayChangeTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
	}
}