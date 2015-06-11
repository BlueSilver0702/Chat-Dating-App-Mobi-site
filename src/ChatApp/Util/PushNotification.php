<?php

namespace ChatApp\Util;

use Silex\Application;
use ChatApp\Model\Entity\User;

class PushNotification
{
    /**
     * Send a push notification to...
     *
     * @return Boolean True is successfully sent, otherwise false
     */
    public static function send(Application $app, User $user, array $data)
    {
        // ignore when on debug mode
        if ($app['debug']) {
            return false;
        }

        $error = false;

        if ($user->getEndroidGcmId()) {
            $tmp = $data;
            switch ($tmp['title']) {
                case 'START_TYPING':
                case 'STOP_TYPING':
                case 'NEW_MESSAGE':
                case 'INVITE':
                    $tmp['chat_id'] = $tmp['parameters'];
                    break;

                case 'MOMENT_COMMENT':
                case 'MOMENT_LIKE':
                    $tmp['moment_id'] = $tmp['parameters'];
                    break;
            }
            unset($tmp['parameters']);

            if (!$app['endroid.gcm']->send($tmp, array(
                $user->getEndroidGcmId()
            ))) {
                $error = true;
            }
        }

        if ($user->getIosDeviceId()) {

            // provide the Host Information
            $host = $app['ios.push_notification']['host'];
            $port = $app['ios.push_notification']['port'];

            // provide the Certificate and Key Data
            $certFile = $app['ios.push_notification']['cert_file'];

            // provide the Private Key Passphrase (alternatively you can keep this secrete
            // and enter the key manually on the terminal -> remove relevant line from code)
            $passphrase = $app['ios.push_notification']['passphrase'];

            // provide the Device Identifier (Ensure that the Identifier does not have spaces in it)
            $token = $user->getIosDeviceId();

            $type = 1; //NEW_MESSAGE
            switch (strtoupper($data['title'])) {
                case 'INVITE': $type = 2; break;
                case 'START_TYPING': $type = 3; break;
                case 'STOP_TYPING': $type = 4; break;
                case 'MOMENT_LIKE': $type = 5; break;
                case 'MOMENT_COMMENT': $type = 6; break;
            }

            // create the message content that is to be sent to the device.
            // and encode the body to JSON.
            $body = json_encode(array(
                'aps' => array(
                    // the message that is to appear on the dialog.
                    'alert' => $data['from_username'],

                    // the Badge Number for the Application Icon (integer >=0)
                    'badge' => 1,

                    // audible Notification Option
                    'sound' => 'default',

                    // type of Notification (1=NEW_MESSAGE, 2=INVITE, 3=START_TYPING, 4=STOP_TYPING)
                    'payload' => sprintf('%d;%d', $type, $data['parameters']),
                ),
            ));

            // create the Socket Stream.
            $context = stream_context_create();
            stream_context_set_option($context, 'ssl', 'local_cert', $certFile);

            // remove this line if you would like to enter the Private Key Passphrase manually.
            stream_context_set_option($context, 'ssl', 'passphrase', $passphrase);

            // open the Connection to the APNS Server.
            $socket = stream_socket_client('ssl://'.$host.':'.$port, $error, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $context);

            // check if we were able to open a socket.
            if (!$socket) {
                throw new \Exception(sprintf('APNS Connection Failed: %s %s', $error, $errstr));
            }

            // build the Binary Notification.
            $message = chr(0).pack('n', 32).pack('H*', $token).pack('n', strlen($body)).$body;

            // send the Notification to the Server.
            $result = fwrite ($socket, $message, strlen($message));

            if (!$result) {
                $error = true;
            }

            // close the Connection to the Server.
            fclose ($socket);
        }

        return !$error;
    }
}
