<?php

/**
 * The date factory is used to create new date objects and allows for
 * developers to customize the date system.
 *
 * Provides the concept of a LocalDate and a StorageDate where LocalDate
 * represents a Date object in the "local" timezone (see the config file),
 * and StorageDate represents a date object in the "storage" timezone
 * (or the timezone of the server where data is stored.)
 *
 */
class DateFactory
{
    /** @var \DateTimeZone */
    protected $storageTimeZone;

    /** @var \DateTimeZone */
    protected $localTimeZone;

    /** @var \DateTimeZone */
    protected $gmtTimeZone;

    /**
     * Creates a new DateFactory
     *
     * @param DateTimeZone $datesLocalTimeZone   The local timezone for the user
     * @param DateTimeZone $datesStorageTimeZone The time zone used for storing data in the db
     */
    public function __construct($datesLocalTimeZone, $datesStorageTimeZone)
    {
        date_default_timezone_set($datesLocalTimeZone);
        $this->localTimeZone   = new DateTimeZone($datesLocalTimeZone);
        $this->storageTimeZone = new DateTimeZone($datesStorageTimeZone);
        $this->gmtTimeZone = new DateTimeZone('GMT');
    }

    /**
     * Returns a new StorageDate object
     *
     * @param mixed $date The initialization string used to create the date
     *
     * @return Date the created object
     */
    public function newStorageDate($date = false)
    {
        return $this->newDate($this->getStorageTimeZone(), $date);
    }

    /**
     * Returns a new LocalDate object
     *
     * @param mixed $date The initialization string used to create the date
     *
     * @return Date
     */
    public function newLocalDate($date = false)
    {
        return $this->newDate($this->getLocalTimeZone(), $date);
    }

    /**
     * Returns a new LocalDate object
     *
     * @param mixed $date The initialization string used to create the date
     *
     * @return Date
     */
    public function newGMTDate($date = false)
    {
        return $this->newDate($this->getGMTTimeZone(), $date);
    }

    /**
     * Creates a new date in the desired timezone
     *
     * @param string $desiredTZ The desired timezone in which to return the date
     * @param mixed $date       An initialization string used to define the date.
     *
     * @return Date
     *
     * @throws \DateException
     */
    protected function newDate($desiredTZ, $date = false)
    {
        if ($date === false) {
            $date = 'now';
        }

        try {
            $newDate = new Date(is_numeric('' . $date) ? '@' . $date : $date, $desiredTZ);

            // if the string TZ was not the desired TZ, we must convert it
            //$temp = new DateTime(is_numeric(''.$date)?'@'.$date:$date);
            //if(intVal($temp->format('Z')) != $desiredTZ->getOffset(new DateTime()))
            $newDate->setTimezone($desiredTZ);
        } catch (Exception $e) {
            throw new DateException($e);
        }

        unset($date);
        unset($desiredTZ);

        return $newDate;
    }

    /**
     * Transforms the date specified into the localtimezone or {@link $newTimeZone} if given
     *
     * @param Date         $date        The date to transform
     * @param DateTimeZone $newTimeZone If specified, the date will be returned in this timezone.
     *                                   Otherwise, the Local Time Zone used in the constructor will be used.
     *
     * @return Date
     */
    public function toLocalDate(Date $date, DateTimeZone $newTimeZone = null)
    {
        $tz = $this->getLocalTimeZone();
        if (!is_null($newTimeZone)) {
            $tz = $newTimeZone;
        }
        $date->setTimezone($tz);
        return $date;
    }

    /**
     * Transforms the date specified into the proper time in the Storage TimeZone
     *
     * @param Date $date The date to convert
     *
     * @return Date The date in the storage timezone
     */
    public function toStorageDate(Date $date)
    {
        $date->setTimezone($this->getStorageTimeZone());
        return $date;
    }

    /**
     * Transforms the date specified into the proper time in the Storage TimeZone
     *
     * @param Date $date The date to convert
     *
     * @return Date
     */
    public function toGMTDate(Date $date)
    {
        $date->setTimezone($this->getGMTTimeZone());
        return $date;
    }

    /**
     * Returns the internal storage timezone used
     *
     * @return DateTimeZone
     */
    public function getStorageTimeZone()
    {
        return $this->storageTimeZone;
    }

    /**
     * Returns the local timezone
     *
     * @return DateTimeZone
     */
    public function getLocalTimeZone()
    {
        return $this->localTimeZone;
    }

    /**
     * Returns the GMT timezone
     *
     * @return DateTimeZone the GMT timezone
     */
    public function getGMTTimeZone()
    {
        return $this->gmtTimeZone;
    }
}