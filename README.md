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

sudo apt install libavif-bin<br>
sudo pkg install avifenc

等としてインストールしてください

## 圧縮について
avifencを使用すると多大な負荷がかかるだけではなく、
ファイル数が多いと504エラーになります

再度圧縮しなおしてください

## アンインストール
無効化して削除

面倒だが、wp-content/uploads 以下の *.webp *.avif ファイルをそれぞれ削除

## バージョン履歴
v0.22 - 経緯な変更、特にWebサイトからfetchされる時 webp/avifに対応してないとうまくいかないのを修正

v0.21 - 圧縮画像ファイルのディレクトリ変更、テーマも対応

v0.2 - avif対応

v0.1 - 初版
