<?php
/**
 * task_patch_0353
 * @package modules.task
 */
class task_patch_0353 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$query = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::eq('systemtaskclassname', 'f_tasks_ReindexDocumentsByUpdatedRolesTask'))
			->add(Restrictions::eq('executionStatus', task_PlannedtaskService::STATUS_RUNNING))
			->add(Restrictions::published());
		foreach ($query->find() as $task)
		{
			$task->setExecutionStatus(task_PlannedtaskService::STATUS_FAILED);
			$task->save();
			$task->getDocumentService()->file($task->getId());
		}
	}
}