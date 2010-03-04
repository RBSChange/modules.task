<?php
/**
 * @package modules.task
 */
class task_PlannedTaskRunner
{
	/**
	 * @param task_persistentdocument_plannedtask $runnableTask
	 */
	static function executeSystemTask($runnableTask)
	{
		$taskService = task_PlannedtaskService::getInstance();
		$logMessages = array();
		try
		{
			ob_start();
			$taskClassName = $runnableTask->getSystemtaskclassname();
			if (!f_util_ClassUtils::classExists($taskClassName))
			{
				throw new Exception("Class $taskClassName does not exist");
			}
			
			$reflectionClass = new ReflectionClass($taskClassName);
			
			if (!$reflectionClass->implementsInterface('task_SystemTask'))
			{
				throw new Exception("Class $taskClassName does not implement task_SystemTask");
			}
			
			$classInstance = $reflectionClass->newInstance();
			$classInstance->setParameterString($runnableTask->getParameters());
			$classInstance->run();
			$failed = false;
		}
		catch (Exception $e)
		{
			$logMessages[] = $e->getMessage();
			$failed = true;
		}
		
		if ($failed === true)
		{
			$logMessages[] = ob_get_clean();
		}
		$runnableTask->setHasFailed($failed);
		$taskService->rescheduleIfNecesseary($runnableTask);
		if ($failed)
		{
			self::createNewDocumentLogEntry($runnableTask->getId(), implode("\n", $logMessages));
		}
	}
	
	/**
	 * @param Integer $taskId
	 * @param String $message
	 */
	static function createNewDocumentLogEntry($taskId, $message)
	{
		$logEntry = generic_DocumentlogentryService::getInstance()->getNewDocumentInstance();
		$logEntry->setLabel("&modules.tasks.document.plannedtask.ExecutionFailed;");
		$logEntry->setDocumentid($taskId);
		$logEntry->setActor(__CLASS__);
		$logEntry->setDecision("failed");
		$logEntry->setCommentary($message);
		$logEntry->save();
	}
	
	/**
	 * @return void
	 */
	static function main()
	{
		$taskService = task_PlannedtaskService::getInstance();
		$runnableTasks = $taskService->getRunnableTasks();
		foreach ($runnableTasks as $runnableTask)
		{
			$runnableTask->setIsrunning(true);
			$runnableTask->save();
		}

		foreach ($runnableTasks as $runnableTask)
		{
			$processHandle = popen("php " . f_util_FileUtils::buildWebappPath('bin' , 'tasks', 'plannedTasks.php' . ' ' . $runnableTask->getId()), 'r');
			while(($string = fread($processHandle, 1024)))
			{
				echo $string;
			}
			pclose($processHandle);
		}
	}
}