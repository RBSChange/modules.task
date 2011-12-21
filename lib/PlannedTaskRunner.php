<?php
/**
 * @package modules.task
 */
class task_PlannedTaskRunner
{
	
	/**
	 * @param string $baseURL
	 * @return void
	 */
	static function main($baseURL)
	{
		$taskService = task_PlannedtaskService::getInstance();
		foreach ($taskService->getTasksToAutoUnlock() as $task) 
		{
			$taskService->autoUnlock($task);
		}
		
		foreach ($taskService->getTasksToLock() as $task) 
		{
			$taskService->lock($task);
		}
		
		$runningIds = array();
		foreach ($taskService->getTasksToStart() as $task) 
		{
			$taskService->start($task);
			$runningIds[] = $task->getId();
			
		}
		
		foreach ($runningIds as $runningId)
		{
			$url = $baseURL . '/changecron.php?taskId=' . $runningId;
			self::launchTask($url);
		}
	}
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	static function executeSystemTask($task)
	{
		$taskService = task_PlannedtaskService::getInstance();
		$erroMessage = null;
		$taskId = $task->getId();
		$taskClassName = $task->getSystemtaskclassname();
		
		try
		{
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
			$classInstance->setParameterString($task->getParameters());
			$classInstance->setPlannedTask($task);
			$taskService->ping($task);
			$startTime = time();
			$classInstance->run();
		}
		catch (BaseException $e)
		{
			$erroMessage = ''. $e->getLocaleMessage();

		}
		catch (Exception $e)
		{
			$erroMessage = ''. $e->getMessage();
		}

/*
		if (defined('MYSQL_WAIT_TIMEOUT') && time() - $startTime >=  MYSQL_WAIT_TIMEOUT)
		{
			// TODO et le transaction manager !!!
			f_persistentdocument_PersistentProvider::refresh();
		}
*/				
		if ($erroMessage === null)
		{
			$taskService->end($task);
		}
		else 
		{
			$taskService->error($task, $erroMessage);
		}
	}
	

	
	/**
	 * @param string $URL
	 */
	public static function launchTask($URL)
	{
		$rc = curl_init();
		curl_setopt($rc, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($rc, CURLOPT_HTTPHEADER, array('Cache-Control: no-store, no-cache, no-transform', 'Pragma: no-cache', 'Connection: close'));
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.6');
		curl_setopt($rc, CURLOPT_POST, 1);
		curl_setopt($rc, CURLOPT_POSTFIELDS, null);	
		curl_setopt($rc, CURLOPT_TIMEOUT, 5);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($rc, CURLOPT_URL, $URL);
		$lp = Framework::getConfigurationValue('general/selfRequestProxy');
		if (!empty($lp)) {curl_setopt($rc, CURLOPT_PROXY, $lp);}
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
		curl_setopt($rc, CURLOPT_HTTPHEADER, array('Cache-Control: no-store, no-cache, no-transform', 'Pragma: no-cache', 'Connection: close'));
		curl_setopt($rc, CURLOPT_USERAGENT, 'RBSChange/3.6');
		curl_setopt($rc, CURLOPT_POST, 1);
		curl_setopt($rc, CURLOPT_POSTFIELDS, null);
		curl_setopt($rc, CURLOPT_FRESH_CONNECT, 1); // don't use a cached version of the url 	
		curl_setopt($rc, CURLOPT_TIMEOUT, 1);
		curl_setopt($rc, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($rc, CURLOPT_URL, $pingURL);
		$lp = Framework::getConfigurationValue('general/selfRequestProxy');
		if (!empty($lp)) {curl_setopt($rc, CURLOPT_PROXY, $lp);}
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