<?php
// In apache user's crontab, please add:
// # run hourChange task every hour
// */5 * * * * php ${WEBEDIT_HOME}/webapp/bin/tasks/plannedTasks.php

require_once("BaseTask.php");

class f_tasks_PlannedTasksTask extends f_tasks_BaseTask
{
	function __construct()
	{
		parent::__construct("plannedTasks");
	}

	protected function execute($previousRunTime)
	{
		$this->loadFramework();
		Controller::newInstance("controller_ChangeController");

		if ($_SERVER['argc'] == 2)
		{
			$taskId = $_SERVER['argv'][1];
			$runnableTask = DocumentHelper::getDocumentInstance(intval($taskId));
			task_PlannedTaskRunner::executeSystemTask($runnableTask);
		}
		else
		{
			task_PlannedTaskRunner::main();
		}
	}
}

$task = new f_tasks_PlannedTasksTask();
$task->start();