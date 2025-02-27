<?php
/**
 * Plugin Name: Neo-WebP-Converter
 * Description: 自動で WebP/AVIF を作成し、HTML を変換する
 * Version: 0.2
 * Author: Nano Yozakura
 */

ini_set('memory_limit', '512M');

/*
function generate_webp_on_upload($metadata) {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];

    foreach ($metadata['sizes'] as $size) {
        $file_path = $base_dir . '/' . $size['file'];
        $webp_path = $file_path . '.webp';

        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $image = imagecreatefromstring(file_get_contents($file_path));
            if ($image) {
                imagewebp($image, $webp_path, 80);
                imagedestroy($image);
            }
        }
    }
    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'generate_webp_on_upload');
*/

/*
function replace_images_with_webp($content) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $content = preg_replace('/\.(jpg|jpeg|png)/i', '.${1}.webp', $content);
    }
    return $content;
}
add_filter('the_content', 'replace_images_with_webp');
*/
/*
function webp_converter_enqueue_script() {
    wp_enqueue_script('webp-converter', plugin_dir_url(__FILE__) . 'Neo-WebP-Converter.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'webp_converter_enqueue_script');
*/

// エラーログ
function neoerrorlog($content) {
    if(0) {
        error_log($content);
    }
}

// 管理画面のメニュー追加
function webp_converter_add_admin_menu() {
    add_options_page('Neo WebP Converter 設定', 'Neo-WebP-Converter', 'manage_options', 'webp_converter', 'webp_converter_settings_page');
}
add_action('admin_menu', 'webp_converter_add_admin_menu');

// 設定ページの内容
function webp_converter_settings_page() {
    ?>
    <div class="wrap">
        <h1>Neo WebP Converter 設定</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('webp_converter_options');
            do_settings_sections('webp_converter');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 設定オプションの登録
function webp_converter_register_settings() {
    register_setting('webp_converter_options', 'webp_quality');
    register_setting('webp_converter_options', 'webp_replace_html');

    add_settings_section('webp_converter_section', '基本設定', null, 'webp_converter');

    add_settings_field('webp_quality', 'WebP 画質 (0-100)', 'webp_quality_callback', 'webp_converter', 'webp_converter_section');
    add_settings_field('webp_replace_html', 'HTML の書き換えを有効にする', 'webp_replace_html_callback', 'webp_converter', 'webp_converter_section');
}
add_action('admin_init', 'webp_converter_register_settings');

// 画質設定の入力フィールド
function webp_quality_callback() {
    $quality = get_option('webp_quality', 80);
    echo "<input type='number' name='webp_quality' value='$quality' min='0' max='100' />";
}

// HTML 書き換えの ON/OFF スイッチ
function webp_replace_html_callback() {
    $replace_html = get_option('webp_replace_html', 1);
    $checked = $replace_html ? 'checked' : '';
    echo "<input type='checkbox' name='webp_replace_html' value='1' $checked />";
}

// 一括変換のボタンを追加
function webp_converter_add_bulk_convert_button() {
    ?>
    <form method="post" action="">
        <input type="hidden" name="webp_bulk_convert" value="1">
        <?php submit_button('既存画像を WebP /AVIF に変換'); ?>
    </form>
    <?php
    if (isset($_POST['webp_bulk_convert'])) {
        webp_avif_convert_existing_images();
    }
}
add_action('admin_notices', 'webp_converter_add_bulk_convert_button');

// 画像をスキャンする
function get_all_images($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, get_all_images($path));
        } elseif (preg_match('/\.(jpg|jpeg|png)$/i', $item)) {
            $files[] = $path;
        }
    }
    return $files;
}

// 既存画像を WebP、AVIF に変換
function webp_avif_convert_existing_images() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir']; // /home/username/public_html/wp-content/uploads
    $quality = get_option('webp_quality', 80);
    $avif_quality = get_option('avif_quality', 30); // AVIF は 30 くらいが推奨

    $files = get_all_images($base_dir);

    if (!$files) {
        echo '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>';
        return;
    }

    $converted_count = 0;

    foreach ($files as $file) {
        $webp_path = $file . '.webp';
        $avif_path = $file . '.avif';

        // WebP 変換
        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $image = @imagecreatefromjpeg($file);
            } elseif ($ext === 'png') {
                $image = @imagecreatefrompng($file);
                imagepalettetotruecolor($image); // パレット PNG を変換
            } else {
                $image = false;
            }

            if ($image) {
                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
            }
        }

        // AVIF 変換（`avifenc` バイナリを使用）
        if (!file_exists($avif_path)) {
            $cmd = "avifenc --min " . escapeshellarg($avif_quality) . " --max " . escapeshellarg($avif_quality) . " " . escapeshellarg($file) . " " . escapeshellarg($avif_path);
            exec($cmd, $output, $result);
            if ($result !== 0) {
                neoerrorlog("AVIF 変換失敗: " . $file);
            }
        }

        $converted_count++;
    }

    echo '<div class="notice notice-success"><p>' . $converted_count . ' 件の画像を WebP / AVIF に変換しました。</p></div>';
}

