<?php

class Date extends DateTime implements Serializable
{
    protected $str;

    /**
     * Creates the Date object with the specified time and timezone
     *
     * @param string $time     String in a format accepted by strtotime(), defaults to "now".
     * @param string $timezone Time zone of the time.
     */
    public function __construct($time, $timezone = null)
    {
        if (!is_null($timezone)) {
            parent::__construct($time, $timezone);
        } else {
            parent::__construct($time);
        }
        $this->str = $this->__toString();
    }

    /**
     * Returns the date value in seconds since the epoch.
     *
     * @return integer seconds since epoch
     */
    public function toUnix()
    {
        return intVal($this->format('U'));
    }

    /**
     * Returns the date in a format compatible with mySQL queries
     *
     * @return string The formatted date
     */
    public function toMySQLDate()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * @see DateTime::modify
     * @link http://us3.php.net/manual/en/datetime.modify.php
     *
     * @param string $modify String in a relative format accepted by strtotime().
     *
     * @return Date The current instance. Makes it useful for chaining
     */
    public function modify($modify)
    {
        parent::modify($modify);
        return $this;
    }

    /**
     * Returns the date in one of the following formats: rfc1123, rfc1036, or asctime
     *
     * EXAMPLES-------
     *
     * rfc1123:
     *  Wed, 24 Jun 2009 08:56:18 GMT
     *
     * rfc1036:
     *  Wednesday, 24-Jun-09 08:56:45 GMT
     *
     * asctime:
     *  Wed Jun 24 08:57:06
     *
     * @param string $type One of: rfc1123, rfc1036, or asctime
     *
     * @return string
     *
     * @throws \Exception
     */
    public function toRFCDate($type = 'rfc1123')
    {
        $type = strtolower($type);
        $this->setTimezone(new DateTimeZone('UTC'));

        if ($type == 'rfc1123') {
            return substr($this->format('r'), 0, -5) . 'GMT';
        } else if ($type == 'rfc1036') {
            return $this->format('l, d-M-y H:i:s ') . 'GMT';
        } elseif ($type == 'asctime') {
            return $this->format('D M j H:i:s');
        } else {
            throw new Exception('Type parameter for toRFCDate() must be one of: rfc1123, rfc1036 or asctime');
        }
    }

    /**
     * Returns the date in RFC-3339 format
     *
     * @return string The date in RFC-3339 FORMAT
     */
    public function __toString()
    {
        return $this->format(DATE_RFC3339);
    }

    /**
     * Returns the date as a string
     *
     * @return string the date in a string that can be serialized.
     */
    public function serialize()
    {
        return $this->__toString();
    }

    /**
     * Creates a date object from a serialized version of the date object
     *
     * @param string $serialized The serialized date object
     *
     * @return Date the unserialized date
     */
    public function unserialize($serialized)
    {
        $this->__construct($serialized);
    }
}
