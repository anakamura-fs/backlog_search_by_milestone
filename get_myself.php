<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

function echo_json($it){
    echo json_encode($it, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$backlog = new Backlog(new ApiKeyConnector(getenv("SPACE_ID"), getenv("APIKEY"),
	getenv('BACKLOG_DOMAIN')?:'com'));

// $issues = $backlog->issues->load();
// echo_json($issues);

/** ログイン者 */
$myself = $backlog->users->myself();

echo_json($myself);
