<?php
/**
 * Email Implementation using PHPMailer_v5.0.0
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
 * @version     $Id: EmailWithPHPMailer.php 2012 2010-02-17 13:34:44Z ryans $
 */

require_once PATH_SYSTEM . '/vendors/PHPMailer_v5.0.0/class.phpmailer.php';

/**
 * Email Implementation using PHPMailer_v5.0.0
 *
 * @package     CrowdFusion
 */
class EmailWithPHPMailer implements EmailInterface
{
    protected $mailer = null;
    protected $Logger;
    protected $testingOnly;
    protected $emailConfig;
    protected $emailMode;

    /**
     * Sets various parameters on our mailer object.
     *
     * Example:
     * <code>
     *      $this->_setParams(array('username', 'host'), $params);
     * </code>
     *
     * @param array   $params   A key-value array containing configuration values. For example: array('port' => 25, 'host' => 'localhost', ...)
     * @param array   $keys     A list of keys to extract from the {@link $params} and set on our mailer object
     * @param boolean $required If set to true an exception will be thrown if the specified key doesn't exist in the params
     *
     * @return void
     **/
    private function _setParams($params, $keys, $required = true)
    {
        foreach ( $keys as $key ) {

            if (!array_key_exists($key, $params)) {
                if ($required == true) {
                    throw new EmailException("Required configuration key '{$key}' was not found.");
                } else {
                    continue;
                }
            }

            switch ( strtolower($key) ) {
            case 'charset':
                $this->mailer->CharSet = $params['charset'];
                break;

            case 'content-type':
                $this->mailer->ContentType = $params['content-type'];
                break;

            case 'encoding':
                $this->mailer->Encoding = $params['encoding'];
                break;

            case 'helo':
                $this->mailer->Helo = $params['helo'];
                break;

            case 'host':
                $this->mailer->Host = $params['host'];
                break;

            case 'hostname':
                $this->mailer->Hostname = $params['hostname'];
                break;

            case 'message-id':
                $this->mailer->MessageId = $params['message-id'];
                break;

            case 'password':
                $this->mailer->Password = $params['password'];
                break;

            case 'port':
                $this->mailer->Port = $params['port'];
                break;

            case 'priority':
                $this->mailer->Priority = $params['priority'];
                break;

            case 'smtp-debug':
                // $this->_check_key($params, 'smtp-debug');
                $this->mailer->SMTPDebug = json_decode($params['smtp-debug']);
                break;

            case 'smtp-keepalive':
                // $this->_check_key($params, 'smtp-keepalive');
                $this->mailer->SMTPKeepAlive = json_decode($params['smtp-keepalive']);
                break;

            case 'smtp-secure':
                $this->mailer->SMTPSecure = $params['smtp-secure'];
                break;

            case 'sendmail':
                $this->mailer->Sendmail = $params['sendmail'];
                break;

            case 'timeout':
                $this->mailer->Timeout = $params['timeout'];
                break;

            case 'username':
                $this->mailer->Username = $params['username'];
                break;

            default:
                throw new EmailException("Parameter '{$key}' not supported.");
            }
        }
    }

    /**
     * Requires all listed params
     *
     * @param array $params The params
     * @param array $keys   An array of keys to require
     *
     * @return void
     * @throws EmailException if params don't exist
     */
    private function _requireParams($params, $keys)
    {
        $this->_setParams($params, $keys, true);
    }


    /**
     * Fills all listed params if they exist
     *
     * @param array $params The params
     * @param array $keys   An array of keys to fill
     *
     * @return void
     */
    private function _fillParams($params, $keys)
    {
        $this->_setParams($params, $keys, false);
    }


    /**
     * Creates an email instance
     *
     * @param LoggerInterface $Logger           The logger
     * @param boolean         $emailMode        Specifies the type of email server to use.
     *                                             Possible values: sendmail|mail|smtp|smtp-no-auth|qmail
     *                                             Default: mail
     * @param array           $emailConfig      A key-value array of parameters to configure the mail system
     * @param boolean         $emailTestingOnly If true, then email is in test-only mode
     */
    public function __construct(LoggerInterface $Logger, $emailMode = 'mail', $emailConfig = array(), $emailTestingOnly = false)
    {
        $this->testingOnly = $emailTestingOnly;
        $this->Logger      = $Logger;
        $this->emailConfig = $emailConfig;
        $this->emailMode = $emailMode;

        $this->clear();

    }

