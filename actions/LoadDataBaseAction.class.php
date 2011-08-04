<?php
/**
 * task_LoadDataBaseAction
 * @package modules.task.actions
 */
abstract class task_LoadDataBaseAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$task = DocumentHelper::getDocumentInstance($request->getParameter('taskId'));
		$lang = $task->getWorkitem()->getCase()->getlang();
		try
		{
			RequestContext::getInstance()->beginI18nWork($lang);
			$workDocument = DocumentHelper::getDocumentInstance($task->getWorkitem()->getDocumentid());
			
			$originalDocument = null;
			if (!is_null($workDocument->getCorrectionofid()) && $workDocument->getCorrectionofid() != 0)
			{
				$originalDocument = DocumentHelper::getDocumentInstance($workDocument->getCorrectionofid());				
			}
			
			$result['taskId'] = $task->getId();
			$result['taskDescription'] = $task->getDescription();
			$result['taskComment'] = $task->getCommentary();
			$result = $this->completeResult($result, $workDocument, $originalDocument, $task);
			RequestContext::getInstance()->endI18nWork();
		}
		catch (Exception $e)
		{
			RequestContext::getInstance()->endI18nWork($e);
		}
		
		return $this->sendJSON($result);
	}
	
	/**
	 * @param Array $result
	 * @param f_persistentdocument_PersistentDocument $workDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param tast_persistentdocument_task $task
	 * @return Array
	 */
	protected function completeResult($result, $workDocument, $originalDocument, $task)
	{
		$result['workDocument'] = $this->getInfoForDocument($workDocument);
		if ($originalDocument !== null)
		{
			$result['originalDocument'] = $this->getInfoForDocument($originalDocument);
			$differencies = array();
			foreach ($result['workDocument'] as $key => $value)
			{
				$differencies[$key] = ($value != $result['originalDocument'][$key]);
			}
			$result['differences'] = $differencies;
		}
		return $result;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return Array
	 */
	protected abstract function getInfoForDocument($document);
}