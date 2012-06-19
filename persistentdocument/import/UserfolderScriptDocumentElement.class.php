<?php
/**
 * task_UserfolderScriptDocumentElement
 * @package modules.task.persistentdocument.import
 */
class task_UserfolderScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return task_persistentdocument_userfolder
	 */
	protected function initPersistentDocument()
	{
		return task_UserfolderService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_task/userfolder');
	}
}