<?php
class task_UsertaskScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return task_persistentdocument_usertask
	 */
	protected function initPersistentDocument()
	{
		return task_UsertaskService::getInstance()->getNewDocumentInstance();
	}
}