<?php
/**
 * Defines the default error handler.
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: ErrorHandler.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ErrorHandler class
 *
 * Defines the default error handler. By default,
 * Warnings, notices, user errors, user warnings, user notices,
 * and recoverable errors halt execution.
 *
 * On error:
 * A nice template is displayed,
 * Error details are logged,
 * And an email with details is sent to the address in config[admin.emailAddress].
 *
 * @package    CrowdFusion
 */
class ErrorHandler
{
    protected $isDevelopmentEnv = false;
    protected $isCommandLine = false;
    protected $Email = false;
    protected $environment = '';
    protected $systemEmailAddress;
    protected $sendEmails;
    protected $sendEmailsFrom;
    protected $verbose = true;
    protected $multiline = false;

    protected $errorTemplate;
    protected $levels;

    /**
     * Constructs the error handler
     *
     * @param const   $errorReporting      If specified, we'll use this value for what errors are reported.
     * @param string  $errorSendEmailsFrom The email address used to send email errors from
     * @param string  $systemEmailAddress   The email address for the administrator
     * @param boolean $developmentMode     Set to true if we're running in the development environment
     * @param boolean $errorSendEmails     If true, then errors will send emails
     *                                      Default: true
     * @param boolean $errorVerbose        If true, include SERVER, HEADERS, FILES, POST, GET and SESSION variables in error notification output
     *                                      Default: true
     * @param string  $errorTemplate       If specified, we'll use this as the error template. This is displayed when errors occur.
     *                                      Default: null
     */
    public function __construct($errorReporting,
                                $errorSendEmailsFrom,
                                $systemEmailAddress,
                                $developmentMode,
                                $isCommandLine,
                                $errorRedeployOnError = false,
                                $errorSendEmails = true,
                                $errorVerbose = true,
                                $errorMultiline = false,
                                $errorTemplate = null
                                )
    {

        if(!empty($errorTemplate))
            $this->errorTemplate = $errorTemplate;

        $this->isDevelopmentEnv  = $developmentMode;
        $this->isCommandLine     = $isCommandLine;
        $this->redeployOnError   = $errorRedeployOnError;
        $this->systemEmailAddress = $systemEmailAddress;
        $this->sendEmails        = $errorSendEmails;
        $this->sendEmailsFrom    = $errorSendEmailsFrom;
        $this->verbose           = $errorVerbose;
        $this->multiline         = $errorMultiline;

        error_reporting($errorReporting);
        ini_set('log_errors', false);

        set_error_handler(array($this, 'handleErrors'));
        set_exception_handler(array($this, 'handleExceptions'));
        register_shutdown_function(array($this, 'shutdown'));
    }

    /**
     * Sets the Email object we'll use to send emails
     *
     * @param EmailInterface $Email The Email object
     *
     * @return void
     */
    public function setEmail(EmailInterface $Email)
    {
        $this->Email = $Email;
    }

    public function setSecurity(SecurityInterface $Security)
    {
        $this->Security = $Security;
    }

    public function setSiteService(SiteService $SiteService)
    {
        $this->SiteService = $SiteService;
    }

