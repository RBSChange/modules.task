<?php
class task_Setup extends object_InitDataSetup
{

	public function install()
	{
		try
		{
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
	}

}