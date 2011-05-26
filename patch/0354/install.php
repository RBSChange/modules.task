<?php
/**
 * task_patch_0354
 * @package modules.task
 */
class task_patch_0354 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('update-autoload', array('modules/task'));
		$this->execChangeCommand('compile-editors-config');
		$this->executeModuleScript('lists.xml', 'task');
	
		$this->executeSQLQuery("ALTER TABLE `m_task_doc_plannedtask` ADD `unlockcount` INT( 11 ) NULL ,
ADD `executionstatus` VARCHAR( 255 ) NULL ,
ADD `executionstatusdate` DATETIME NULL ,
ADD `runningdate` DATETIME NULL ,
ADD `document_s18s` MEDIUMTEXT NULL ");

		$tasks = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::isNull('executionStatus'))
			->find();
		
		$this->log('update tasks ... ');
		foreach ($tasks as $task) 
		{
			if ($task instanceof task_persistentdocument_plannedtask)
			{
				$task->setExecutionStatus(task_PlannedtaskService::STATUS_SUCCESS);
				$task->setExecutionStatusDate($task->getLastrundate());	
				$task->setUnlockCount(0);
				$task->setTotalErrorCount(0);
				$task->setTotalSuccessCount(0);
				$task->setTotalLockCount(0);
				$task->setDurationAvg(0);
				$task->setModificationdate(null);
				$task->setMeta('task_durations', null);
				$task->save();
			}
		}
		
		$this->log('compile-locales for task ... ');
		$this->execChangeCommand('compile-locales', array('task'));
		
		$this->log('import useractionlogger ... ');
		$this->executeModuleScript('useractionlogger.xml', 'task');
	}
}