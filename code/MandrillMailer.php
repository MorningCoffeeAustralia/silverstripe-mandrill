<?php

/*
 * class MandrillMailer
 * Mailer class for sendin email using Mandrill api https://mandrillapp.com/api/docs/
 */

class MandrillMailer extends Mailer {
    
    function __construct ($apiKey) {
        Mandrill::set_api_key ($apiKey);
    }
    
    function sendPlain ($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false) {
        $args = array ("message" => array (
                "text" => $plainContent,
                "subject" => $subject,
                "from_email" => $from,
                "to" => array (
                    array (
                        "email" => $to
                    )
                )
            )
        );
        
        $ret = Mandrill::request ("messages/send", $args);
        if ($ret) {
            return array ($to, $subject, $plainContent, $customheaders);
        } else {
            return false;
        }
    }
    
    function sendHTML ($to, $from, $subject, $htmlContent, $attachedFiles = false,
                       $customheaders = false, $plainContent = false, $inlineImages = false) {
        
        $args = array ("message" => array (
                "html" => $htmlContent,
                "subject" => $subject,
                "from_email" => $from,
                "to" => array (
                    array (
                        "email" => $to
                    )
                )
            )
        );
        
        $ret = Mandrill::request ("messages/send", $args);

        if ($ret) {
            return array ($to, $subject, $htmlContent, $customheaders);
        } else {
            return false;
        }
    }
}