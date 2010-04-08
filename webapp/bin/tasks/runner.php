<?php
if (!defined('WEBEDIT_HOME'))
{
	define('WEBEDIT_HOME', dirname(dirname(dirname(realpath(__FILE__)))));
}

chdir(WEBEDIT_HOME);
require_once WEBEDIT_HOME . '/framework/Framework.php';
$rq = RequestContext::getInstance();
$rq->setLang($rq->getDefaultLang());
Controller::newInstance("controller_ChangeController");
if ($_SERVER['argc'] == 2)
{
	$taskId = $_SERVER['argv'][1];
	$runnableTask = DocumentHelper::getDocumentInstance(intval($taskId));
	task_PlannedTaskRunner::executeSystemTask($runnableTask);
}