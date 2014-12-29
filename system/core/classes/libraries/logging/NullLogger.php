<?php

class NullLogger extends AbstractLogger
{
    public function log($message, $level = LOG_INFO)
    {
        // noop
        return null;
    }
}