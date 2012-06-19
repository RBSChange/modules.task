<?php
class task_PlannedtaskScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return task_persistentdocument_plannedtask
	 */
	protected function initPersistentDocument()
	{
		return task_PlannedtaskService::getInstance()->getNewDocumentInstance();
	}
}