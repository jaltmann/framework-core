<?php
include_once(dirname(__FILE__).'/_bootstrap.php');

$requester = new \util\Requester;

$response = $requester->get('https://jens-altmann.de');

unset($response[0]['response']['content']);
print_r($response);
