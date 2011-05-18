<?php
/**
 * @date Wed, 06 Aug 2008 09:19:58 +0000
 * @author intstaufl
 * @package modules.task
 */
class task_PlannedtaskService extends f_persistentdocument_DocumentService
{
	const MINUTELY = 1;
	const HOURLY = 2;
	const DAILY = 3;
	const MONTHLY = 3;
	
	/**
	 * @var task_PlannedtaskService
	 */
	private static $instance;
	
	/**
	 * @return task_PlannedtaskService
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
	 * @return task_persistentdocument_plannedtask
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_task/plannedtask');
	}
	
	/**
	 * Create a query based on 'modules_task/plannedtask' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_task/plannedtask');
	}
	
	/**
	 * @return f_persistentdocument_criteria_Query
	 */
	protected function getPublishedNonRunningTasksQuery()
	{
		$query = $this->createQuery()->add(Restrictions::published())->add(Restrictions::ne('isrunning', true));
		if (defined('NODE_NAME'))
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('node'), Restrictions::eq('node', NODE_NAME)));
		}
		return $query;
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getPublishedTasksToRun()
	{
		$query = $this->createQuery()->add(Restrictions::published());
		if (defined('NODE_NAME'))
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('node'), Restrictions::eq('node', NODE_NAME)));
		}
		return $query->add(Restrictions::le('nextrundate', date_Calendar::getInstance()->toString()))->find();
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param Integer $parentId
	 */
	public function preSave($task, $parentId)
	{
		$someIsDefined = ($task->getMinute() !== null)
			|| ($task->getHour() !== null)
			|| ($task->getDayofmonth() !== null)
			|| ($task->getMonthofyear() !== null)
			|| ($task->getYear() !== null);
		if ($someIsDefined && $task->getMinute() === null)
		{
			$task->setMinute(rand(0, 59));
			if ($task->getHour() === null)
			{
				$task->setHour(rand(0, 23));
				if ($task->getDayofmonth() === null)
				{
					$task->setDayofmonth(rand(1, 28));
					if ($task->getMonthofyear() === null)
					{
						$task->setMonthofyear(rand(1, 12));
					}
				}
			}
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $document
	 * @return date_Calendar
	 */
	public function getNextOccurenceDate($document)
	{
		$year = $document->getYear();
		$month = $document->getMonthofyear();
		$day = $document->getDayofmonth();
		$hour = $document->getHour();
		$minute = $document->getMinute();
		
		$periodUnit = $document->getPeriodUnit();
		$periodValue = $document->getPeriodValue();
		
		if ($periodUnit === null)
		{
			$runDate = date_Calendar::getInstance()->setYear($year)->setMonth($month)->setDay($day)->setHour($hour)->setMinute($minute);
			if ($document->getNextrundate() === null)
			{
				return $runDate;
			}
			else if ($runDate->belongsToFuture())
			{
				return $runDate;
			}
			return null;
		}
		
		$nextRunDate = date_GregorianCalendar::getInstance()->setSecond(0);
		
		if ($month == 2 && $day == 29)
		{
			if ($year === null)
			{
				$year = $nextRunDate->getYear();
			}
			
			// Look for the next leap year.
			while (!date_GregorianCalendar::staticIsLeapYear($year) || ($year == $nextRunDate->getYear() && date_GregorianCalendar::staticIsLeapYear($year) && ($nextRunDate->getMonth() > 2 || $nextRunDate->getDay() > 29)))
			{
				$year++;
			}
		}
		
		if ($day > 28 && $month === null)
		{
			// Look for the next month with enough days.
			while ($nextRunDate->getDaysInMonth() < $day)
			{
				$nextRunDate->add(date_Calendar::MONTH, 1);
			}
		}
		
		if ($year !== null)
		{
			if ($nextRunDate->getYear() !== $year)
			{
				$nextRunDate->setYear($year);
				$nextRunDate->setMonth(1);
				$nextRunDate->setDay(1);
				$nextRunDate->setHour(0);
				$nextRunDate->setMinute(0);
			}
			$nextRunDate->setYear($year);
		}
		
		if ($month !== null)
		{
			if ($nextRunDate->getMonth() > $month)
			{
				if ($periodUnit != date_Calendar::YEAR && $nextRunDate->getMonth() > $month)
				{
					$nextRunDate->add(date_Calendar::YEAR, 1);
				}
				$nextRunDate->setDay(1);
				$nextRunDate->setHour(0);
				$nextRunDate->setMinute(0);
			}
			$nextRunDate->setMonth($month);
		}
		
		if ($day !== null)
		{
			if ($nextRunDate->getDay() !== $day)
			{
				if ($periodUnit != date_Calendar::MONTH && $nextRunDate->getDay() > $day)
				{
					$nextRunDate->add(date_Calendar::MONTH, 1);
				}
				$nextRunDate->setHour(0);
				$nextRunDate->setMinute(0);
			}			
			$nextRunDate->setDay($day);
		
		}
		if ($hour !== null)
		{
			if ($nextRunDate->getHour() != $hour)
			{
				if ($periodUnit != date_Calendar::DAY && $nextRunDate->getHour() > $hour)
				{
					$nextRunDate->add(date_Calendar::DAY, 1);
				}
				$nextRunDate->setMinute(0);
			}
			$nextRunDate->setHour($hour);
		}
		
		if ($minute !== null)
		{
			if ($nextRunDate->getMinute() != $minute)
			{
				$nextRunDate->setMinute(0);
			}
			$nextRunDate->setMinute($minute);
		}
		
		$nextRunDate->add($periodUnit, $periodValue);
		if ($nextRunDate->belongsToPast())
		{
			return null;
		}
		return $nextRunDate;
	
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getRunnableTasks()
	{
		return $this->getPublishedNonRunningTasksQuery()->add(Restrictions::le('nextrundate', date_Calendar::getInstance()->toString()))->find();
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getRunnableBySystemtaskclassname($className)
	{
		return $this->getPublishedNonRunningTasksQuery()->add(Restrictions::eq('systemtaskclassname', $className))->find();
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getBySystemtaskclassname($className)
	{
		return $this->createQuery()->add(Restrictions::eq('systemtaskclassname', $className))->find();
	}
	
	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param task_persistentdocument_plannedtask $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if ($document->isPublished() && $oldPublicationStatus == "ACTIVE")
		{
			$document->setNextrundate($this->getNextOccurenceDate($document));
			$document->save();
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $plannedTask
	 */
	public function rescheduleIfNecesseary($plannedTask)
	{
		try 
		{
			$this->tm->beginTransaction();
			$plannedTask->setIsrunning(false);
			if (!$plannedTask->getHasFailed())
			{
				$plannedTask->setLastrundate(date_Calendar::now());
			}
			$nextRunDate = $this->getNextOccurenceDate($plannedTask);
			if ($nextRunDate === null)
			{
				$this->file($plannedTask->getId());
				$ts = TreeService::getInstance();
				$treeNode = $ts->getInstanceByDocument($plannedTask);
				if ($treeNode !== null)
				{
					$ts->deleteNode($treeNode);
				}
			}
			else
			{
				$plannedTask->setNextrundate($nextRunDate);
				$this->pp->updateDocument($plannedTask);
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param string $startedBefore
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getRunningTasks($startedBefore = null)
	{
		$query = $this->createQuery()->add(Restrictions::eq('isrunning', true))->addOrder(Order::desc('lastrundate'));
		if ($startedBefore !== null)
		{
			$query->add(Restrictions::lt('lastrundate', $startedBefore));
		}
		return $query->find();
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getLockedTasks()
	{
		$query = $this->createQuery()->add(Restrictions::eq('isrunning', true))->find();
		$lockedTasks = array();
		
		foreach ($query as $runningTask)
		{
			$nextRunDate = date_Calendar::getInstance($runningTask->getNextrundate());
			$durationMaxDate = date_Calendar::getInstance($runningTask->getNextrundate());
			$durationMaxDate->add(date_Calendar::MINUTE, $runningTask->getMaxduration());
			if (!date_Calendar::getInstance()->isBetween($nextRunDate, $durationMaxDate))
			{
				$lockedTasks[] = $runningTask;
			}
		}
		
		return $lockedTasks;		
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::getResume()
	 *
	 * @param task_persistentdocument_plannedtask $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$ls = LocaleService::getInstance();
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$data['properties']['isrunning'] = $document->getIsrunning() ? $ls->transBO("modules.uixul.bo.general.Yes") : $ls->transBO("modules.uixul.bo.general.No");
		$data['properties']['lastrundate'] = $document->getUILastrundate();
		$data['properties']['nextrundate'] = $document->getUINextrundate();
		$data['properties']['node'] = $document->getNode();
		
		$durationAverage = $document->getDurationAverage();
		if ($durationAverage !== null)
		{
			$durationAverage = $durationAverage < 1 ? '< 1' : round($durationAverage, 2);
			$durationAverage .=  ' ' . $ls->transBO('f.unit.minutes');
		}
		else
		{
			$durationAverage = $ls->transBO('m.uixul.bo.doceditor.empty-field-content');
		}
		
		$lastDuration = $document->getLastDuration();
		if ($lastDuration !== null)
		{
			$lastDuration = $lastDuration < 1 ? '< 1' : round($lastDuration, 2);
			$lastDuration .=  ' ' . $ls->transBO('f.unit.minutes');
		}
		else
		{
			$lastDuration = $ls->transBO('m.uixul.bo.doceditor.empty-field-content');
		}
		
		$data['durationinfos']['durationaverage'] = $durationAverage;
		$data['durationinfos']['lastduration'] = $lastDuration;
		return $data;
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @return boolean
	 */
	public function run($task)
	{
		$result = false;
		try 
		{
			$this->tm->beginTransaction();
			$task->setIsrunning(true);
			if ($task->isModified())
			{
				$this->pp->updateDocument($task);
				$result = true;
			}
			else 
			{
				Framework::warn(__METHOD__ . ' Task ' . $task->__toString() . ' already running!');
			}
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
		return $result;
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $document
	 * @param String[] $propertiesName
	 * @param Array $datas
	 */
	public function addFormProperties($document, $propertiesName, &$datas)
	{
		if (in_array('extraeditparamsjson', $propertiesName))
		{
			$datas['extraeditparamsjson'] = array(
				'minute' => $document->getMinute() !== null,
				'hour' => $document->getHour() !== null,
				'dayofmonth' => $document->getDayofmonth() !== null,
				'monthofyear' => $document->getMonthofyear() !== null,
				'year' => $document->getYear() !== null,
				'node' => false
			);
			
			if (ModuleService::getInstance()->isInstalled('clustersafe'))
			{
				$datas['extraeditparamsjson']['node'] = true;
				$datas['nodes'] = array();
				$nodes = clustersafe_WebnodeService::getInstance()->createQuery()
				->setProjection(Projections::groupProperty('label', 'label'))
				->findColumn('label');
				
				foreach ($nodes as $nodeName) {
					$datas['nodes'][$nodeName] = $nodeName;
				}
			}
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $document
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	public function addTreeAttributes($document, $moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['isrunninglabel'] = $document->getIsrunningLabel();
		$nodeAttributes['isrunning'] = (int)$document->getIsrunning();
	}
}