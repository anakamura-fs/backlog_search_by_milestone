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

// milestone に所属するissue
$milestones = $backlog->projects->versions(getenv("PROJECT"));
$milestones = array_filter($milestones, function($milestone){
    return $milestone->name == getenv("MILESTONE_NAME");
});
$milestones = array_values($milestones); // array_filterがキーを維持してしまうので、それを捨てるため
$issues = [];
for ($try_count=0; ; $try_count++){
    $count = count($issues);
    $issues = array_merge($issues,
        $backlog->issues->load([
            "milestoneId"=>[
                $milestones[0]->id,
            ],
            "count"=>100,
            "offset" => ($try_count * 100),
        ])
    );
    $count2 = count($issues);
    if ($count == $count2 ) break; // 増えなくなったら終了
    sleep(1); // お作法
}
$issueIds = array_map(function($issue){return $issue->id;}, $issues);
//echo_json($issueIds);

// git repo
$repos = $backlog->git->repositories(getenv("PROJECT"));
// echo_json($repos);

$pullReqs = array_map(function ($repo) use ($backlog, $myself, $issueIds){
    $pullReqs =  $backlog->git->pullRequests(getenv("PROJECT"), $repo->name, [
        "createdUserId" => [$myself->id], // プルリク出した人、つまりコード書いた人
        "count" => 100, // 上限
        "issueId"=>$issueIds,
    ]);
    foreach ($pullReqs as $pr){
        $pr->repoName = $repo->name;
    }
    return $pullReqs;
}, $repos);
$pullReqs = call_user_func_array('array_merge', $pullReqs); // flatten
foreach ($pullReqs as $pr) {
    if ($pr->status->name != "Merged"){
        continue;
    }
    if ($pr->issue->milestone[0]->name != getenv("MILESTONE_NAME")){
        continue;
    }

    echo "{$pr->issue->issueKey}:{$pr->issue->summary} (repo={$pr->repoName})"."\n";
}
//echo "pullReqs";
//echo_json($pullReqs);

