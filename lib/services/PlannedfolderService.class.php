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

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
//		parent::preInsert($document, $parentNodeId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
//		parent::postInsert($document, $parentNodeId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
//		parent::preUpdate($document, $parentNodeId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
//		parent::postUpdate($document, $parentNodeId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
//		parent::postSave($document, $parentNodeId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//		parent::preDelete($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//		parent::preDeleteLocalized($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//		parent::postDelete($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//		parent::postDeleteLocalized($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param task_persistentdocument_plannedfolder $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//		parent::onCorrectionActivated($document, $args);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//		parent::tagAdded($document, $tag);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//		parent::tagRemoved($document, $tag);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param task_persistentdocument_plannedfolder $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//		parent::onMoveToStart($document, $destId);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//		parent::onDocumentMoved($document, $destId);
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param task_persistentdocument_plannedfolder $newDocument
	 * @param task_persistentdocument_plannedfolder $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param task_persistentdocument_plannedfolder $newDocument
	 * @param task_persistentdocument_plannedfolder $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * @param website_UrlRewritingService $urlRewritingService
	 * @param task_persistentdocument_plannedfolder $document
	 * @param website_persistentdocument_website $website
	 * @param string $lang
	 * @param array $parameters
	 * @return f_web_Link | null
	 */
//	public function getWebLink($urlRewritingService, $document, $website, $lang, $parameters)
//	{
//		return null;
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'task', 'template' => 'Task-Inc-PlannedfolderResultDetail');
//	}

	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param task_persistentdocument_plannedfolder $document
	 * @param String[] $propertiesName
	 * @param Array $datas
	 */
//	public function addFormProperties($document, $propertiesName, &$datas)
//	{
//	}
		
}