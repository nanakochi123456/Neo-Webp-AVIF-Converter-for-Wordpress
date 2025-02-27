<?php
/**
 * Plugin Name: Neo-WebP-Converter
 * Description: 自動で WebP を作成し、HTML を変換する
 * Version: 0.1
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
        <?php submit_button('既存画像を WebP に変換'); ?>
    </form>
    <?php
    if (isset($_POST['webp_bulk_convert'])) {
        webp_convert_existing_images();
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

// 既存画像を WebP に変換
function webp_convert_existing_images() {
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir']; // /home/username/public_html/wp-content/uploads
    $quality = get_option('webp_quality', 80);
//    $files = glob($base_dir . '/**/*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
  $files = get_all_images($base_dir);
    if (!$files) {
        echo '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>';
        return;
    }

    $converted_count = 0;

    foreach ($files as $file) {
        $webp_path = $file . '.webp';

        if (!file_exists($webp_path) && function_exists('imagewebp')) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $image = @imagecreatefromjpeg($file);
            } elseif ($ext === 'png') {
                $image = @imagecreatefrompng($file);
                imagepalettetotruecolor($image); // パレット PNG の場合は変換
            } else {
                $image = false;
            }

            if ($image) {
                imagewebp($image, $webp_path, $quality);
                imagedestroy($image);
                $converted_count++;
            } else {
                error_log("WebP 変換失敗: " . $file); // ログに記録
            }
        }
    }

    echo '<div class="notice notice-success"><p>' . $converted_count . ' 件の画像を WebP に変換しました。</p></div>';
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


/*  方法 1: .jpg.webp を自動的に出力する（フィルターを使用） */
/*
function replace_images_with_webp($content) {
    if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $content = preg_replace_callback('/<img\s+[^>]*src=["\']([^"\']+)\.(jpg|jpeg|png)["\'][^>]*>/i', function ($matches) {
            $webp_url = $matches[1] . '.' . $matches[2] . '.webp';

            if (file_exists(str_replace(site_url(), ABSPATH, $webp_url))) {
                return str_replace($matches[1] . '.' . $matches[2], $webp_url, $matches[0]);
            } else {
                return $matches[0]; // WebP がない場合はそのまま
            }
        }, $content);
    }
    return $content;
}
add_filter('the_content', 'replace_images_with_webp');
*/

/* 方法 2: <picture> タグを自動挿入 */
function convert_images_to_webp($attr, $attachment, $size) {
    if (isset($attr['src']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
        $webp_url = $attr['src'] . '.webp';
        $webp_path = str_replace(site_url(), ABSPATH, $webp_url);

        if (file_exists($webp_path)) {
            $original_src = $attr['src'];
            $attr['src'] = $webp_url;
            $attr['srcset'] = $webp_url;
            $attr['data-original'] = $original_src; // デバッグ用
        }
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'convert_images_to_webp', 10, 3);


/* 方法3：JavaScript
function add_webp_js_script() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (document.createElement("canvas").toDataURL("image/webp").indexOf("data:image/webp") == 0) {
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
add_action('wp_footer', 'add_webp_js_script');
*/
