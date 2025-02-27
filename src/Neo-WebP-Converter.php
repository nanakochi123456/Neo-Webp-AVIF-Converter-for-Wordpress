<?php
/**
 * Plugin Name: Neo WebP/AVIF Converter
 * Description: 自動で WebP/AVIF を作成し、HTML を変換する
 * Version: 0.31
 * Author: Nano Yozakura
 */

if (!defined('ABSPATH')) {
    exit;
}

ini_set('memory_limit', '512M');

// エラーログ
function neowebp_errorlog($content) {
    if(1) {
        error_log($content);
    }
}

// 管理画面のメニュー追加
function neowebp_webp_converter_add_admin_menu() {
    add_options_page('Neo WebP Converter 設定', 'Neo-WebP-Converter', 'manage_options', 'webp_converter', 'neowebp_webp_converter_settings_page');
}
add_action('admin_menu', 'neowebp_webp_converter_add_admin_menu');

// 設定ページの内容
function neowebp_webp_converter_settings_page() {
    ?>
    <div class="wrap">
        <h1>Neo WebP/AVIF Converter 設定</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('neowebp_webp_converter_options');
            do_settings_sections('neowebp_webp_converter');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 設定オプションの登録
function neowebp_webp_converter_register_settings() {
    register_setting('neowebp_webp_converter_options', 'neowebp_webp_quality');
    register_setting('neowebp_webp_converter_options', 'neowebp_avif_quality');
    register_setting('neowebp_webp_converter_options', 'neowebp_avifenc_path');
    register_setting('neowebp_webp_converter_options', 'neowebp_avifenc_jobs');

    //基本設定
    add_settings_section('neowebp_webp_converter_section', '基本設定', null, 'neowebp_webp_converter');

    add_settings_field('neowebp_webp_quality', 'WebP 画質 (0-100 80が標準)', 'neowebp_webp_quality_callback', 'neowebp_webp_converter', 'neowebp_webp_converter_section');
    add_settings_field('neowebp_avif_quality', 'AVIF 画質 (0-100 30が標準)', 'neowebp_avif_quality_callback', 'neowebp_webp_converter', 'neowebp_webp_converter_section');
    add_settings_field('neowebp_avifenc_path', 'avifencの絶対PATH', 'neowebp_avifenc_path_callback', 'neowebp_webp_converter', 'neowebp_webp_converter_section');
    add_settings_field('neowebp_avifenc_jobs', 'avifencの使用CPUコア数', 'neowebp_avifenc_jobs_callback', 'neowebp_webp_converter', 'neowebp_webp_converter_section');


    add_settings_section('neowebp_webp_converter_compressfile', '圧縮画像', null, 'neowebp_webp_converter');

}

add_action('admin_init', 'neowebp_webp_converter_register_settings');

// 画質設定の入力フィールド
function neowebp_webp_quality_callback() {
    $quality = get_option('neowebp_webp_quality', 80);
    echo "<input type='number' name='neowebp_webp_quality' value='$quality' min='0' max='100' />";
}

function neowebp_avif_quality_callback() {
    $quality = get_option('neowebp_avif_quality', 30);
    echo "<input type='number' name='neowebp_avif_quality' value='$quality' min='0' max='100' />";
}

// avifencのパス
function neowebp_avifenc_path_callback() {
    $avifenc_path = get_option('neowebp_avifenc_path', '');
    echo "<input type='text' name='neowebp_avifenc_path' value='$avifenc_path' /> 空欄でPATHを参照する";
}

// avifencのjobs
function neowebp_avifenc_jobs_callback() {
    $avifenc_jobs = get_option('neowebp_avifenc_jobs', 1);
    if($avifenc_jobs < 1) {
        $avifenc_jobs = 1;
    }
    echo "<input type='number' name='neowebp_avifenc_jobs' value='$avifenc_jobs' /> 実コア・契約コア数と同じ程度 レンタルサーバーでは最大4ぐらいにして下さい";
}

// 一括変換のボタンを追加
function neowebp_webp_converter_add_bulk_convert_button() {
    ?>
    <form method="post" action="">
        <input type="hidden" name="neowebp_webp_bulk_convert" value="1">
        <?php submit_button('既存画像を WebP / AVIF に変換'); ?>
    </form>
    <?php
    if (isset($_POST['neowebp_webp_bulk_convert'])) {
        neowebp_webp_avif_convert_existing_images();
    }
}
add_action('admin_notices', 'neowebp_webp_converter_add_bulk_convert_button');

// 画像をスキャンする
function neowebp_get_all_images($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, neowebp_get_all_images($path));
        } elseif (preg_match('/\.(jpg|jpeg|png|JPG|JPEG|PNG)$/i', $item)) {
            $files[] = $path;
        }
    }
    return $files;
}

