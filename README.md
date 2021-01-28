# backlog_search_by_milestone

backlog.jp で、
(或るスペースの)或るプロジェクトに属する或るユーザの、
或るマイルストーンに属するチケット
(およびマイルストーンに属するチケットの子チケット)
のプルリクを、
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

## 使い方1: search_by_milestone

本題である「或るマイルストーンに属する成果(のチケット)検索」をします

* `compose install` します
    * php5.6の場合は、`composer update`してください(ライブラリ依存関係を見直す)
* search_by_milestone.shを編集します
    * SPACE_ID にはスペースIDを設定
    * APIKEY には、backlog.jpの自分のアカウントで取得したAPI KEYを設定
        * 個人設定→API で設定画面に行けます。
            URLは https://(スペースID).backlog.jp/EditApiSettings.action
        * ※APIKEYは一人で幾つでも取得できるようなので、都合に応じて新規生成すると良いと思います。
    * PROJECT と MILESTONE_NAMEもそれぞれ設定
    * USER_ID は空、または環境変数じたいを削除(orコメント)。
        * ※「USER_IDが設定されていれば」そのIDで(自分自身ではなく)検索するよう動作します。後述
    * BACKLOG_DOMAIN は、お使いのbacklogのURLが「backlog.jp」なら`jp`を、「backlog.com」なら`com`を、設定してください。
      (デフォルト=`com`)
* search_by_milestone.shを実行します。 `sh search_by_milestone.sh` など
* 標準出力に検索結果(成果物)が出ます
    * 例：
        ```
        TP1-4:課題01 (repo=rep01)
        TP1-5:課題02 (repo=rep02)
        ```
       * この状態の出力はsort,uniqされていません
    * sort , uniq , grep , sed などなどでお好みに整えてください
    
## 使い方2: get_myself

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

## 使い方2.5: get_myself を使わない:-)方法

実は、
get_myselfと同じ効果を、
同スクリプトを使わないし
同僚の手も煩わせないで
得る方法が有ります。

### 方法：
* (ブラウザで)チケット検索画面を開きます
* 検索条件の「担当者」のところで、調べたいユーザを選択します。
* すると、ブラウザのURLの`assignerId`パラメータの値が、調べたいユーザのソレになります。
* その値をメモします。以上。

あ。なんか、このほうが楽ですね:-)

## 使い方3: search_by_milestone で自分以外の情報を検索

「自分以外の、或るマイルストーンに属する成果(のチケット)検索」をします

* 他のユーザに頼んで、 get_myself.sh を実行してもらいます
    * `使い方2: get_myself` を行なってもらいます
* その結果の`id`値を教えてもらいます
* (自分の) search_by_milestone を実行する準備をします
    * `使い方1: search_by_milestone` に倣います
* それに加えて、 search_by_milestone.shを編集します
    * USER_ID には「他のユーザのid」を設定
    * ※「USER_IDが設定されていれば」そのIDで(自分自身ではなく)検索するよう動作します。
* search_by_milestone.shを実行します。 `sh search_by_milestone.sh` など
* 検索される結果は、自分ではなくそのIDのユーザについての成果物になります

## 使い方4： get_the_user.php で自分以外のユーザ情報自体を取得

`get_the_user.php`というスクリプトも作りました。

USER_IDを(環境変数で)指定すると、
そのユーザの情報を取得しJSON表示します。

search_by_milestone.sh の中で呼んでみました。

これで
「環境変数で指定されたIDのユーザの
ユーザ自体の情報と成果物情報とが出力される」
ようになりました。

複数人についてシェルでループ回すような使い方のとき、
便利かなーと思いまして、
作りました。

