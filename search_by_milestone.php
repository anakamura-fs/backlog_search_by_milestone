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

/**
 * 配列を連想配列にする。元の配列の各要素からキーを作り、連想配列化して返します。
 *
 * 注：array_mergeは、
 * 連想配列を「キー後勝ち」で混ぜます。
 * つまり重複キーを期待通り除外されます。
 *
 * @param E[] $ary 元の配列。これのキーは最終的に無視されます。
 * @param callable $index_func 元の配列の各要素からキーを算出する関数。
 * `func(E): string`
 * @return array 元の各要素とキーを使って作った連想配列。
 * `Map<string, E>`
 */
function array_index(array $ary, callable $index_func){
    $result = [];
    foreach ($ary as $elm){
        $result[$index_func($elm)] = $elm;
    }
    return $result;
}

$backlog = new Backlog(new ApiKeyConnector(getenv("SPACE_ID"), getenv("APIKEY"),
	getenv('BACKLOG_DOMAIN')?:'com'));

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
// [id1=>要素1, id2=>要素2, ...] という構造に変換
$issues = array_index($issues, function ($issue){return $issue->id;});

// 「マイルストンが合致する親課題」だけ狙い撃ち
$parentIssues = okawari(100, function ($try_count=0) use ($backlog, $milestones){
    if ($try_count>0) sleep(1); // お作法
    return $backlog->issues->load([
        "milestoneId"=>[
            $milestones[0]->id,
        ],
        "parentChild"=>4, // parent issues only
        "count"=>100,
        "offset" => ($try_count * 100),
    ]);
});
foreach ($parentIssues as $parent){
    // 上で見つけた親課題の子課題(マイルストンは同じとは限らない)を検索
    $childIssues = okawari(100, function ($try_count=0) use ($backlog, $parent){
        if ($try_count>0) sleep(1); // お作法
        return $backlog->issues->load([
            "parentChild"=>2, // child issues only
            "parentIssueId"=>[
                $parent->id,
            ],
            "count"=>100,
            "offset" => ($try_count * 100),
        ]);
    });
    // [id1=>要素1, id2=>要素2, ...] という構造に変換
    $childIssues = array_index($childIssues, function ($issue){return $issue->id;});
    // 追加。ただしid重複は(連想配列なので自動的に)除外
    // 整数風キーの連想配列でarray_mergeを使うとキーが消えてしまうので、`+`演算子のほうが良い。
    $issues += $childIssues;
}
// echo_json($issues);

$issueIds = array_keys($issues); // キーが issue id
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
    // プルリクのチケットに直接マイルストンが書いてあったら照合する
    // プルリクのチケットに書いてない(親にはマイルストンが書いてる)なら照合しない
    if ($pr->issue->milestone && $pr->issue->milestone[0]->name != getenv("MILESTONE_NAME")){
        continue;
    }

    echo "{$pr->issue->issueKey}:{$pr->issue->summary} (repo={$pr->repoName})"."\n";
}
//echo "pullReqs";
//echo_json($pullReqs);