// 既存画像をWebp/AVIFに変換
function neowebp_webp_avif_convert_existing_images() {
    neowebp_errorlog("webp_avif_convert_existing_images() called");
    $upload_dir = wp_upload_dir();
    $theme_dir = get_theme_root();
    $base_dir = $upload_dir['basedir']; // /home/username/public_html/wp-content/uploads
    $compressed_dir_uploads = WP_CONTENT_DIR . '/compressed-image/uploads'; // 保存先を変更
    $compressed_dir_themes = WP_CONTENT_DIR . '/compressed-image/themes'; // 保存先を変更

    $webp_count_uploads=neowebp_webp_convert($base_dir, $compressed_dir_uploads);
    $webp_count_themes=neowebp_webp_convert($theme_dir, $compressed_dir_themes);

    $webp_converted_count = $webp_count_uploads + $webp_count_themes;

    $avif_count_uploads=neowebp_avif_convert($base_dir, $compressed_dir_uploads);
    $avif_count_themes=neowebp_avif_convert($theme_dir, $compressed_dir_themes);

    $avif_converted_count = $avif_count_uploads + $avif_count_themes;

    echo '<div class="notice notice-success"><p>' . $webp_converted_count . ' 件の画像を WebP に変換しました。</p></div>';
    echo '<div class="notice notice-success"><p>' . $avif_converted_count . ' 件の画像を AVIF に変換しました。</p></div>';
}

// 既存画像を WebPに変換
function neowebp_webp_convert($base_dir, $compressed_dir) {
    neowebp_errorlog("webp_convert() called");
    $quality = get_option('neowebp_webp_quality', 80);

    // `compressed-image` ディレクトリがなければ作成
    if (!file_exists($compressed_dir)) {
        mkdir($compressed_dir, 0755, true);
    }

    $files = neowebp_get_all_images($base_dir);

    if (!$files) {
        echo '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>';
        return;
    }

    $converted_count = 0;

    foreach ($files as $file) {
        $relative_path = str_replace($base_dir, '', $file);
        $webp_path = $compressed_dir . $relative_path . '.webp';
        $avif_path = $compressed_dir . $relative_path . '.avif';
        neowebp_errorlog("webp_path:" . $webp_path);
        neowebp_errorlog("avif_path:" . $avif_path);
       // サブフォルダも作成
        $webp_folder = dirname($webp_path);
        if (!file_exists($webp_folder)) {
            mkdir($webp_folder, 0755, true);
        }

        // WebP 変換
        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'JPG' || $ext === 'JPEG') {
                $image = @imagecreatefromjpeg($file);
            } elseif ($ext === 'png' || $ext === 'PNG') {
                $image = @imagecreatefrompng($file);
                imagepalettetotruecolor($image); // パレット PNG を変換
            } else {
                $image = false;
            }

            if ($image) {
                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
                $converted_count++;
            }
        }
    }
    return $converted_count;
}

