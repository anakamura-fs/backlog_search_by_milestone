<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

$backlog = new Backlog(new ApiKeyConnector('xxxxx', 'xxxxxxxx','jp'));

$issues = $backlog->issues->load();
print_r($issues);


