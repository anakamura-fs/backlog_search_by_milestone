# backlog_search_by_milestone

backlog.jp で、
(或るスペースの)或るプロジェクトに属する或るユーザの、
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
    * php5.6でも動くようです。後述
* phpの composer

## 使い方: search_by_milestone

本題である「或るマイルストーンに属する成果(のチケット)検索」をします

* `compose install` します
    * php5.6の場合は、`composer update`してください(ライブラリ依存関係を見直す)
* search_by_milestone.shを編集します
    * SPACE_ID にはスペースIDを設定
    * APIKEY には、backlog.jpの自分のアカウントで取得したAPI KEYを設定
        * 個人設定→API で設定画面に行けます。
            URLは https://(スペースID).backlog.jp/EditApiSettings.action
        * ※自分以外にAPIKEYを教えるのは…お勧めしません…
        * ※APIKEYは一人で幾つでも取得できるようなので、都合に応じて新規生成すると良いと思います。
    * PROJECT と MILESTONE_NAMEもそれぞれ設定
* search_by_milestone.shを実行します。 `sh search_by_milestone.sh` など
* 標準出力に検索結果が出ます
    * 例：
        ```
        TP1-4:課題01 (repo=rep01)
        TP1-5:課題02 (repo=rep02)
        ```
       * この状態の出力はsort,uniqされていません
    * sort , uniq , grep , sed などなどでお好みに整えてください
    
## その他

## 使い方: get_myself

「ユーザ(主に自分以外の同僚?)のアカウントのIDを調べる」手段です。
ただし、直接調べることはbacklogの一般ユーザには出来ません。
(※管理者権限があるユーザだと出来るようですが、未対応)

そこで替わりに、
「各ユーザに「自分のIDを調べる」スクリプトを実行してもらい、その結果を教えてもらう」
ことで解決しようと考えました。

* `compose install` します
    * (同上)
* get_myself.sh を編集します
    * (同上)
* get_myself.shを実行します。 `sh get_myself.sh` など
* 標準出力に検索結果が出ます
    * 例：下記のような小さいJSONが出力されます。
        ```
        {
        "id": 42XXX,
        "userId": "anakamuraf",
        "name": "anakamuraf",
        (以下略)
        ```
* 上記JSONの`id`項目の数値が「これを実行した人のID」です。

### 「ユーザ」IDの見つけ方

* APIKEYの所有者本人の情報なら、myselfというAPIで得られる。(今回はそのように実装している)
* 他のAPI(チケット検索、プルリク検索など)で、本人以外の情報も得られる事があるので、それを地道にメモする。
    * たとえば「私が発行したプルリク」なら「プルリクを受け取った人の名前とID」も出力に付加されている。
* APIKEYの所有者が管理者なら、ユーザ一覧APIを発行できるので、参加者全員の情報が得られる。(今回未実装)

