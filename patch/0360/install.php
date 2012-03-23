<?php
/**
 * task_patch_0360
 * @package modules.task
 */
class task_patch_0360 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$tasks = task_PlannedtaskService::getInstance()->getBySystemtaskclassname('task_I18nSynchroTask');
		if (count($tasks) == 0)
		{
			$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
			$task->setSystemtaskclassname('task_I18nSynchroTask');
			$task->setLabel('task_I18nSynchroTask');
			$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
		}
	}
}