<?php
/**
 * Email
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
 * @version     $Id: Email.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Email
 *
 * @package     CrowdFusion
 */
class Email implements EmailInterface
{

    protected $Logger;
    protected $emailConfig;
    protected $emailMode;
    protected $charset;
    protected $message;
    protected $mailer;

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
    public function __construct(LoggerInterface $Logger, $charset, $emailMode = 'mail', $emailConfig = array())
    {
        $this->Logger      = $Logger;
        $this->emailConfig = $emailConfig;
        $this->emailMode = $emailMode;

        $this->charset = $charset;

    }

    protected function getMailer() {

        if(empty($this->mailer))
        {
            require_once PATH_SYSTEM."/vendors/Swift-4.0.4/lib/swift_required.php";

            switch($this->emailMode)
            {

                case 'smtp':

                    //Create the Transport
                    $transport = Swift_SmtpTransport::newInstance($this->emailConfig['host'], $this->emailConfig['port']);

                    if(!empty($this->emailConfig['user']))
                        $transport->setUsername($this->emailConfig['user']);

                    if(!empty($this->emailConfig['password']))
                        $transport->setPassword($this->emailConfig['password']);

                    if(!empty($this->emailConfig['timeout']))
                        $transport->setTimeout($this->emailConfig['timeout']);

                    if(!empty($this->emailConfig['encryption']))
                        $transport->setEncryption($this->emailConfig['encryption']);

                    $this->mailer = Swift_Mailer::newInstance($transport);
                    break;


                case 'sendmail':

                    $transport = Swift_SendmailTransport::newInstance(!empty($this->emailConfig['pathToSendmail'])?$this->emailConfig['pathToSendmail']:'/usr/sbin/sendmail -bs');

                    $this->mailer = Swift_Mailer::newInstance($transport);
                    break;

                default:
                case 'mail':

                    $transport = Swift_MailTransport::newInstance();

                    $this->mailer = Swift_Mailer::newInstance($transport);
                    break;

            }
        }
        return $this->mailer;

    }

    public function setEmailTestingOnly($emailTestingOnly)
    {
        $this->testingOnly = $emailTestingOnly;
    }


    /**
     * Resets the email system, clearing all sender, receiver and message information
     *
     * @return $this
     **/
    public function clear()
    {

        $this->getMailer();

        $this->message = Swift_Message::newInstance();
        $this->message->setCharset($this->charset);

        return $this;
    }

    /**
     * Sets the email address of the sender, including the name if specified.
     *
     * This function is required to be called before an email can be sent.
     *
     * @param string $emailAddress The email address of the sender
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the sender
     *
     * @return $this
     **/
    public function from($emailAddress, $name = null){

        $this->message->setFrom(array((string)$emailAddress=>(string)$name));
        return $this;
    }

    /**
     * Sets a to address for your email. This function can be called multiple times
     * to add multiple recipients to the email.
     *
     * This function must be called before an email can be sent
     *
     * @param string $emailAddress The email address of the recipient
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the recipient
     *
     * @return $this
     **/
    public function to($emailAddress, $name = null)
    {
        $this->message->addTo((string)$emailAddress, (string)$name);
        return $this;
    }

    /**
     * Sets the reply-to address
     *
     * @param string $emailAddress The email address to use as your reply address
     *
     * @return $this
     **/
    public function replyTo($emailAddress, $name = null)
    {
        $this->message->setReplyTo((string)$emailAddress, (string)$name);
        return $this;
    }

    /**
     * Sets a CC address for your email. This function can be called multiple times
     * to add multiple CC recipients to the email.
     *
     * @param string $emailAddress The email address of the CC recipient
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the CC recipient
     *
     * @return $this
     **/
    public function cc($emailAddress, $name = null)
    {
        $this->message->addCc((string)$emailAddress, (string)$name);
        return $this;
    }

    /**
     * Sets a BCC address for your email. This function can be called multiple times
     * to add multiple BCC recipients to the email.
     *
     * @param string $emailAddress The email address of the BCC recipient
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the BCC recipient
     *
     * @return $this
     **/
    public function bcc($emailAddress, $name = null)
    {
        $this->message->addBcc((string)$emailAddress, (string)$name);
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
        $this->message->setSubject((string)$subject);
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
        $this->message->addPart($text, 'text/html');
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
        $this->message->setBody($altMessage);
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
        $this->message->attach(Swift_Attachment::fromPath($filename));
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

            $this->Logger->debug($this->message->toString());

        }


        //Now check if Swift actually sends it
        if (!$this->testingOnly) {

            //Now check if Swift actually sends it
            $this->getMailer()->send($this->message);

        }
        return $this;

    }



}