    /**
     * Resets the email system, clearing all sender, receiver and message information
     *
     * @return $this
     **/
    public function clear()
    {
        try {
            // Standardize the params array
            $params = array_change_key_case((array)$this->emailConfig, CASE_LOWER);

            // Initialize the mailer
            $this->mailer = new PHPMailer(true);

            // Set any global options in the params
            // All of these are optional, but if we have them, we should fill them.
            $this->_fillParams($params, array('content-type', 'encoding', 'hostname',
                                             'message-id', 'priority', 'smtp-debug',
                                             'sendmail'), false);

            switch ( strtolower($this->emailMode) ) {
            case 'sendmail':
                $this->mailer->IsSendmail();
                break;

            case 'mail':
                $this->mailer->IsMail();
                break;

            case 'smtp':
                $this->mailer->IsSMTP();
                $this->mailer->SMTPAuth = true;
                $this->_requireParams($params, array('host', 'username', 'password'));
                $this->_fillParams($params, array('port', 'smtp-secure', 'timeout'));
                break;

            case 'smtp-no-auth':
                $this->mailer->IsSMTP();
                $this->_requireParams($params, array('host'));
                $this->_fillParams($params, array('port', 'smtp-secure', 'timeout'));
                break;

            case 'qmail':
                $this->mailer->IsQmail();
                break;

            default:
                throw new EmailException("Invalid Email type: '{$this->emailMode}'");
            }

        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }
        return $this;
    }

    /**
     * Sets the email address of the sender, including the name if specified.
     *
     * This function is required to be called before an email can be sent.
     *
     * @param string $emailAddress The email address of the sender
     * @param string $name         (Optional) If not blank, will be used as the human-readable name of the sender
     *
     * @return $this
     **/
    public function from($emailAddress, $name = '')
    {
        try {
            $this->mailer->setFrom($emailAddress, $name);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets a to address for your email. This function can be called multiple times
     * to add multiple recipients to the email.
     *
     * This function must be called before an email can be sent
     *
     * @param string $emailAddress The email address of the recipient
     * @param string $name         (Optional) If not blank, will be used as the human-readable name of the recipient
     *
     * @return $this
     **/
    public function to($emailAddress, $name = '')
    {
        try {
            $this->mailer->AddAddress($emailAddress, $name);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets the reply-to address
     *
     * @param string $emailAddress The email address to use as your reply address
     *
     * @return $this
     **/
    public function replyTo($emailAddress)
    {
        try {
            $this->mailer->AddReplyTo($emailAddress);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets a CC address for your email. This function can be called multiple times
     * to add multiple CC recipients to the email.
     *
     * @param string $emailAddress The email address of the CC recipient
     * @param string $name         (Optional) If not blank, will be used as the human-readable name of the CC recipient
     *
     * @return $this
     **/
    public function cc($emailAddress, $name = '')
    {
        try {
            $this->mailer->AddCC($emailAddress, $name);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets a BCC address for your email. This function can be called multiple times
     * to add multiple BCC recipients to the email.
     *
     * @param string $emailAddress The email address of the BCC recipient
     * @param string $name         (Optional) If not blank, will be used as the human-readable name of the BCC recipient
     *
     * @return $this
     **/
    public function bcc($emailAddress, $name = '')
    {
        try {
            $this->mailer->AddBCC($emailAddress, $name);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets the subject for your email.
     *
     * @param string $subject The subject for the email.
     *
     * @return $this
     **/
    public function subject($subject)
    {
        try {
            $this->mailer->Subject = $subject;
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets the email message body
     *
     * @param string $text The text to use as the message body
     *
     * @return $this
     **/
    public function body($text)
    {
        try {
            $this->mailer->MsgHTML($text);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sets the alternative email message body.
     *
     * This is an optional message string which can be used if you send HTML
     * formatted email. It lets you specify an alternative message with no HTML
     * formatting which is added to the header string for people who do not accept
     * HTML email.
     *
     * @param string $altMessage The alternative message to use
     *
     * @return $this
     **/
    public function altMessage($altMessage)
    {
        try {
            $this->mailer->IsHTML();
            $this->mailer->AltBody = $altMessage;
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Attaches the file specified to the email.
     *
     * @param string $filename The absolute filename of the file to attach
     *
     * @return void
     **/
    public function attach($filename)
    {
        try {
            $this->mailer->AddAttachment($filename);
        } catch (phpmailerException $e) {
            throw new EmailException($e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Sends the email.
     *
     * @throws EmailException when something is wrong
     * @return TRUE if email sent successfully
     **/
    public function send()
    {

        if ($this->Logger->isEnabled()) {
            if($this->mailer->IsError())
                $this->Logger->debug("ERROR OCCURRED");

            $this->Logger->debug("Headers:\n".$this->mailer->CreateHeader());
            $this->Logger->debug("Body:\n".$this->mailer->CreateBody());
        }

        if (!$this->testingOnly) {
            try {
                $this->mailer->Send();
            } catch (phpmailerException $e) {
                throw new EmailException($e->getMessage(), $e->getCode());
            }

        }
        return true;
    }

}