// 既存画像を AVIFに変換
function neowebp_avif_convert($base_dir, $compressed_dir) {
    $avif_quality = get_option('neowebp_avif_quality', 30); // AVIF は 30 くらいが推奨
    $avifenc_path = get_option('neowebp_avifenc_path', '');
    $avifenc_jobs = get_option('neowebp_avifenc_jobs', 1);

    // `compressed-image` ディレクトリがなければ作成
    if (!file_exists($compressed_dir)) {
        mkdir($compressed_dir, 0755, true);
    }

    $files = neowebp_get_all_images($base_dir);

    if (!$files) {
        echo '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>';
        return;
    }

    $converted_count = 0;

    foreach ($files as $file) {
        $relative_path = str_replace($base_dir, '', $file);
        $webp_path = $compressed_dir . $relative_path . '.webp';
        $avif_path = $compressed_dir . $relative_path . '.avif';
        neowebp_errorlog("webp_path:".$webp_path);
        neowebp_errorlog("avif_path:".$avif_path);
        // サブフォルダも作成
        $webp_folder = dirname($webp_path);
        if (!file_exists($webp_folder)) {
            mkdir($webp_folder, 0755, true);
        }

        // AVIF 変換（`avifenc` バイナリを使用）
        if (!file_exists($avif_path)) {
            if($avifenc_path == '') {
                $avifenc_path = "avifenc";
            }
            $cmd = $avifenc_path . " -j " . $avifenc_jobs . " --min " . " " . escapeshellarg($avif_quality) . " --max " . escapeshellarg($avif_quality) . " " . escapeshellarg($file) . " " . escapeshellarg($avif_path);
            neowebp_errorlog("cmd:".$cmd);
            exec($cmd, $output, $result);
            if ($result !== 0) {
                neowebp_errorlog("AVIF conversion failed : " . $file);
            } else {
                $converted_count++;
            }
        }
    }
    return $converted_count;
}

// アップロード時に WebP / AVIF に変換する
function neowebp_convert_uploaded_image_to_webp_avif($metadata) {
    $upload_dir = wp_upload_dir();
    // フルパスを生成
    $file_path = trailingslashit($upload_dir['path']) . $size['file']; 
    // 年月を取得
     preg_match('/(\d{4})\/(\d{2})\//', $file_path, $matches);
    if (!$matches) {
        neowebp_errorlog("[Error]Failed to retrieve the year and month from the file path : " . $file_path);
    }
    $year  = $matches[1]; // 例: 2025
    $month = $matches[2]; // 例: 02
    $ymdir = "/" . $year . "/"  . $month . "/";
    $base_dir = $upload_dir['basedir'];  // /wp-content/uploads
    $compressed_dir = WP_CONTENT_DIR . '/compressed-image/uploads'; // WebP / AVIF の保存先

    $quality_webp = get_option('neowebp_webp_quality', 80);
    $avif_quality = get_option('neowebp_avif_quality', 30);
    $avifenc_path = get_option('neowebp_avifenc_path', '');
    $avifenc_jobs = get_option('neowebp_avifenc_jobs', 1);

    // `compressed-image` ディレクトリがなければ作成
    if (!file_exists($compressed_dir)) {
        mkdir($compressed_dir, 0755, true);
    }

    // オリジナルファイルを圧縮
    $file_path = $base_dir . '/' . $metadata['file']; // 
    $relative_path = str_replace($base_dir, '', $file_path);
    $webp_path = $compressed_dir . $relative_path . '.webp';
    $avif_path = $compressed_dir . $relative_path . '.avif';
    neowebp_errorlog("Original File:".$file_path);
    neowebp_errorlog("webp path:".$webp_path);
    neowebp_errorlog("webp path:".$avif_path);

    // サブフォルダも作成
    $folder_path = dirname($webp_path);
    if (!file_exists($folder_path)) {
        mkdir($folder_path, 0755, true);
    }

    // WebP 変換
    if (!file_exists($webp_path) && function_exists('imagewebp')) {
        $image = imagecreatefromstring(file_get_contents($file_path));
        if ($image) {
            imagewebp($image, $webp_path, $quality_webp);
            imagedestroy($image);
        }
    }

    // AVIF 変換（`avifenc` コマンドを使用）
    if (!file_exists($avif_path)) {
        if($avifenc_path == '') {
            $avifenc_path = "avifenc";
        }
        $cmd = $avifenc_path  . " -j " . $avifenc_jobs . " --min " . " " .  escapeshellarg($avif_quality) . " --max " . escapeshellarg($avif_quality) . " " . escapeshellarg($file_path) . " " . escapeshellarg($avif_path);        neowebp_errorlog("cmd:".$cmd);
        exec($cmd, $output, $result);
        if ($result !== 0) {
             neowebp_errorlog("AVIF conversion failed : " . $file_path);
        }
    }

    // サイズごとのファイルを圧縮
    $base_dir = $upload_dir['basedir'] . $ymdir . "/";  // /wp-content/uploads
    $compressed_dir = WP_CONTENT_DIR . '/compressed-image/uploads' . $ymdir . "/"; // WebP / AVIF の保存先
    foreach ($metadata['sizes'] as $size) {
        $file_path = $base_dir . $size['file'];
        $relative_path = str_replace($base_dir, '', $file_path);
        $webp_path = $compressed_dir . $relative_path . '.webp';
        $avif_path = $compressed_dir . $relative_path . '.avif';

        // WebP 変換
        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $image = imagecreatefromstring(file_get_contents($file_path));
            if ($image) {
                imagewebp($image, $webp_path, $quality_webp);
                imagedestroy($image);
            }
        }

        // AVIF 変換（`avifenc` コマンドを使用）
        if (!file_exists($avif_path)) {
            if($avifenc_path == '') {
                $avifenc_path = "avifenc";
            }
            $cmd = $avifenc_path  . " -j " . $avifenc_jobs . " --min " . " " .  escapeshellarg($avif_quality) . " --max " . escapeshellarg($avif_quality) . " " . escapeshellarg($file_path) . " " . escapeshellarg($avif_path);
            neowebp_errorlog("cmd:".$cmd);
            exec($cmd, $output, $result);
            if ($result !== 0) {
                neowebp_errorlog("AVIF conversion failed : " . $file_path);
            }
        }
    }

    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'neowebp_convert_uploaded_image_to_webp_avif');

