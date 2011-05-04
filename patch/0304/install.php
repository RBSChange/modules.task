<?php
/**
 * task_patch_0304
 * @package modules.task
 */
class task_patch_0304 extends patch_BasePatch
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
		return '0304';
	}
}