<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

$backlog = new Backlog(new ApiKeyConnector('xxxxx', 'xxxxxxxx','jp'));

$issues = $backlog->issues->load();
print_r($issues);

// milestone に所属するissue
$milestones = $backlog->projects->versions("TP1");
print_r($milestones[0]);
$issues = $backlog->issues->load([
    "milestoneId"=>[
        $milestones[0]->id,
    ],
]);
print_r($issues);

