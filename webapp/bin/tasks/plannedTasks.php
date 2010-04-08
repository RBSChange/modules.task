<?php
// In apache user's crontab, please add:
// # run hourChange task every hour
// */5 * * * * php ${WEBEDIT_HOME}/bin/tasks/plannedTasks.php

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
		task_PlannedTaskRunner::main();
	}
	
	function startNoLock()
	{
		$this->checkWebeditHome();
		chdir(WEBEDIT_HOME);
		$this->execute(null);
	}
}

$task = new f_tasks_PlannedTasksTask();
$task->startNoLock();