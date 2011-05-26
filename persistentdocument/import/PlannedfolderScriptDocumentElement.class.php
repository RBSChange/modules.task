<?php
/**
 * task_PlannedfolderScriptDocumentElement
 * @package modules.task.persistentdocument.import
 */
class task_PlannedfolderScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return task_persistentdocument_plannedfolder
     */
    protected function initPersistentDocument()
    {
    	return task_PlannedfolderService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_task/plannedfolder');
	}
}