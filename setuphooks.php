<?php

require 'vendor/autoload.php';

use ProsperWorks\CRM;
use ProsperWorks\Config;
use ProsperWorks\Webhooks;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$email = getenv('COPPER_EMAIL');
$token = getenv('COPPER_TOKEN');
$secret = getenv('COPPER_SECRET');

$crypt = new Class () {
    public function encryptBase64 ($data) {
        return base64_encode(openssl_encrypt($data, 'aes-256-xts', getenv('KEY')));
    }
    public function decryptBase64 ($data) {
        return openssl_decrypt(base64_decode($data), 'aes-256-xts', getenv('KEY'));
    }
};
Config::set($email, $token, $secret, '', $crypt);
Config::debugLevel(Config::DEBUG_COMPLETE);
$webhookAPI = new Webhooks('https://129c9651.ngrok.io/');

$list = $webhookAPI->list();
$ids = [];
foreach ($list as $entry) {
    $date = $entry->created_at->format('Y-m-d H:i:s');
    echo $entry->id, " ", $entry->event, " ", $entry->type, " ", PHP_EOL;
    $ids[] = $entry->id;
}

$webhookAPI->delete(...$ids);

$resources = [CRM::RES_COMPANY, CRM::RES_PERSON, CRM::RES_OPPORTUNITY];
foreach ($resources as $resource) {
    $ids = $webhookAPI->create($resource);
    $total = sizeof($ids);
    $s = $total > 1 ? 's' : '';
    echo "<info>Created <underscore>$total webhook$s</underscore> for <bold>$resource</bold></info>\n";
}

$list = $webhookAPI->list();
foreach ($list as $entry) {
    $date = $entry->created_at->format('Y-m-d H:i:s');
    echo $entry->id, " ", $entry->event, " ", $entry->type, " ", PHP_EOL;
}