// 記事のHTML変換 (picture)
function neowebp_convert_images_to_webp_avif($attr, $attachment, $size) {
    neowebp_errorlog("convert_images_to_webp_avif() called");

    $upload_dir = wp_upload_dir();
    $theme_dir = get_theme_root();

    if (!isset($attr['src'])) {
        neowebp_errorlog("No src attribute found in image");
        return $attr;
    }

    if (!isset($_SERVER['HTTP_ACCEPT'])) {
        neowebp_errorlog("HTTP_ACCEPT is not set");
        return $attr;
    }

    $accept = $_SERVER['HTTP_ACCEPT'];
    neowebp_errorlog("HTTP_ACCEPT: " . $accept);

    if (strpos($accept, 'image/avif') === false) {
        neowebp_errorlog("Browser does not support AVIF");
    }
    if (strpos($accept, 'image/webp') === false) {
        neowebp_errorlog("Browser does not support WebP");
    }

    $src = $attr['src'];
    $wp_dir = ABSPATH;
    $upload_dir = $upload_dir['basedir'];
    $theme_dir = get_theme_root();
    $upload_dir = str_replace($wp_dir, '',$upload_dir);
    $theme_dir = str_replace($wp_dir, '', $theme_dir);
    $compressed_dir_uploads = WP_CONTENT_DIR . '/compressed-image/uploads'; // 保存先を変更
    $compressed_dir_themes = WP_CONTENT_DIR . '/compressed-image/themes';    $compressed_dir_uploads = str_replace($wp_dir, '',$compressed_dir_uploads);
    $compressed_dir_themes = str_replace($wp_dir, '', $compressed_dir_themes);
    $webp_src = $src . '.webp';
    $avif_src = $src . '.avif';

    $webp_src = str_replace($upload_dir, $compressed_dir_uploads, $webp_src);
    $avif_src = str_replace($upload_dir, $compressed_dir_uploads, $avif_src);
    $webp_src = str_replace($theme_dir, $compressed_dir_themes, $webp_src);
    $avif_src = str_replace($theme_dir, $compressed_dir_themes, $avif_src);

    neowebp_errorlog("webp_src: " . $webp_src);
    neowebp_errorlog("avif_src: " . $avif_src);

    $webp_exists = file_exists(str_replace(site_url(), ABSPATH, $webp_src));
    $avif_exists = file_exists(str_replace(site_url(), ABSPATH, $avif_src));

    neowebp_errorlog("Checking: " . $src);
    neowebp_errorlog("WebP Exists: " . ($webp_exists ? "Yes" : "No"));
    neowebp_errorlog("AVIF Exists: " . ($avif_exists ? "Yes" : "No"));

    if ($webp_exists || $avif_exists) {
        $attr['srcset'] = '';
        if ($avif_exists) {
            $attr['srcset'] .= $avif_src . ' 1x, ';
        }
        if ($webp_exists) {
            $attr['srcset'] .= $webp_src . ' 1x';
        }
        //$attr['src'] = $avif_exists ? $avif_src : $webp_src;
    }

    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'neowebp_convert_images_to_webp_avif', 10, 3);
