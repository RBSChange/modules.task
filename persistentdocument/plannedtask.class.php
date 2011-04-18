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
	 * @return float minutes
	 */
	public function getDurationAverage()
	{
		$durations = $this->getMetaMultiple("task_durations");
		$sum = array_sum($durations);
		$count = count($durations);
		return ($sum/$count)/60;
	}
	
	/**
	 * @return float minutes
	 */
	public function getLastDuration()
	{
		$durations = $this->getMetaMultiple("task_durations");
		return ($durations[count($durations)-1])/60;
	}
	
	/**
	 * @return boolean
	 */
	public function canBeAutoUnlock()
	{
		$nextRunDate = date_Calendar::getInstance($this->getNextrundate());
		$durationMaxDate = date_Calendar::getInstance($this->getNextrundate());
		$durationMaxDate->add(date_Calendar::MINUTE, ($this->getMaxduration() * 2));
		if (date_Calendar::getInstance()->isBetween($nextRunDate, $durationMaxDate))
		{
			return false;
		}
		else
		{
			return true;
		}	
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
	 * @param Integer $minute 0..59 or -1 for random value
	 */
	public function setMinute($minute)
	{
		if ($minute == -1)
		{
			$minute = rand(0, 59);	
		}
		parent::setMinute($minute);
	}
	
	/**
	 * @param Integer $hour 0..23 or -1 for random value
	 */
	public function setHour($hour)
	{
		if ($hour == -1)
		{
			$hour = rand(0, 23);	
		}
		parent::setHour($hour);
	}
	
	/**
	 * @param Integer $dayofmonth 1..31 or -1 for random value
	 */
	public function setDayofmonth($dayofmonth)
	{
		if ($dayofmonth == -1)
		{
			$dayofmonth = rand(1, 28);	
		}
		parent::setDayofmonth($dayofmonth);
	}
	
	/**
	 * @param Integer $month 1..12 or -1 for random value
	 */
	public function setMonth($month)
	{
		if ($month == -1)
		{
			$month = rand(1, 12);	
		}
		parent::setMonth($month);
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