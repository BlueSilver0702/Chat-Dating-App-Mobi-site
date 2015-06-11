<?php

// configure your app for the production environment

$app['limit'] = 20;

$app['media_dir'] = __DIR__.'/../web/media/';
$app['media_url'] = '/media/';

$app['db.options'] = array(
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'chatapp',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8',
);

$app['endroid.gcm.api_key'] = 'AIzaSyBPUwjz3UIcDGIi5SjYb525JhmRhtOWqmg';

$app['ios.push_notification'] = array(
    'host' => 'gateway.push.apple.com',
    'port' => 2195,
    'passphrase' => 'ChatApp2014',
    'cert_file' => __DIR__.'/../doc/ArnelChatAppDistAPNS.pem',
);

$app['facebook'] = array(
    'key' => '550898918324089',
    'secret' => '03c3ffeccbecbdec45336166cc87d5c4',
);

$app['oauth.services'] = array(
    'facebook' => array(
        'key' => $app['facebook']['key'],
        'secret' => $app['facebook']['secret'],
        'scope' => array('email'),
        'user_endpoint' => 'https://graph.facebook.com/me',
    ),
);
