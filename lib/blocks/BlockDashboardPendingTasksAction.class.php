<?php
/**
 * task_BlockDashboardPendingTasksAction
 * @package modules.task.lib.blocks
 */
class task_BlockDashboardPendingTasksAction extends dashboard_BlockDashboardAction
{	
	/**
	 * @param f_mvc_Request $request
	 * @param boolean $forEdition
	 */
	protected function setRequestContent($request, $forEdition)
	{
		if ($forEdition)
		{
			return;
		}
		
		$ls = LocaleService::getInstance();
		$tasks = TaskHelper::getPendingTasksForCurrentUser();
		$widget = array();
		foreach ($tasks as $task)
		{
			/* @var $task task_persistentdocument_usertask */
			try 
			{
				$document = DocumentHelper::getDocumentInstance($task->getWorkitem()->getDocumentid());
			}
			catch (Exception $e)
			{
				Framework::warn(__METHOD__ . ' no document found with id ' . $task->getWorkitem()->getDocumentid() .  ' for the task with id ' . $task->getId());
				continue;
			}
						
			$attr = array(
				'id' => $task->getId(),
				'taskLabel' => $task->getLabelAsHtml(),
				'dialog' => $task->getDialogName(),
				'module' => $task->getModule(),
				'status' => date_Formatter::toDefaultDateTimeBO($task->getUICreationdate()),
				'documentId' => $document->getId(),
				'documentLabel' => f_util_HtmlUtils::textToHtml($document->getPersistentModel()->isLocalized() ? $document->getLabelForLang($task->getLang()) : $document->getLabel()),
				'documentModel' => str_replace('/', '_', $document->getDocumentModelName()),
				'documentModule' => $document->getPersistentModel()->getModuleName(),
				'comment' => $task->getCommentaryAsHtml(),
				'author' => ucfirst($task->getDescriptionAsHtml())
			);
			$widget[] = $attr;
		}
		$request->setAttribute('tasks', $widget);
	}
}