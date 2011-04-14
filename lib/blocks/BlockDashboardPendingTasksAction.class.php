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
		
		$tasks = TaskHelper::getPendingTasksForCurrentUser();
		$widget = array();
		foreach ($tasks as $task)
		{
			try 
			{
				$document = DocumentHelper::getDocumentInstance($task->getWorkitem()->getDocumentid());
			}
			catch (Exception $e)
			{
				Framework::warn(__METHOD__ . ' no document found with id ' . $task->getWorkitem()->getDocumentid() .  ' for the task with id ' . $task->getId());
				continue;
			}
			
			$lastModification = date_Calendar::getInstance($task->getCreationdate());

			if ($lastModification->isToday())
			{
				$status = f_Locale::translateUI('&modules.uixul.bo.datePicker.Calendar.today;') . date_DateFormat::format(date_Converter::convertDateToLocal($lastModification), ', H:i');
			}
			else
			{
				$status = date_DateFormat::format(date_Converter::convertDateToLocal($lastModification), 'l j F Y, H:i');
			}
			$attr = array(
				'id' => $task->getId(),
				'taskLabel' => $task->getLabelAsHtml(),
				'dialog' => $task->getDialogName(),
				'module' => $task->getModule(),
				'status' => ucfirst($status),
				'documentId' => $document->getId(),
				'documentLabel' => f_util_HtmlUtils::textToHtml($document->getPersistentModel()->isLocalized() ? $document->getLabelForLang($task->getLang()) : $document->getLabel()),
				'documentModel' => str_replace('/', '_', $document->getDocumentModelName()),
				'documentModule' => $document->getDocumentModel()->getModuleName(),
				'comment' => $task->getCommentaryAsHtml(),
				'author' => ucfirst($task->getDescriptionAsHtml())
			);
			$widget[] = $attr;
		}
		$request->setAttribute('tasks', $widget);
	}
}