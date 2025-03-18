# Free Wordpress webp/AVIFコンバーター

## 特徴
100% Pure ChatGPT Based Code<br>
nginx+fpm環境のWordpressに対応<br>
nginx.confとかをいじる必要がなし<br>
mod_rewriteや.htaccessが不要<br>
Apache環境(もしくはnginx+Apache環境）でもリダイレクトが発生しないのでより従来のwebp/avif変換より高速化<br>
img srcsetで画像を置換

## インストール
WPROOT/wp-content/plugins/Neo-WebP-Converter ディレクトリを作成し
その中にNeo-WebP-Converter.phpを入れて有効化

## 設定画面
設定画面は、設定→Neo Webp/AVIF Converterの中にあります

## avifについて
AVIFの変換はphp8.1以降、libgd 2.3.0以降でないと対応しません

sudo apt install libgd-dev

等としてインストールしてください

もしくはavifencコマンドがPATHに通ってないと使用できません

sudo apt install libavif-bin<br>
sudo pkg install libavif

等としてインストールしてください

## 圧縮について
avifencを使用すると多大な負荷がかかるだけではなく、
ファイル数が多いと504エラーになります

再度圧縮しなおしてください

## アンインストール
無効化して削除

wp-content/compressed-image をすべて削除

## バージョン履歴
v1.0 - Wordpressディレクトリに登録、セキュリティー強化、なお、execを使用してはならないということで avifencのサポートは終了しています https://github.com/nanakochi123456/Neo-Webp-AVIF-Converter-for-Wordpress/commit/6d0a27ebd9c30406205fc85e295328cdbefee0c1#diff-b5875aebd416d1596845a822436c7a2bca2a865a800bec54d24ee17c47842ff1 こちらのバージョンをご利用下さい

v0.99 - リリース用にクラス化、簡易国際化

v0.34 - uninstall.phpを作成、なお*.webp、*.avifの画像は削除されません

v0.33 - avif変換にphpのエンコーダに対応

v0.32 - 手動一括変換時にavifencが存在しない場合、エラーを出力するようにした

v0.31 - avifenc の -j オプションでJOB数を指定できるようにした

v0.30 - メディアアップロード時に動的変換

v0.23 - ユーザー権限サーバーでも動作するように？avifencのパスを設定できるようにした、webpとavifの変換を分割化、avifencがなくても正常動作するようにした

v0.22 - 経緯な変更、特にWebサイト等（ブログランキング、SNS等）からfetchされる時 webp/avifに対応してないとうまくいかないのを修正

v0.21 - 圧縮画像ファイルのディレクトリ変更、テーマも対応

v0.2 - avif対応

v0.1 - 初版
