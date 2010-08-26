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
			$classInstance->setPlannedTask($runnableTask);
			
			$classInstance->run();
			if (Framework::isInfoEnabled())
			{
				Framework::info($runnableTask->getId() . ' [' . $taskClassName . '] executed.');
			}
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
	 * @param string $baseURL
	 * @return void
	 */
	static function main($baseURL)
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
			$url = $baseURL . '/changecron.php?taskId=' . $runnableTask->getId();
			self::launchTask($url);
		}
	}
	
	/**
	 * @param string $URL
	 */
	public static function launchTask($URL)
	{
		$rc = curl_init();
		curl_setopt($rc, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.0');
		curl_setopt($rc, CURLOPT_POSTFIELDS, null);
		curl_setopt($rc, CURLOPT_POST, false);
		curl_setopt($rc, CURLOPT_TIMEOUT, 5);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($rc, CURLOPT_URL, $URL);
		curl_exec($rc);
		curl_close($rc);
	}
		
	public static function pingChangeCronURL($pingURl)
	{
		$rc = curl_init();
		curl_setopt($rc, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.0');
		curl_setopt($rc, CURLOPT_POSTFIELDS, null);
		curl_setopt($rc, CURLOPT_POST, false);
		curl_setopt($rc, CURLOPT_TIMEOUT, 1);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($rc, CURLOPT_URL, $pingURl);
		curl_exec($rc);
		curl_close($rc);
	}	
	
	public static function setChangeCronToken($start)
	{
		$tokenPath = f_util_FileUtils::buildCachePath('changecron.pid');
		f_util_FileUtils::writeAndCreateContainer($tokenPath, $start, f_util_FileUtils::OVERRIDE);
	}

	public static function getChangeCronToken()
	{
		$tokenPath = f_util_FileUtils::buildCachePath('changecron.pid');
		if (file_exists($tokenPath))
		{
			return file_get_contents($tokenPath);
		}
		return null;
	}
}