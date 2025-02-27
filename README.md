# Free Wordpress webp/AVIFコンバーター

## 特徴
100% Pure ChatGPT Code<br>
nginx+fpm環境のWordpressに対応<br>
Apache環境(もしくはnginx+Apache環境）でもリダイレクトが発生しないのでより高速化

## インストール
WPROOT/wp-content/plugins/Neo-WebP-Converter ディレクトリを作成し
その中にNeo-WebP-Converter.phpを入れて有効化

## 設定画面
設定画面は、設定→Neo Webp Converterの中にあります

## avifについて
avifencコマンドがPATHに通ってないと使用できません

## アンインストール
無効化して削除

面倒だが、wp-content/uploads 以下の *.webp *.avif ファイルをそれぞれ削除

## その他
コードの中に、以下のものが入っています

１．HTMLを直接 .jpg.webp 等に置換する（無効）<br>
これを有効にした場合、キャッシングプラグインが使用できません

２．pictureタグに置き換える（有効）

３．JavaScriptで置き換える（無効）

おそらく2番しかうまく動きません
コメント、コメントアウトしてご利用下さい
