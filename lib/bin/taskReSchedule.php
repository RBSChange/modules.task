<?php
Controller::newInstance("controller_ChangeController");
$arguments = $_POST['argv'];
if (count($arguments) != 2)
{
	Framework::error(__FILE__ . " invalid arguments " . var_export($arguments, true));
	echo 'ERROR';
}
else
{
	if (Framework::isInfoEnabled())
	{
		Framework::info('taskReSchedule.php: ' . implode(', ', $arguments));
	}

	$tm = f_persistentdocument_TransactionManager::getInstance();
	try
	{
		$tm->beginTransaction();

		$taskId = intval($arguments[0]);
		$runnableTask = task_persistentdocument_plannedtask::getInstanceById($taskId);

		$runnableTask->setUniqueExecutiondate(date_Calendar::getInstance($arguments[1]));
		$runnableTask->setNextrundate(null);
		$tm->getPersistentProvider()->updateDocument($runnableTask);

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