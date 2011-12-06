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

		if ($failed === true)
		{
			$logMessages[] = ob_get_clean();
			$messagesFilePath = f_util_FileUtils::getTmpFile('task_');
			file_put_contents($messagesFilePath, implode("\n", $logMessages));
		}
		else
		{
			$messagesFilePath = '';
		}

		$scriptPath = 'modules/task/lib/bin/taskUpdate.php';
		$output = f_util_System::execHTTPScript($scriptPath, array($runnableTask->getId(), $failed ? 1 : 0, $messagesFilePath));
		if ($output != 'OK')
		{
			Framework::warn(__METHOD__ . ' -> ' . $runnableTask->getId() . ' : ' . $output);
		}
	}

	static function reSchedule($taskId, $date)
	{
		$scriptPath = 'modules/task/lib/bin/taskReSchedule.php';
		$output = f_util_System::execHTTPScript($scriptPath, array($taskId, $date));
		if ($output != 'OK')
		{
			Framework::warn(__METHOD__ . ' -> ' . $taskId . ' : ' . $output);
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