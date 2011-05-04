<?php
/**
 * task_patch_0352
 * @package modules.task
 */
class task_patch_0352 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->addBackGroundIndexingTask();
	}
	
	/**
	 * @return void
	 */
	private function addBackGroundIndexingTask()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('f_tasks_BackgroundIndexingTask');
		$task->setLabel('f_tasks_BackgroundIndexingTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
	}
}