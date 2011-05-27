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
	
	const MAX_AUTOUNLOCK = 3;
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';
	const STATUS_RUNNING = 'running';
	const STATUS_LOCKED = 'locked';
	
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
	protected function getPublishedNodetaskQuery()
	{
		$query = $this->createQuery()->add(Restrictions::published());
		if (defined('NODE_NAME'))
		{
			$query->add(Restrictions::orExp(Restrictions::isNull('node'), Restrictions::eq('node', NODE_NAME)));
		}
		return $query;
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getBySystemtaskclassname($className)
	{
		return $this->createQuery()->add(Restrictions::eq('systemtaskclassname', $className))->find();
	}
	
	/**
	 * 
	 * @param task_persistentdocument_plannedtask $className
	 */
	public function getRunnableBySystemtaskclassname($className)
	{
		return $this->createQuery()->add(Restrictions::published())
			->add(Restrictions::eq('systemtaskclassname', $className))
			->add(Restrictions::in('executionStatus', array(self::STATUS_SUCCESS, self::STATUS_FAILED)))->find();
	}
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getTasksToStart()
	{
		$now = date_Calendar::getInstance()->toString();
		$query  = $this->getPublishedNodetaskQuery();
		$query->add(Restrictions::le('nextrundate', $now))->find();
		$query->add(Restrictions::in('executionStatus', array(self::STATUS_SUCCESS, self::STATUS_FAILED)));
		return $query->find();
	}

	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getTasksToAutoUnlock()
	{
		$now = date_Calendar::getInstance()->toString();
		$query  = $this->getPublishedNodetaskQuery();
		$query->add(Restrictions::le('unlockCount', self::MAX_AUTOUNLOCK))->find();
		$query->add(Restrictions::eq('executionStatus', self::STATUS_LOCKED));
		return $query->find();
	}	
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */	
	public function getTasksToLock()
	{
		$query  = $this->getPublishedNodetaskQuery();
		$query->add(Restrictions::eq('executionStatus', self::STATUS_RUNNING));
		$tasks = $query->find();
		$result = array();
		foreach ($tasks as $task) 
		{
			if ($task instanceof task_persistentdocument_plannedtask) 
			{
				$maxDuration = (is_integer($task->getMaxduration())) ? $task->getMaxduration() : 60;
				$date = date_Calendar::getInstance()->add(date_Calendar::MINUTE, - $maxDuration)->toString();
				if ($task->getRunningDate() <= $date)
				{
					$result[] = $task;
				}
			}
		}
		return $result;
	}	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param string $newStatus
	 * @return task_persistentdocument_plannedtask
	 */
	protected function updateExecutionStatus($task, $newStatus)
	{
		$task->setExecutionStatus($newStatus);
		$task->setExecutionStatusDate(date_Calendar::getInstance()->toString());
		f_persistentdocument_PersistentProvider::getInstance()->updateDocument($task);
		return $task;
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function start($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		
		try 
		{
			$this->tm->beginTransaction();
			$now = date_Calendar::getInstance()->toString();
			$task->setLastrundate($now);
			$task->setRunningDate($now);
			$this->updateExecutionStatus($task, self::STATUS_RUNNING);
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function end($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		
		try 
		{
			$this->tm->beginTransaction();
			$now = date_Calendar::getInstance();
			$task->setRunningDate($now->toString());
			$task->setLastSuccessDate($now->toString());
			
			$lastrundate  = date_Calendar::getInstance($task->getLastrundate());
			$duration = ($now->getTimestamp() - $lastrundate->getTimestamp());
			
			$this->updateDurationAvg($task, $duration);
			
			$task->setTotalSuccessCount(intval($task->getTotalSuccessCount() + 1));
			$task->setUnlockCount(0);
			
			$this->rescheduleIfNecesseary($task);
			
			$this->updateExecutionStatus($task, self::STATUS_SUCCESS);
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param string message
	 */
	public function error($task, $message)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel() . " - " . $message);
		}
		
		try 
		{
			$this->tm->beginTransaction();
			$now = date_Calendar::getInstance();
			$task->setRunningDate($now->toString());
			$lastrundate  = date_Calendar::getInstance($task->getLastrundate());
			$duration = ($now->getTimestamp() - $lastrundate->getTimestamp());+
			$this->updateDurationAvg($task, $duration);
			
			$task->setTotalErrorCount(intval($task->getTotalErrorCount() + 1));
			$task->setUnlockCount(0);
			
			//$this->rescheduleIfNecesseary($task);
			
			$this->updateExecutionStatus($task, self::STATUS_FAILED);
			
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			$action = 'run-failed.plannedtask';
			UserActionLoggerService::getInstance()->addUserDocumentEntry($user, $action, $task, array('message' => $message), 'task');

			//TODO Send ERROR Notification
			
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}
	
	/**
	 * 
	 * @param task_persistentdocument_plannedtask $task
	 * @param integer $duration
	 * @return double
	 */
	protected function updateDurationAvg($task, $duration)
	{
		$nb = intval($task->getTotalSuccessCount()) + intval($task->getTotalErrorCount());
		$avg = ($nb * doubleval($task->getDurationAvg()) + $duration) / ($nb + 1);
		$task->setDurationAvg($avg);
	}
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function lock($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		
		try 
		{
			$this->tm->beginTransaction();
			$now = date_Calendar::getInstance();
			$task->setRunningDate($now->toString());
			$lastrundate  = date_Calendar::getInstance($task->getLastrundate());
			$duration = ($now->getTimestamp() - $lastrundate->getTimestamp());+
			$this->updateDurationAvg($task, $duration);
			$task->setTotalLockCount(intval($task->getTotalLockCount() + 1));
			
			$unlockCount = intval($task->getUnlockCount()) + 1;
			$task->setUnlockCount($unlockCount);
			
			$this->updateExecutionStatus($task, self::STATUS_LOCKED);
			
			
			$action = 'lock.plannedtask';
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			UserActionLoggerService::getInstance()->addUserDocumentEntry($user, $action, $task, array('unlockCount' => $unlockCount), 'task');
			
			if ($unlockCount > self::MAX_AUTOUNLOCK)
			{
				//TODO Send Fianl LOCK Notification
			}
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function unlock($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		try 
		{
			$this->tm->beginTransaction();	
			$task->setUnlockCount(0);	
			$this->updateExecutionStatus($task, self::STATUS_FAILED);
			
			$action = 'unlock.plannedtask';
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			UserActionLoggerService::getInstance()->addUserDocumentEntry($user, $action, $task, array(), 'task');
			
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}	
	
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function autoUnlock($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		try 
		{
			$this->tm->beginTransaction();			
			$this->updateExecutionStatus($task, self::STATUS_FAILED);
			
			$action = 'autounlock.plannedtask';
			$user = users_UserService::getInstance()->getCurrentBackEndUser();
			$unlockCount = intval($task->getUnlockCount());
			UserActionLoggerService::getInstance()->addUserDocumentEntry($user, $action, $task, array('unlockCount' => $unlockCount), 'task');
			
			$this->tm->commit();			
		} 
		catch (Exception $e) 
		{
			$this->tm->rollBack($e);
		}
	}		
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	public function ping($task)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . " " . $task->getId()  ." - " .  $task->getLabel());
		}
		
		try 
		{
			$this->tm->beginTransaction();		
			$now = date_Calendar::getInstance()->toString();	
			$task->setRunningDate($now);
			if ($task->isModified())
			{
				$this->pp->updateDocument($task);
			}
			$this->tm->commit();			
		} 
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
	}	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param date_Calendar $date
	 */
	public function setUniqueExecutiondate($task, $date)
	{
		try 
		{
			$this->tm->beginTransaction();	
			$task->setUniqueExecutiondate($date);
			if ($task->isModified())
			{
				$this->pp->updateDocument($task);
			}
			$this->tm->commit();			
		} 
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}		
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param date_Calendar $date
	 */
	public function reSchedule($task, $date)
	{
		try 
		{
			$this->tm->beginTransaction();
			$task->setNextrundate($date->toString());
			if ($task->isModified())
			{
				$this->pp->updateDocument($task);
			}
			$this->tm->commit();			
		} 
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}		
	}	
	
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @param Integer $parentId
	 */
	public function preSave($task, $parentId)
	{
		$defined = ($task->getYear() !== null);
		
		$defined = $defined || ($task->getMonthofyear() !== null);
		if ($defined && $task->getMonthofyear() === null)
		{
			$task->setMonthofyear(rand(1, 12));
		}
		$defined = $defined || ($task->getDayofmonth() !== null);
		if ($defined && $task->getDayofmonth() === null)
		{
			$task->setDayofmonth(rand(1, 28));
		}
		$defined =  $defined || ($task->getHour() !== null);
		if ($defined && $task->getHour() === null)
		{
			$task->setHour(rand(0, 23));
		}
		$defined =  $defined || ($task->getMinute() !== null);
		if ($defined && $task->getMinute() === null)
		{
			$task->setMinute(rand(0, 59));
		}
		
		if ($task->getNextrundate() == null)
		{
			if ($task->getPeriodUnit() !== null)
			{
				$task->setNextrundate($this->getNextOccurenceDate($task));
			}
			else
			{
				$task->setNextrundate($task->getUniqueNextDate());	
			}
		}
	}
	
	
	
	/**
	 * @param task_persistentdocument_plannedtask $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preInsert($document, $parentNodeId)
	{
		if ($document->getNode() === null)
		{
			$val = Framework::getConfigurationValue('modules/task/default-node');
			if (!empty($val))
			{
				$document->setNode($val);
			}
		}
	}

	/**
	 * @param task_persistentdocument_plannedtask $task
	 */
	protected function rescheduleIfNecesseary($task)
	{
		if ($task->getNextrundate() < date_Calendar::getInstance()->toString())
		{
			if ($task->getPeriodUnit() !== null)
			{
				$task->setNextrundate($this->getNextOccurenceDate($task));
			}
			else
			{
				$task->setPublicationstatus('FILED');
				if ($task->getTreeId())
				{
					TreeService::getInstance()->deleteNodeById($task->getId());
				}
			}
		}
	}
	
	/**
	 * @param task_persistentdocument_plannedtask $task
	 * @return string
	 */
	protected function getNextOccurenceDate($task)
	{
		$nextRunDate = date_Calendar::getInstance($task->getLastrundate())
			->setSecond(rand(0, 59));
		
		$periodUnit = $task->getPeriodUnit();
		$nextRunDate->add($periodUnit, $task->getPeriodValue());	
		if ($periodUnit > date_Calendar::MINUTE)
		{
			$nextRunDate->setMinute($task->getMinute());
			if ($periodUnit > date_Calendar::HOUR)
			{
				$nextRunDate->setHour($task->getHour());
				if ($periodUnit > date_Calendar::DAY)
				{
					$nextRunDate->setDay($task->getDayofmonth());
					if ($periodUnit > date_Calendar::MONTH)
					{
						$nextRunDate->setMonth($task->getMonthofyear());
					}
				}
			}
			
		}
		return $nextRunDate->toString();
	}
	
	
	/**
	 * @return task_persistentdocument_plannedtask[]
	 */
	public function getLockedTasks()
	{
		return $this->createQuery()->add(Restrictions::eq('executionStatus', self::STATUS_LOCKED))
			->find();	
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
		$data['properties']['executionStatus'] = $document->getExecutionStatusLabel();
		$data['properties']['executionStatusDate'] = $document->getUIExecutionStatusDate();
		$data['properties']['periodUnit'] = $document->getPeriodUnitLabel();
		$data['properties']['unlockCount'] = $document->getUnlockCount();
		
		$data['properties']['nextrundate'] = $document->getUINextrundate();
		$data['properties']['node'] = $document->getNode();
		
		
		$data['executioninfos']['lastrundate'] = $document->getUILastrundate();
		$durationAverage = doubleval($document->getDurationAvg()) / 60;
		$durationAverage = $durationAverage < 1 ? '< 1' : round($durationAverage, 2);
		$durationAverage .=  ' ' . $ls->transBO('f.unit.minutes');
		$data['executioninfos']['durationaverage'] = $durationAverage;
		
		$lastSuccessDate = $document->getUILastSuccessDate();
		if ($lastSuccessDate !== null)
		{
			$data['executioninfos']['lastSuccessDate'] = $lastSuccessDate;
		}
		$data['executioninfos']['totalSuccessCount'] = $document->getTotalSuccessCount();
		$data['executioninfos']['totalErrorCount'] = $document->getTotalErrorCount();
		
		$data['executioninfos']['totalLockCount'] = $document->getTotalLockCount();
		
		return $data;
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
		$nodeAttributes['isLocked'] = $document->isLocked() ? '1' : '0';
	}
}