<?php
/**
 * @package framework.builder.webapp.bin.tasks
 */
define('WEBEDIT_HOME', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR  . '..' . DIRECTORY_SEPARATOR));
chdir(WEBEDIT_HOME);
if (!file_exists(WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'webapp' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'site_is_disabled'))
{
	require_once WEBEDIT_HOME . "/framework/Framework.php";
	
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
else 
{
	echo('WARNING: Planned tasks skipped: '.time()." (site disabled)\n"); 
}