    public function setApplicationContext(ApplicationContext $ApplicationContext)
    {
        $this->ApplicationContext = $ApplicationContext;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * Defines the error handler that will be used when this class is created.
     *
     * Using error_log, this function will record the error.
     *
     * @param string $severity The severity of the error
     * @param string $message  The error message
     * @param string $filepath The file where the error was encountered
     * @param string $line     The line number where the error occured
     *
     * @return void|boolean
     */
    public function handleErrors($severity, $message, $filepath, $line)
    {
        if (error_reporting() == 0 || $severity == E_STRICT)
            return;

        if (false !== strpos($filepath, '/')) {
            $x = explode('/', $filepath);
            $filepath = $x[count($x)-3].'/'.$x[count($x)-2].'/'.end($x);
        }

        $mess = $this->getLevel($severity).'('.$severity.'):  '.$message. ' in '.$filepath.' on line '.$line;

        /*
         * shouldn't need to log the error here as the exception will handle the logging.
         */
        /*
        $context = (isset($_SERVER['CONTEXT']) ? $_SERVER['CONTEXT'] : 'none');
        if (array_key_exists('SITE', $_SERVER)) {
            $appName = (string) $_SERVER['SITE']['slug'];
            if ($appName === $context) {
                $appName = $this->SiteService->getAnchoredSite()->Slug;
            }
        } else {
            $appName = $_SERVER['SERVER_NAME'];
        }
        error_log("[{$this->environment}][$appName][$context][PHP_ERROR] " .  $mess);
        */

        if($severity == E_RECOVERABLE_ERROR)
            throw new Exception($mess);

        if (($severity & error_reporting()) == $severity) {
            if($this->redeployOnError && isset($this->ApplicationContext))
                $this->ApplicationContext->clearContextFiles();

            throw new Exception($mess);
        }

        return true;
    }

    public function shutdown()
    {
        if(is_null($e = error_get_last()) === false)
        {
            if(strpos($e['message'], 'DateTime::__construct') !== FALSE)
                return false;

            $mess = $this->getLevel($e['type']).'('.$e['type'].'):  '.$e['message']. ' in '.$e['file'].' on line '.$e['line'];
            $this->handleExceptions($mess);
        }
    }

    protected function getLevel($type)
    {
        if($this->levels == null)
        {
            $this->levels = array(
                E_ERROR             =>  'Fatal Error',
                E_WARNING           =>  'Warning',
                E_PARSE             =>  'Parse Error',
                E_NOTICE            =>  'Notice',
                E_CORE_ERROR        =>  'Core Error',
                E_COMPILE_ERROR     =>  'Compile Error',
                E_COMPILE_WARNING   =>  'Compile Warning',
                E_USER_ERROR        =>  'User Error',
                E_USER_WARNING      =>  'User Warning',
                E_USER_NOTICE       =>  'User Notice',
                E_RECOVERABLE_ERROR =>  'Recoverable Error',

                // Below are new in 5.3.0, so we're using the
                // integer value instead of the constant so
                // we don't get undefined constant errors in pre-5.3.0
                8192                =>  'Deprecated',
                16384               =>  'User Deprecated',
                4096                =>  'Recoverable Fatal Error',
                2048                =>  'Strict Warning'
            );
        }

        if (!isset($this->levels[$type])) {
            return "Error type [$type] Unknown";
        }

        return $this->levels[$type];
    }

    /**
     * Defines the exception handler to use.
     *
     * @param Exception $exception The exception to handle
     *
     * @return void
     */
    public function handleExceptions($exception)
    {
        $this->displayError($exception);
        $this->sendErrorNotification($exception);

        if(isset($this->ApplicationContext) && ($this->ApplicationContext->isOneOffRedeploy() || $this->redeployOnError))
            $this->ApplicationContext->clearContextFiles();
    }

    protected function displayError($exception)
    {
        if(!headers_sent())
            header("HTTP/1.1 500 Internal Server Error");

        if($exception instanceof Exception)
            $msg = $exception->getMessage();
        else
            $msg = (string)$exception;

        $clistr = "\n\n==== ERROR ====\n";
        $clistr.= $msg ."\n";

        if($exception instanceof SQLException)
            $clistr.= $exception->getSQL() ."\n";

        $clistr .= "==== ERROR ====\n\n";


        // We need to manually echo the exception information to the CLI if we're log aggregating.
        if($this->isCommandLine && $exception instanceof Exception) {
            $clistr .= "Code: ".$exception->getCode()."\n";
            $clistr .= "File: ".$exception->getFile()."\n";
            $clistr .= "Line: ".$exception->getLine()."\n";
            $clistr .= "Stack Trace: ".$exception->getTraceAsString()."\n\n";
        }

        $errorMsg = '<p>Error: ';
        $errorMsg.= str_replace("\n", "<br/>", @htmlspecialchars( $msg, ENT_QUOTES, 'UTF-8', false ));

        if($exception instanceof SQLException && $this->isDevelopmentEnv)
            $errorMsg.= "</p><p>SQL: <pre>".str_replace("\t", " ", str_replace("\n", "<br/>", $exception->getSQL()))."</pre>";

        $errorMsg .= '</p>';

        if($this->errorTemplate == null)
        {
            $this->errorTemplate    = '<html>
<head>
<title>System Error</title>
<style type="text/css">

body {
background-color:   #eee;
margin:             30px;
font-family:        Helvetica, Verdana, Sans-serif;
font-size:          12px;
color:              #333;
}

#content  {
position:           absolute;
border:             #333 1px solid;
background-color:   #fff;
padding:            17px 17px 8px 17px;
width:              750px;
left:               50%;
margin-left:        -375px;
}

h1 {
font-weight:        normal;
font-size:          16px;
color:              #B30000;
margin:             0 0 4px 0;
}

.hilight { color: red; font-weight: bold; }
</style>
</head>
<body>
    <div id="content">

    <h1>Unexpected Error Occurred</h1>

    <p><strong>The system administrator has been notified of the problem.</strong><p>
    %ErrorMessage%

    </div>
</body>
</html>';
        }

        if ($this->isDevelopmentEnv) {
            $str = str_replace('%ErrorMessage%', $errorMsg, $this->errorTemplate);
        } else {
            if (false !== strpos($errorMsg, 'POST Content-Length of')) {
                $str = str_replace('%ErrorMessage%', '<p class="hilight">The file you submitted is too large.</p>', $this->errorTemplate);
            } elseif (false !== strpos($errorMsg, 'Missing boundary in multipart/form-data')) {
                $str = str_replace('%ErrorMessage%', '<p class="hilight">Your device does not support forms with file uploads.</p>', $this->errorTemplate);
            } else {
                $str = str_replace('%ErrorMessage%', ($this->environment === 'prod' ? '' : $errorMsg), $this->errorTemplate);
            }
        }

        if ($this->isCommandLine) {
            echo($clistr);
        } else {
            echo($str);
        }
    }


