<?php
/**
 * @package modules.task
 * @method task_UserfolderService getInstance()
 */
class task_UserfolderService extends generic_FolderService
{
	/**
	 * @return task_persistentdocument_userfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_task/userfolder');
	}

	/**
	 * Create a query based on 'modules_task/userfolder' model.
	 * Return document that are instance of modules_task/userfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_task/userfolder');
	}
	
	/**
	 * Create a query based on 'modules_task/userfolder' model.
	 * Only documents that are strictly instance of modules_task/userfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_task/userfolder', false);
	}
	
	/**
	 * @param task_persistentdocument_userfolder $document
	 * @param string[] $subModelNames
	 * @param integer $locateDocumentId null if use startindex
	 * @param integer $pageSize
	 * @param integer $startIndex
	 * @param integer $totalCount
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		$publicationstatus = $document->getStatusFilter();
		if ($locateDocumentId !== null)
		{
			$startIndex = 0;
			$query = task_UsertaskService::getInstance()->createQuery()
						->addOrder(Order::asc('document_label'))
		   				->setProjection(Projections::property('id', 'id')); 
			if (!empty($publicationstatus))
			{
				$query->add(Restrictions::eq('publicationstatus', $publicationstatus));
			}
		   	$idsArray = $query->find();
		   	$totalCount = count($idsArray);
		   	foreach ($idsArray as $index => $row)
		   	{					
		   		if ($row['id'] == $locateDocumentId)
		   		{
		   			$startIndex = $index - ($index % $pageSize);
		   			break;
		   		}
		   	}	 
		}
		else
		{
			
			$query = task_UsertaskService::getInstance()->createQuery()
				->setProjection(Projections::rowCount('countItems'));
			if (!empty($publicationstatus))
			{
				$query->add(Restrictions::eq('publicationstatus', $publicationstatus));
			}
	  		$resultCount = $query->findColumn('countItems');
			$totalCount = intval($resultCount[0]);
		}
		
		$query = task_UsertaskService::getInstance()->createQuery()
			->addOrder(Order::asc('document_label'))
			->setFirstResult($startIndex)->setMaxResults($pageSize);
		if (!empty($publicationstatus))
		{
			$query->add(Restrictions::eq('publicationstatus', $publicationstatus));
		} 		 
		return $query->find();
	}
	
	/**
	 * @param task_persistentdocument_userfolder $document
	 * @param integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		$publicationstatus = $document->getStatusFilter();
		if (empty($publicationstatus))
		{
			$document->setLabel(LocaleService::getInstance()->trans('m.task.document.userfolder.all-status'));
		}
		else
		{
			$label = LocaleService::getInstance()->trans('f.persistentdocument.status.' . strtolower($publicationstatus), array('ucf'));
				
			$document->setLabel(LocaleService::getInstance()->trans('m.task.document.userfolder.def-status', 
				array(), array('publicationstatus' => $label)));
		}
	}
}