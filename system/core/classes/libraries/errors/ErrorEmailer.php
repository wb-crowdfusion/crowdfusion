<?php

/*
 * This class is **deprecated** in favour of the email methods on the
 * ErrorHandler class (sendErrorEmail, sendWarningEmail, & al.).
 * All references to it in any plugin or application code should be removed.
 */
class ErrorEmailer
{
    protected $ErrorHandler;
    public function setErrorHandler(ErrorHandler $ErrorHandler)
    {
        $this->ErrorHandler = $ErrorHandler;
    }

    /*
     *
     */
    public function send($name, Exception $e, $info = null)
    {
        $this->ErrorHandler->sendErrorEmail($e, $name, $info);
    }
}
