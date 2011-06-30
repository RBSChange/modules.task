<?php
/**
 * task_patch_0356
 * @package modules.task
 */
class task_patch_0356 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_CleanDataCacheTask');
		$task->setHour(2);
		$task->setLabel('task_CleanDataCacheTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
	}
}