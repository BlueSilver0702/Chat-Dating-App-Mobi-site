<?php

namespace ChatApp\Util;

class Mailer
{
    /**
     * Send email using php mail()
     */
    public static function send($to, $subject, $message, array $headers = array())
    {
        if (is_array($to)) $to = implode(',', $to);
        return mail($to, $subject, $message, implode("\r\n", array_merge($headers, array("From: noreply@chatapp.mobi"))));
    }
}
