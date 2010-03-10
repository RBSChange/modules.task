<?php
if (!defined('WEBEDIT_HOME'))
{
	// Install path is webapp/bin/tasks/<myTaskPath>.php
	if (!isset($_SERVER["PWD"]) || !isset($_SERVER["SCRIPT_FILENAME"]))
	{
		throw new Exception("Could not discover WEBEDIT_HOME: PWD = " . var_export($_SERVER["PWD"]) . ", SCRIPT_FILENAME = " . var_export($_SERVER["SCRIPT_FILENAME"]));
	}
	if ($_SERVER["SCRIPT_FILENAME"][0] == '/')
	{
		$thisPath = $_SERVER["SCRIPT_FILENAME"];
	}
	else
	{
		$thisPath = $_SERVER["PWD"] . DIRECTORY_SEPARATOR . $_SERVER["SCRIPT_FILENAME"];
	}
	define('WEBEDIT_HOME', realpath(dirname($thisPath) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'));
}

chdir(WEBEDIT_HOME);

require_once WEBEDIT_HOME . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'Framework.php';
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());
Controller::newInstance("controller_ChangeController");
if ($_SERVER['argc'] == 2)
{
	$taskId = $_SERVER['argv'][1];
	$runnableTask = DocumentHelper::getDocumentInstance(intval($taskId));
	task_PlannedTaskRunner::executeSystemTask($runnableTask);
}