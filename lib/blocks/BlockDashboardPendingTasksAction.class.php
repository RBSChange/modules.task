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
			$document = DocumentHelper::getDocumentInstance($task->getWorkitem()->getDocumentid());
			
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
				'taskLabel' => $task->getLabel(),
				'dialog' => $task->getDialogName(),
				'module' => $task->getModule(),
				'status' => ucfirst($status),
				'documentLabel' => $document->getPersistentModel()->isLocalized() ? $document->getLabelForLang($task->getLang()) : $document->getLabel()
			);
			$widget[] = $attr;
		}
		$request->setAttribute('tasks', $widget);
	}
}