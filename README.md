# backlog_search_by_milestone

backlog.jp で、
(或るスペースの)或るプロジェクトに属するユーザの、
或るマイルストーンにおいての成果物
を含むチケット/プルリクを、
検索するコマンドです。

成果物とはここでは、
同サービス内のgitでプルリクし
(恐らくレビューを通って)
マージしてもらえたファイル改訂
とします。

## 環境

* unixライクなシェル環境を想定しています。
    * linuxとか git for windowsに付属のbashとかで使えると思います。
* php (7.x)
    * (windowsの`scoop`などでインストールしたphpで動きました)
* phpの composer


## 使い方

* `compose install` します
* run.shを編集します
    * SPACE_ID にはスペースIDを設定
    * APIKEY には、backlog.jpの自分(誰か)のアカウントで取得したAPI KEYを設定
        * 個人設定→API で設定画面に行けます。
            URLは https://(スペースID).backlog.jp/EditApiSettings.action
    * PROJECT と MILESTONE_NAMEもそれぞれ設定
* run.shを実行します。 `sh run.sh` など
* 標準出力に検索結果が出ます
    * 例：
        ```
        TP1-4:課題01 (repo=rep01)
        TP1-5:課題02 (repo=rep02)
        ```
       * この状態の出力はsort,uniqされていません
    * sort , uniq , grep , sed などなどでお好みに整えてください
    
## その他

### 「ユーザ」IDの見つけ方

* APIKEYの所有者本人の情報なら、myselfというAPIで得られる。(今回はそのように実装している)
* 他のAPI(チケット検索、プルリク検索など)で、本人以外の情報も得られる事があるので、それを地道にメモする。
    * たとえば「私が発行したプルリク」なら「プルリクを受け取った人の名前とID」も出力に付加されている。
* APIKEYの所有者が管理者なら、ユーザ一覧APIを発行できるので、参加者全員の情報が得られる。(今回未実装)

