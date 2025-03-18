=== Neo WebP/AVIF Converter ===
Contributors: Nano Yozakura
Donate link: https://support.773.moe/donate
Tags: avif, webp, 画像圧縮, nginx, No Redirect
Requires at least: 6.0
Tested up to: 6.7.2
Stable tag: 1.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ChatGPTを用いて制作されたWebp/AVIFエンコーダ
nginx+fpmの環境でも動作し、nginx.confをいじる必要がなし
nginx+Apache環境でもリダイレクトがないため高速
avif圧縮はphp8.1以降+libgd2.3.0以降の環境でのみ動作します

== Description ==

記事並びにテーマにある画像を自動抽出し、Webp、AVIFに自動変換するプラグイン

画像アップロード時にも自動変換します

画像置換時リダイレクトを使用せず、HTMLを書き換えるので特にスマホでより高速になります

[Documentation](https://support.773.moe/neo-webavif-converter)

== Features ==

* ChatGPTを用いて制作されたコード
* nginx+fpm環境のWordpressに対応
* nginx.confとかをいじる必要がなし
* mod_rewriteや.htaccessが不要
* Apache環境(もしくはnginx+Apache環境）でもリダイレクトが発生しないのでより従来のwebp/avif変換より高速化

より詳しくは以下のページで説明しています。
[公式サイト](https://support.773.moe/neo-webavif-converter)

== Frequently Asked Questions ==

= AVIFの変換にAPIとか買わないといけないのですか？ =

あなたのサーバー上（もしくは共用サーバー）で動作するので購入の必要はありません

= 日本語には対応してますか？ =

対応しています。

= 大量変換中、504エラーが発生します =

戻るボタンを押して、再度変換してください。

レンタルサーバーの場合、2,3分待ってから変換したほうが良いでしょう

= どこのディレクトリに保存されますか？ =

webp/avif変換済画像は /wp-content/compressed-image に保存されます

= 既存のjpegとpngの圧縮はしますか？ =

既存の画像の再圧縮は行いません

== Screenshots ==



== Changelog ==

= 1.0 =
* 初回アップロード

これ以前の変更履歴は[サポートページまで](https://support.773.moe/neo-webavif-converter)、もしくはプラグインディレクトリ内のREADME.md等で