    /**
     * Facing error notification methods.  Send the error notification specified by {@link $message}
     *
     * @param string $message       The message of the email. Can be instance of Exception or string
     * @param string $subject       If specified, use this as the subject for the email.
     * @param string $customMessage Any message you'd like to appear in the email
     *
     * @return void
     */
    public function sendErrorNotification($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->_sendErrorNotification('ERROR', $message, $subject, $customMessage, $extraRecipients);
    }

    public function sendWarningNotification($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->_sendErrorNotification('WARN', $message, $subject, $customMessage, $extraRecipients);
    }

    public function sendInfoNotification($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->_sendErrorNotification('INFO', $message, $subject, $customMessage, $extraRecipients);
    }

    public function sendDebugNotification($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->_sendErrorNotification('DEBUG', $message, $subject, $customMessage, $extraRecipients);
    }

    public function sendVendorErrorNotification($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->_sendErrorNotification('VENDOR_ERROR', $message, $subject, $customMessage, $extraRecipients);
    }


    /**
     * Facing error email methods.  Send the error email specified by {@link $message}
     *
     * @deprecated Since we now prefer log aggregation over sending error email, please prefer the "send____Notification"
     * forms of these functions, as it's a more accurate reflection of what is actually happening.
     *
     * @param string $message       The message of the email. Can be instance of Exception or string
     * @param string $subject       If specified, use this as the subject for the email.
     * @param string $customMessage Any message you'd like to appear in the email
     *
     * @return void
     */
    public function sendErrorEmail($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->sendErrorNotification($message, $subject, $customMessage, $extraRecipients);
    }
    /* @deprecated See comment on sendErrorEmail function. */
    public function sendWarningEmail($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->sendWarningNotification($message, $subject, $customMessage, $extraRecipients);
    }
    /* @deprecated See comment on sendErrorEmail function. */
    public function sendInfoEmail($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->sendInfoNotification($message, $subject, $customMessage, $extraRecipients);
    }
    /* @deprecated See comment on sendErrorEmail function. */
    public function sendDebugEmail($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->sendDebugNotification($message, $subject, $customMessage, $extraRecipients);
    }
    /* @deprecated See comment on sendErrorEmail function. */
    public function sendVendorErrorEmail($message, $subject = null, $customMessage = false, $extraRecipients = null) {
        $this->sendVendorErrorNotification($message, $subject, $customMessage, $extraRecipients);
    }


