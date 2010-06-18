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
			
			while (!date_GregorianCalendar::staticIsLeapYear($year) || ($year == $nextRunDate->getYear() && date_GregorianCalendar::staticIsLeapYear($year) && ($nextRunDate->getMonth() > 2 || $nextRunDate->getDay() > 29)))
			{
				$year++;
			}
		}
		
		if ($day > 28 && $month === null)
		{
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
				$nextRunDate->setDay(1);
				$nextRunDate->setHour(0);
				$nextRunDate->setMinute(0);
				if ($nextRunDate->getMonth() > $month)
				{
					$nextRunDate->add(date_Calendar::YEAR, 1);
				}
			}
			$nextRunDate->setMonth($month);
		}
		
		if ($day !== null)
		{
			if ($nextRunDate->getDay() !== $day)
			{
				$nextRunDate->setHour(0);
				$nextRunDate->setMinute(0);
				if ($nextRunDate->getDay() > $day)
				{
					$nextRunDate->add(date_Calendar::MONTH, 1);
				
				}
			}
			
			$nextRunDate->setDay($day);
		
		}
		if ($hour !== null)
		{
			if ($nextRunDate->getHour() != $hour)
			{
				if ($nextRunDate->getHour() > $hour)
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
			$plannedTask->save();
		}
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
		$data = parent::getResume($document, $forModuleName, $allowedSections);
		$data['properties']['isrunning'] = $document->getIsrunning() ? f_Locale::translateUI("&modules.uixul.bo.general.Yes;") : f_Locale::translateUI("&modules.uixul.bo.general.No;");
		$data['properties']['lastrundate'] = $document->getUILastrundate();
		$data['properties']['nextrundate'] = $document->getUINextrundate();
		$data['properties']['node'] = $document->getNode();
		return $data;
	}
}