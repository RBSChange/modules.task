<?php
/**
 * task_patch_0355
 * @package modules.task
 */
class task_patch_0355 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_ClearDocumentCacheTask');
		$task->setHour(1);
		$task->setLabel('task_ClearDocumentCacheTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
	}
}