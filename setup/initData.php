<?php
class task_Setup extends object_InitDataSetup
{
	public function install()
	{
		try
		{
			$this->executeModuleScript('lists.xml');
			$this->addCronTask();
		}
		catch (Exception $e)
		{
			echo "ERROR: " . $e->getMessage() . "\n";
			Framework::exception($e);
		}
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
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_PublishTask');
		$task->setUniqueExecutiondate(date_Calendar::getInstance());
		$task->setLabel('task_PublishTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_ClearDocumentCacheTask');
		$task->setHour(1);
		$task->setLabel('task_ClearDocumentCacheTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_CleanDataCacheTask');
		$task->setHour(2);
		$task->setLabel('task_CleanDataCacheTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));

		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('task_I18nSynchroTask');
		$task->setLabel('task_I18nSynchroTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));
		
		$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
		$task->setSystemtaskclassname('f_tasks_BackgroundIndexingTask');
		$task->setLabel('f_tasks_BackgroundIndexingTask');
		$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'task'));	
	}
}