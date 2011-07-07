<?php
/**
 * task_PlannedfolderService
 * @package modules.task
 */
class task_PlannedfolderService extends generic_FolderService
{
	/**
	 * @var task_PlannedfolderService
	 */
	private static $instance;

	/**
	 * @return task_PlannedfolderService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return task_persistentdocument_plannedfolder
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_task/plannedfolder');
	}

	/**
	 * Create a query based on 'modules_task/plannedfolder' model.
	 * Return document that are instance of modules_task/plannedfolder,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_task/plannedfolder');
	}
	
	/**
	 * Create a query based on 'modules_task/plannedfolder' model.
	 * Only documents that are strictly instance of modules_task/plannedfolder
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_task/plannedfolder', false);
	}
	
	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param string[] $subModelNames
	 * @param integer $locateDocumentId null if use startindex
	 * @param integer $pageSize
	 * @param integer $startIndex
	 * @param integer $totalCount
	 * @return f_persistentdocument_PersistentDocument[]
	 */
	public function getVirtualChildrenAt($document, $subModelNames, $locateDocumentId, $pageSize, &$startIndex, &$totalCount)
	{
		$executionStatus = $document->getStatusFilter();	
		if ($locateDocumentId !== null)
		{
			$startIndex = 0;
			$query = task_PlannedtaskService::getInstance()->createQuery()
						->add(Restrictions::published())
						->addOrder(Order::asc('document_label'))
           				->setProjection(Projections::property('id', 'id')); 
			if (!empty($executionStatus))
			{
				$query->add(Restrictions::eq('executionStatus', $executionStatus));
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
			
			$query = task_PlannedtaskService::getInstance()->createQuery()
				->add(Restrictions::published())
				->setProjection(Projections::rowCount('countItems'));
			if (!empty($executionStatus))
			{
				$query->add(Restrictions::eq('executionStatus', $executionStatus));
			}
      		$resultCount = $query->findColumn('countItems');
			$totalCount = intval($resultCount[0]);
		}
		
		$query = task_PlannedtaskService::getInstance()->createQuery()
			->add(Restrictions::published())
			->addOrder(Order::asc('document_label'))
			->setFirstResult($startIndex)->setMaxResults($pageSize);
		if (!empty($executionStatus))
		{
			$query->add(Restrictions::eq('executionStatus', $executionStatus));
		} 		 
		return $query->find();
	}
	
	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		parent::preSave($document, $parentNodeId);
		$executionStatus = $document->getStatusFilter();
		if (empty($executionStatus))
		{
			$document->setLabel(LocaleService::getInstance()->transBO('m.task.document.plannedfolder.all-status'));
		}
		else
		{
			$label = LocaleService::getInstance()->transBO('m.task.document.plannedtask.status-' . $executionStatus, 
				array('ucf'));
				
			$document->setLabel(LocaleService::getInstance()->transBO('m.task.document.plannedfolder.def-status', 
				array(), array('executionStatus' => $label)));
		}
	}
}