<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

function echo_json($it){
    echo json_encode($it, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$backlog = new Backlog(new ApiKeyConnector(getenv("SPACE_ID"), getenv("APIKEY"),'jp'));

// $issues = $backlog->issues->load();
// echo_json($issues);

/** ログイン者 */
$myself = $backlog->users->myself();
// echo "myself";
// echo_json($myself);

/** チケットのステータス一覧。(プルリクの、ではない) */
$allStatuses = $backlog->projects->statuses(getenv("PROJECT"));
echo "allStatuses";
//echo_json($allStatuses);

// milestone に所属するissue
$milestones = $backlog->projects->versions(getenv("PROJECT"));
echo "milestones";
//echo_json($milestones);
$issues = $backlog->issues->load([
    "milestoneId"=>[
        $milestones[0]->id,
    ],
]);
echo "issues";
//echo_json($issues);

// git repo
$repos = $backlog->git->repositories(getenv("PROJECT"));
// echo_json($repos);

$pullReqs = array_map(function ($repo) use ($backlog, $myself){
    return $backlog->git->pullRequests(getenv("PROJECT"), $repo->name, [
        "createdUserId" => [$myself->id], // プルリク出した人、つまりコード書いた人
        "count" => 100, // 上限
    ]);
}, $repos);
$pullReqs = call_user_func_array('array_merge', $pullReqs); // flatten
echo "pr ...";
foreach ($pullReqs as $pr) {
    if ($pr->status->name != "Merged"){
        continue;
    }
    if ($pr->issue->milestone[0]->name != "MS01"){
        continue;
    }

    echo_json($pr);
}
//echo "pullReqs";
//echo_json($pullReqs);

