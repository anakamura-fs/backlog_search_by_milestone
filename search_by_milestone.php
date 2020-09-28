<?php

require_once "./vendor/autoload.php";

use Itigoppo\BacklogApi\Backlog\Backlog;
use Itigoppo\BacklogApi\Connector\ApiKeyConnector;

function echo_json($it){
    echo json_encode($it, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

/**
 * 「おかわり」。
 * 関数を何度か呼び出して得た答え(配列)をつなぎ合わせたものを自分の答えとする。
 * @param int $max_count_per_once お茶碗一杯ぶんの最大量。
 * おかわりを盛る人が返す量がこの最大量を下回る場合は
 * 「もうお櫃が空っぽ」と見なす。
 * @param callable $okawari_func おかわりを盛る人。
 * 呼び出される(おかわりを要求される)と何らかの処理をして結果配列を返してほしい。
 * 渡す引数は「今何杯目か？」(ただし0オリジン)。
 * @return array 結局食べた全部
 */
function okawari($max_count_per_once, callable $okawari_func){
    $result = [];
    for ($count=0; ; $count++){
        /** @var array $result_once */
        $result_once = $okawari_func($count);
        $result = array_merge($result, $result_once);
        if (count($result_once) < $max_count_per_once){
            break;
        }
    }
    return $result;
}

$backlog = new Backlog(new ApiKeyConnector(getenv("SPACE_ID"), getenv("APIKEY"),'jp'));

// $issues = $backlog->issues->load();
// echo_json($issues);

if (getenv("USER_ID")){
    $target_user = new stdClass();
    $target_user->id = getenv("USER_ID");
} else {
    $target_user = $backlog->users->myself();
}

// milestone に所属するissue
$milestones = $backlog->projects->versions(getenv("PROJECT"));
$milestones = array_filter($milestones, function($milestone){
    return $milestone->name == getenv("MILESTONE_NAME");
});
$milestones = array_values($milestones); // array_filterがキーを維持してしまうので、それを捨てるため
$issues = okawari(100, function ($try_count=0) use ($backlog, $milestones){
    if ($try_count>0) sleep(1); // お作法
    return $backlog->issues->load([
        "milestoneId"=>[
            $milestones[0]->id,
        ],
        "count"=>100,
        "offset" => ($try_count * 100),
    ]);
});
$issueIds = array_map(function($issue){return $issue->id;}, $issues);
//echo_json($issueIds);

// git repo
$repos = $backlog->git->repositories(getenv("PROJECT"));
// echo_json($repos);

$pullReqs = array_map(function ($repo) use ($backlog, $target_user, $issueIds){
    $pullReqs = okawari(100, function ($try_count=0)
    use ($backlog, $repo, $target_user, $issueIds){
        if ($try_count>0) sleep(1); // お作法
        return $backlog->git->pullRequests(getenv("PROJECT"), $repo->name, [
            "createdUserId" => [$target_user->id], // プルリク出した人、つまりコード書いた人
            "count" => 100, // 上限
            "offset" => ($try_count * 100),
            "issueId"=>$issueIds,
        ]);
    });
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

