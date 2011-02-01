<?php
/**
 * task_persistentdocument_plannedtask
 * @package task
 */
class task_persistentdocument_plannedtask extends task_persistentdocument_plannedtaskbase
{
	/**
	 * @var integer
	 */
	private $periodUnit;
	
	/**
	 * @return Integer or null
	 */
	public function getPeriodUnit()
	{
		if ($this->periodUnit === null)
		{
			if ($this->getMinute() === null)
			{
				$this->periodUnit = date_Calendar::MINUTE;
			}
			else if ($this->getHour() === null)
			{
				$this->periodUnit = date_Calendar::HOUR;
			}
			else if ($this->getDayofmonth() === null)
			{
				$this->periodUnit = date_Calendar::DAY;
			}
			else if ($this->getMonthofyear() === null)
			{
				$this->periodUnit = date_Calendar::MONTH;
			}
			
			else if ($this->getYear() === null)
			{
				$this->periodUnit = date_Calendar::YEAR;
			}
		}
		return $this->periodUnit;
	}
	
	/**
	 * @return Integer
	 */
	public function getPeriodValue()
	{
		return 1;
	}
	
	/**
	 * @param date_Calendar $date
	 */
	public function setUniqueExecutiondate($date)
	{
		$this->setYear($date->getYear());
		$this->setMonthofyear($date->getMonth());
		$this->setDayofmonth($date->getDay());
		$this->setHour($date->getHour());
		$this->setMinute($date->getMinute());
	}
	
	/**
	 * @return Boolean
	 */
	public function getHasFailed()
	{
		return $this->hasFailed;
	}
	
	/**
	 * @param Boolean $hasFailed
	 */
	public function setHasFailed($hasFailed)
	{
		$this->hasFailed = $hasFailed;
	}
	
	/**
	 * @param date_Calendar $date
	 */	
	public function reSchedule($date)
	{
		$this->setUniqueExecutiondate($date);
		$this->periodUnit = null;
		$this->setNextrundate(null);
	}

	/**
	 * @var Boolean
	 */
	private $hasFailed = false;
	
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		$nodeAttributes['isrunninglabel'] = $this->getIsrunningLabel();
		$nodeAttributes['isrunning'] = (int)$this->getIsrunning();
	}
	
	/**
	 * @return Boolean
	 */
	public function isLocked()
	{
		if ($this->getIsrunning())
		{
			$nextRunDate = date_Calendar::getInstance($this->getNextrundate());
			$durationMaxDate = date_Calendar::getInstance($this->getNextrundate());
			$durationMaxDate->add(date_Calendar::MINUTE, $this->getMaxduration());
			
			if (date_Calendar::getInstance()->isBetween($nextRunDate, $durationMaxDate))
			{
				return false;
			}
			else 
			{
				return true;
			}
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * @return string
	 */
	public function getIsrunningLabel()
	{
		if ($this->getIsrunning())
		{
			return f_Locale::translate('&modules.task.document.plannedtask.Yes;');
		}
		else 
		{
			return f_Locale::translate('&modules.task.document.plannedtask.No;');
		}	
	}
}