function generate_webp_on_upload($metadata) {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'];
    $quality = get_option('webp_quality', 80);

    foreach ($metadata['sizes'] as $size) {
        $file_path = $base_dir . '/' . $size['file'];
        $webp_path = $file_path . '.webp';

        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $image = imagecreatefromstring(file_get_contents($file_path));
            if ($image) {
                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
            }
        }
    }
    return $metadata;
}
add_filter('wp_generate_attachment_metadata', 'generate_webp_on_upload');


/*  方法 ①: the_content フィルターで HTML を置換 */
/*
function replace_images_with_picture($content) {
    neoerrorlog("replace_images_with_picture() called");

    if (!isset($_SERVER['HTTP_ACCEPT'])) {
        neoerrorlog("HTTP_ACCEPT is not set");
        return $content;
    }

    $accept = $_SERVER['HTTP_ACCEPT'];
    neoerrorlog("HTTP_ACCEPT: " . $accept);

    if (strpos($accept, 'image/avif') === false) {
        neoerrorlog("Browser does not support AVIF");
    }
    if (strpos($accept, 'image/webp') === false) {
        neoerrorlog("Browser does not support WebP");
    }

    return preg_replace_callback('/<img\s+([^>]*?)src=["\']([^"\']+)\.(jpg|jpeg|png)["\']([^>]*?)>/i', function ($matches) {
        $src = $matches[2] . '.' . $matches[3];
        $webp_src = $src . '.webp';
        $avif_src = $src . '.avif';

        $webp_exists = file_exists(str_replace(site_url(), ABSPATH, $webp_src));
        $avif_exists = file_exists(str_replace(site_url(), ABSPATH, $avif_src));

        neoerrorlog("Checking: " . $src);
        neoerrorlog("WebP Exists: " . ($webp_exists ? "Yes" : "No"));
        neoerrorlog("AVIF Exists: " . ($avif_exists ? "Yes" : "No"));

        if ($webp_exists || $avif_exists) {
            $picture_tag = '<picture>';
            if ($avif_exists) {
                $picture_tag .= '<source srcset="' . $avif_src . '" type="image/avif">';
            }
            if ($webp_exists) {
                $picture_tag .= '<source srcset="' . $webp_src . '" type="image/webp">';
            }
            $picture_tag .= $matches[0] . '</picture>';
            return $picture_tag;
        }

        return $matches[0];
    }, $content);
}
add_filter('the_content', 'replace_images_with_picture');
*/

/* 方法 ②: WordPress の wp_get_attachment_image_attributes を変更 */

function convert_images_to_webp_avif($attr, $attachment, $size) {
    neoerrorlog("convert_images_to_webp_avif() called");

    if (!isset($attr['src'])) {
        neoerrorlog("No src attribute found in image");
        return $attr;
    }

    if (!isset($_SERVER['HTTP_ACCEPT'])) {
        neoerrorlog("HTTP_ACCEPT is not set");
        return $attr;
    }

    $accept = $_SERVER['HTTP_ACCEPT'];
    neoerrorlog("HTTP_ACCEPT: " . $accept);

    if (strpos($accept, 'image/avif') === false) {
        neoerrorlog("Browser does not support AVIF");
    }
    if (strpos($accept, 'image/webp') === false) {
        neoerrorlog("Browser does not support WebP");
    }

    $src = $attr['src'];
    $webp_src = $src . '.webp';
    $avif_src = $src . '.avif';

    $webp_exists = file_exists(str_replace(site_url(), ABSPATH, $webp_src));
    $avif_exists = file_exists(str_replace(site_url(), ABSPATH, $avif_src));

    neoerrorlog("Checking: " . $src);
    neoerrorlog("WebP Exists: " . ($webp_exists ? "Yes" : "No"));
    neoerrorlog("AVIF Exists: " . ($avif_exists ? "Yes" : "No"));

    if ($webp_exists || $avif_exists) {
        $attr['srcset'] = '';
        if ($avif_exists) {
            $attr['srcset'] .= $avif_src . ' 1x, ';
        }
        if ($webp_exists) {
            $attr['srcset'] .= $webp_src . ' 1x';
        }
        $attr['src'] = $avif_exists ? $avif_src : $webp_src;
    }

    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'convert_images_to_webp_avif', 10, 3);

/* for JS */
/*
function add_webp_avif_js_script() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (document.createElement("canvas").toDataURL("image/avif").indexOf("data:image/avif") === 0) {
            document.querySelectorAll("img").forEach(img => {
                let avifSrc = img.src + ".avif";
                fetch(avifSrc, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            img.src = avifSrc;
                        }
                    });
            });
        } else if (document.createElement("canvas").toDataURL("image/webp").indexOf("data:image/webp") === 0) {
            document.querySelectorAll("img").forEach(img => {
                let webpSrc = img.src + ".webp";
                fetch(webpSrc, { method: 'HEAD' })
                    .then(response => {
                        if (response.ok) {
                            img.src = webpSrc;
                        }
                    });
            });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'add_webp_avif_js_script');
*/

