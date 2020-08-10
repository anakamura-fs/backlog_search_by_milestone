<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

function echo_json($it){
    echo json_encode($it, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$backlog = new Backlog(new ApiKeyConnector(getenv("SPACE_ID"), getenv("APIKEY"),'jp'));

$issues = $backlog->issues->load();
echo_json($issues);

// milestone に所属するissue
$milestones = $backlog->projects->versions("TP1");
echo echo_json($milestones);
$issues = $backlog->issues->load([
    "milestoneId"=>[
        $milestones[0]->id,
    ],
]);
echo echo_json($issues);


