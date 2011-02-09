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
			$startTime = time();
			$classInstance->run();
			if (Framework::isInfoEnabled())
			{
				Framework::info($runnableTask->getId() . ' [' . $taskClassName . '] executed.');
			}
			$failed = false;
		}
		catch (BaseException $e)
		{
			$logMessages[] = $e->getLocaleMessage();
			$failed = true;
		}
		catch (Exception $e)
		{
			$logMessages[] = $e->getMessage();
			$failed = true;
		}
		if (defined('MYSQL_WAIT_TIMEOUT') && time() - $startTime >=  MYSQL_WAIT_TIMEOUT)
		{
			// Make sure we didn't loose the MySQL connection due to inactivity timeout
			f_persistentdocument_PersistentProvider::refresh();
		}
		if ($failed === true)
		{
			$logMessages[] = ob_get_clean();
		}
		$runnableTask->setHasFailed($failed);
		
		$taskService->rescheduleIfNecesseary($runnableTask);
		if ($failed)
		{
			$action = 'run-failed.plannedtask';
			UserActionLoggerService::getInstance()->addUserDocumentEntry(null, $action, $runnableTask, array('message' => implode("\n", $logMessages)), 'task');
		}
	}
	
	/**
	 * @param string $baseURL
	 * @return void
	 */
	static function main($baseURL)
	{
		$taskService = task_PlannedtaskService::getInstance();
		$runnableTasks = $taskService->getRunnableTasks();
		$runningIds = array();
		foreach ($runnableTasks as $runnableTask)
		{
			if ($taskService->run($runnableTask))
			{
				$runningIds[] = $runnableTask->getId();
			}
		}

		foreach ($runningIds as $runningId)
		{
			$url = $baseURL . '/changecron.php?taskId=' . $runningId;
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
	
	/**
	 * @param string $pingURL
	 */
	public static function pingChangeCronURL($pingURL)
	{
		$rc = curl_init();
		curl_setopt($rc, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.0');
		curl_setopt($rc, CURLOPT_POSTFIELDS, null);
		curl_setopt($rc, CURLOPT_POST, false);
		curl_setopt($rc, CURLOPT_TIMEOUT, 1);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($rc, CURLOPT_URL, $pingURL);
		curl_exec($rc);
		curl_close($rc);
	}	
	
	/**
	 * @param string $start
	 */
	public static function setChangeCronToken($start)
	{
		$tokenPath = f_util_FileUtils::buildCachePath('changecron.pid');
		f_util_FileUtils::writeAndCreateContainer($tokenPath, $start, f_util_FileUtils::OVERRIDE);
	}

	/**
	 * @return string
	 */
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