    /*
     * Internal error email method
     */
    protected function _sendErrorNotification($prefix, $message, $subject = null, $customMessage = false, $extraRecipients = null)
    {
        $c = '';

        if ($customMessage) {
            $c .= $customMessage."\n\n";
        }

        if ($message instanceof Exception) {

            $c .= get_class($message).": ".$message->getMessage()."\n";
            if($message instanceof SQLException)
                $c .= "SQL: ".$message->getSQL()."\n";
            $c .= "Code: ".$message->getCode()."\n";
            $c .= "File: ".$message->getFile()."\n";
            $c .= "Line: ".$message->getLine()."\n";
            $c .= "Stack Trace: ".$message->getTraceAsString()."\n\n";

        } else {
            $c .= str_replace("\t", "&nbsp;&nbsp;", $message)."\n\n";
        }

        $c .= "\nURL: ".URLUtils::fullUrl()."\n\n";

        if ($this->verbose) {
            $debugServerVars = array_intersect_key($_SERVER,array_flip(array(

                'SCRIPT_URL',
                'SCRIPT_URI',
                'ENVIRONMENT',
    //            'HTTP_X_FORWARDED_FOR',
    //            'HTTP_CLIENT_IP',
    //            'HTTP_HOST',
    //            'HTTP_REFERER',
    //            'HTTP_USER_AGENT',
    //            'HTTP_ACCEPT',
    //            'HTTP_ACCEPT_LANGUAGE',
    //            'HTTP_ACCEPT_ENCODING',
    //            'HTTP_COOKIE',
    //            'HTTP_CONNECTION',
                'PATH',
                'SERVER_SIGNATURE',
                'SERVER_SOFTWARE',
                'SERVER_NAME',
                'SERVER_ADDR',
                'SERVER_PORT',
                'REMOTE_ADDR',
                'DOCUMENT_ROOT',
                'SERVER_ADMIN',
                'SCRIPT_FILENAME',
                'REMOTE_PORT',
                'GATEWAY_INTERFACE',
                'SERVER_PROTOCOL',
                'REQUEST_METHOD',
                'QUERY_STRING',
                'REQUEST_URI',
                'SCRIPT_NAME',
                'PHP_SELF',
                'REQUEST_TIME',
                'DEVICE_VIEW',
                'DESIGN',
                'DOMAIN',
                'CONTEXT',
                'ROUTER_BASE',
                'MATCHED_ALIAS',
                'REWRITE_BASE',
                'DEPLOYMENT_BASE_PATH',
                'SITE',
                'SYSTEM_VERSION'

            )));

            $c .= "SERVER: ".print_r($this->Security->filterLoggedParameters($debugServerVars), true)."\n\n";

            $headers = array();
            foreach($_SERVER as $name => $value)
                if(strpos($name, 'HTTP_') === 0)
                    $headers[$name] = $value;

            if (!empty($headers))
                $c .= "HEADERS: ".print_r($this->Security->filterLoggedParameters($headers), true)."\n\n";

            if (isset($_FILES))
                $c .= "FILES: ".print_r($_FILES, true)."\n\n";

            if (isset($_POST))
                $c .= "POST: ".print_r($this->Security->filterLoggedParameters($_POST), true)."\n\n";

            if (isset($_GET))
                $c .= "GET: ".print_r($this->Security->filterLoggedParameters($_GET), true)."\n\n";

            if (isset($_SESSION))
                $c .= "SESSION: ".print_r($this->Security->filterLoggedParameters($_SESSION), true)."\n\n";
        }

        if ($message instanceof Exception) {
            if (!$subject) { $subject = '%k: %.100m'; }
            $subject = preg_replace_callback(
                '/%(\.(\d+))?([kmcfl%])/',
                function($match) use ($message) {
                    switch ($match[3]) {
                        case 'k': $out = get_class($message);    break;
                        case 'm': $out = $message->getMessage(); break;
                        case 'c': $out = $message->getCode();    break;
                        case 'f': $out = $message->getFile();    break;
                        case 'l': $out = $message->getLine();    break;
                        default:  $out = $match[3];
                    }
                    if (!empty($match[2])) {
                        $out = substr($out, 0, $match[2]);
                    }
                    return $out;
                },
                $subject
            );
        } else {
            if (!$subject) {
                $subject = substr($message, 0, 100);
            }
        }

        // don't send email about post max or missing boundary errors
        // there's nothing we can do code wise to fix those anyways
        $isPostContentLengthError = false;
        if (false !== strpos($c, 'POST Content-Length of')
            || false !== strpos($c, 'Missing boundary in multipart/form-data')) {
            $isPostContentLengthError = true;
            $prefix = 'WARN';
        }

        $context = (isset($_SERVER['CONTEXT']) ? $_SERVER['CONTEXT'] : 'none');
        if (array_key_exists('SITE', $_SERVER)) {
            $appName = (string) $_SERVER['SITE']['slug'];
            if ($appName === $context) {
                $appName = $this->SiteService->getAnchoredSite()->Slug;
            }
        } else {
            $appName = $_SERVER['SERVER_NAME'];
        }

        $deviceView = (isset($_SERVER['DEVICE_VIEW']) ? $_SERVER['DEVICE_VIEW'] : '');
        $design = (isset($_SERVER['DESIGN']) ? $_SERVER['DESIGN'] : '');

        if (!empty($deviceView) && !empty($design)) {
            $errorPrefix = "[{$this->environment}][$appName][$context][$deviceView:$design][$prefix]";
        } else {
            $errorPrefix = "[{$this->environment}][$appName][$context][$prefix]";
        }

        $subject = "$errorPrefix $subject";
        if (!$this->multiline) {
            $c = str_replace("\n", "<br/>", $c);
        }

        // Do standard PHP error logging
        error_log("$errorPrefix $c");

        // Bail out if we shouldn't be sending emails.
        if (!$this->sendEmails
            || empty($this->systemEmailAddress)
            || empty($this->Email)
            || $isPostContentLengthError)
        {
            return;
        }

        $recipients = array($this->systemEmailAddress);
        if (!empty($extraRecipients)) {
            if (!is_array($extraRecipients)) {
                $recipients[] = $extraRecipients;
            } else {
                $recipients = array_merge($recipients, $extraRecipients);
            }
        }

        // emails always need line breaks replaced
        if ($this->multiline) {
            $c = str_replace("\n", "<br/>", $c);
        }

        $body = '<p><font face="&#39;courier new&#39;, monospace">' . $c . '</font></p>';

        $this->Email->clear();
        $this->Email->from($this->sendEmailsFrom);
        foreach ($recipients as $recipient) {
            $this->Email->to($recipient);
        }
        $this->Email->subject($subject);
        $this->Email->body($body);
        $this->Email->altMessage($c);

        if (is_a($this->Email, 'EmailTagInterface')) {
            $this->Email->tag('error');
        }

        $this->Email->send();
    }

}
