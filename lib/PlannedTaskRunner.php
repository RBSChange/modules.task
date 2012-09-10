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
		Framework::info(__METHOD__ . ' ' . $URL);
		
		$client = change_HttpClientService::getInstance()->getNewHttpClient(array('timeout' => 5));
		$client->setHeaders(array('Cache-Control: no-store, no-cache, no-transform', 'Pragma: no-cache', 'Connection: close'));
		$client->setUri($URL);
		$adapter = $client->getAdapter();
		if ($adapter instanceof \Zend\Http\Client\Adapter\Curl)
		{
			$selfRequestProxy = Framework::getConfigurationValue('general/selfRequestProxy');
			if (!empty($selfRequestProxy))
			{
				$adapter->setCurlOption(CURLOPT_PROXY, $selfRequestProxy);
			}
		}
				
		try 
		{
			$client->setMethod(\Zend\Http\Request::METHOD_POST);
			$client->send();
		} 
		catch (Exception $e)
		{
			Framework::info(__METHOD__ . ' ' . $e->getCode() . ' ' . $e->getMessage());
		}
	}
	
	/**
	 * @param string $pingURL
	 */
	public static function pingChangeCronURL($pingURL)
	{
		$client = change_HttpClientService::getInstance()->getNewHttpClient(array('timeout' => 5));
		$client->setHeaders(array('Cache-Control: no-store, no-cache, no-transform', 'Pragma: no-cache', 'Connection: close'));
		$client->setUri($pingURL);
		$adapter = $client->getAdapter();
		if ($adapter instanceof \Zend\Http\Client\Adapter\Curl)
		{
			$selfRequestProxy = Framework::getConfigurationValue('general/selfRequestProxy');
			if (!empty($selfRequestProxy))
			{
				$adapter->setCurlOption(CURLOPT_PROXY, $selfRequestProxy);
			}
		}
		
		try 
		{
			$client->setMethod(\Zend\Http\Request::METHOD_POST);
			$client->send();
		} 
		catch (Exception $e)
		{
			Framework::info(__METHOD__ . ' ' . $e->getCode() . ' ' . $e->getMessage());
		}
	}	
	
	/**
	 * @param string $start
	 */
	public static function setChangeCronToken($start)
	{
		$tokenPath = f_util_FileUtils::buildChangeCachePath('changecron.pid');
		f_util_FileUtils::writeAndCreateContainer($tokenPath, $start, f_util_FileUtils::OVERRIDE);
	}

	/**
	 * @return string
	 */
	public static function getChangeCronToken()
	{
		$tokenPath = f_util_FileUtils::buildChangeCachePath('changecron.pid');
		if (file_exists($tokenPath))
		{
			return file_get_contents($tokenPath);
		}
		return null;
	}
}