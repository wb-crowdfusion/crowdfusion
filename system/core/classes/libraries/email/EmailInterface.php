<?php
/**
 * Interface for Email, provides email delivery
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
 * @version     $Id: EmailInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for Email, provides email delivery
 *
 * @package     CrowdFusion
 */
interface EmailInterface
{

    /**
     * Resets the email system, clearing all sender, receiver and message information
     *
     * @return $this
     **/
    public function clear();

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
    public function from($emailAddress, $name = null);

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
    public function to($emailAddress, $name = null);

    /**
     * Sets the reply-to address
     *
     * @param string $emailAddress The email address to use as your reply address
     *
     * @return $this
     **/
    public function replyTo($emailAddress);

    /**
     * Sets a CC address for your email. This function can be called multiple times
     * to add multiple CC recipients to the email.
     *
     * @param string $emailAddress The email address of the CC recipient
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the CC recipient
     *
     * @return $this
     **/
    public function cc($emailAddress, $name = null);

    /**
     * Sets a BCC address for your email. This function can be called multiple times
     * to add multiple BCC recipients to the email.
     *
     * @param string $emailAddress The email address of the BCC recipient
     * @param string $name         (Optional) If not null, will be used as the human-readable name of the BCC recipient
     *
     * @return $this
     **/
    public function bcc($emailAddress, $name = null);

    /**
     * Sets the subject for your email.
     *
     * @param string $subject The subject for the email.
     *
     * @return $this
     **/
    public function subject($subject);

    /**
     * Sets the email message body
     *
     * @param string $text The text to use as the message body
     *
     * @return $this
     **/
    public function body($text);

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
    public function altMessage($altMessage);

    /**
     * Attaches the file specified to the email.
     *
     * @param string $filename The absolute filename of the file to attach
     *
     * @return void
     **/
    public function attach($filename);

    /**
     * Sends the email.
     *
     * @throws EmailException when something is wrong
     * @return TRUE if email sent successfully
     **/
    public function send();

}