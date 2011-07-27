<?php
Controller::newInstance("controller_ChangeController");
$arguments = $_POST['argv'];
if (count($arguments) != 3)
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}
else
{
	if (Framework::isInfoEnabled())
	{
		Framework::info('taskUpdate.php: ' . implode(', ', $arguments));
	}

	$tm = f_persistentdocument_TransactionManager::getInstance();
	try 
	{
		$tm->beginTransaction();
		
		$taskId = intval($arguments[0]);
		$runnableTask = task_persistentdocument_plannedtask::getInstanceById($taskId);
		
		$failed = intval($arguments[1]) == 1;
		$messagesFilePath = $arguments[2];
		if (empty($messagesFilePath) || !is_readable($messagesFilePath))
		{
			$logMessages = '';
		}
		else
		{
			$logMessages = file_get_contents($messagesFilePath);
			@unlink($messagesFilePath);
		}
		
		$runnableTask->setHasFailed($failed);
		$runnableTask->getDocumentService()->rescheduleIfNecesseary($runnableTask);
		if ($failed)
		{
			$action = 'run-failed.plannedtask';
			UserActionLoggerService::getInstance()->addUserDocumentEntry(null, $action, $runnableTask, array('message' => $logMessages), 'task');
		}
		$tm->commit();
		echo 'OK';
	}
	catch (Exception $e)
	{
		echo 'ERROR: ';
		$tm->rollBack($e);
		echo $e->getMessage();
	}
}