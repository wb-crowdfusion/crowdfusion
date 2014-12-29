<?php
/**
 * TemplatedEmail
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
 * @version     $Id$
 */

/**
 * TemplatedEmail
 *
 * @package     CrowdFusion
 */
class TemplatedEmail implements TemplatedEmailInterface
{

    protected $Email;

    public function setEmail(EmailInterface $Email)
    {
        $this->Email = $Email;
    }

    protected $Renderer;

    public function setRenderer(RendererInterface $Renderer)
    {
        $this->Renderer = $Renderer;
    }

    protected $Events;

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    public function send($templateFile, $parameters, $viewHandler = 'html')
    {

        $emailView = new View($templateFile,$parameters);

        list($content,$globals) = $this->Renderer->renderView($emailView,$viewHandler);

        $messageData = new Transport();
        $messageData->FromAddress = isset($globals['FromAddress']) ? $globals['FromAddress'] : '';
        $messageData->FromName = isset($globals['FromName']) ? $globals['FromName'] : '';
        $messageData->ToAddress = isset($globals['ToAddress']) ? trim($globals['ToAddress']) : '';
        $messageData->ToName = isset($globals['ToName']) ? $globals['ToName'] : '';
        $messageData->Subject = isset($globals['Subject'])? $globals['Subject'] : '';
        $messageData->Body = $content;
        $messageData->ReplyToAddress = isset($globals['ReplyToAddress']) ? $globals['ReplyToAddress'] : '';
        $messageData->ReplyToName = isset($globals['ReplyToName']) ? $globals['ReplyToName'] : '';

        $this->Events->trigger('TemplatedEmail.send.pre', $messageData);
        $this->Events->trigger('TemplatedEmail.send.'.$templateFile.'.pre', $messageData);

        $email = $this->Email
            ->clear()
            ->from($messageData->FromAddress, $messageData->FromName)
            ->to($messageData->ToAddress, $messageData->ToName)
            ->subject($messageData->Subject)
            ->body($messageData->Body)
        ;
        if (!empty($messageData->ReplyToAddress)) {
            $email->replyTo($messageData->ReplyToAddress, $messageData->ReplyToName);
        }

        $email->send();
    }


}