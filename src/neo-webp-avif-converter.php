<?php
/**
 * Plugin Name: Neo WebP/AVIF Converter
 * Plugin URI: https://github.com/nanakochi123456/Neo-Webp-AVIF-Converter-for-Wordpress
 * Description: Automatically create WebP/AVIF and convert HTML
 * Version: 1.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Nano Yozakura
 * Author URI: https://773.moe
 * Text Domain: neo-webp-avif-converter
 * License: GPLv2 or later
 */

defined('ABSPATH') or die('Oh! No!');

$neowebp = new neowebp();
class neowebp {
	// 言語を取得
	protected function getlang() {
//		return "en";
		return get_bloginfo( 'language' );
	}

	// エラーログ
	protected function errorlog( $content ) {
		if( 0 ) {
			// error_log( $content );
		}
	}

	// WP_Filesystem を初期化
	protected $wp_filesystem;

	private function init_filesystem() {
		// ファイルシステムを初期化
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		$credentials = request_filesystem_credentials( site_url() );
		if ( ! WP_Filesystem( $credentials ) ) {
			return false; // 認証失敗
		}

		// WP_Filesystem_Direct を設定
		$this->wp_filesystem = $GLOBALS['wp_filesystem'];
	}

	// hooks
	public function __construct() {
		// WP_Filesystem の初期化
		$this->init_filesystem();

		add_action('admin_menu', array($this, 'neowebp_webp_converter_add_admin_menu'));
		add_action('admin_init', array($this, 'neowebp_webp_converter_register_settings'));
		add_action('admin_notices', array($this, 'neowebp_webp_converter_add_bulk_convert_button'));
		add_filter('wp_generate_attachment_metadata', array($this, 'neowebp_convert_uploaded_image_to_webp_avif'));
		add_filter('wp_get_attachment_image_attributes', array($this, 'neowebp_convert_images_to_webp_avif'), 10, 3);
	}

	// 再帰mkdir
	function neomkdir( $path ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// 既に存在している場合は true を返す
		if ( $wp_filesystem->is_dir( $path ) ) {
			return true;
		}

		// 親ディレクトリを再帰的に作成
		$parent = dirname( $path );
		if ( ! $wp_filesystem->is_dir( $parent ) ) {
			$this->neomkdir( $parent );
		}

		// ディレクトリを作成
		return $wp_filesystem->mkdir( $path );
	}

	// 管理画面のメニュー追加
	public function neowebp_webp_converter_add_admin_menu() {
		add_options_page( 'ja' === $this->getlang() ? 'Neo WebP/AVIF Converter 設定' : 'Neo WebP/AVIF Converter Setting', 'Neo-WebP/AVIF-Converter', 'manage_options', 'webp_converter', array( $this, 'neowebp_webp_converter_settings_page' ) );
	}

	// 設定ページの内容
	public function neowebp_webp_converter_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo 'ja' === $this->getlang() ? 'Neo WebP/AVIF Converter 設定' : 'Neo WebP/AVIF Converter Settings'?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'neowebp_webp_converter_options' );
				do_settings_sections( 'neowebp_webp_converter' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	// サニタイズ関数を定義
	public function sanitize_webp_quality($input) {
		// 0から100の範囲内であることを確認
		if ( ! is_numeric( $input ) || $input < 0 || $input > 100 ) {
			$input = 80; // 範囲外の場合はデフォルト値として 80 を設定
		}
		return intval( $input ); // 整数として返す
	}

	// サニタイズ関数を定義
	public function sanitize_avif_quality($input) {
		// 0から100の範囲内であることを確認
		if ( ! is_numeric( $input ) || $input < 0 || $input > 100 ) {
			$input = 30; // 範囲外の場合はデフォルト値として 30 を設定
		}
		return intval( $input ); // 整数として返す
	}

	// 設定オプションの登録
	public function neowebp_webp_converter_register_settings() {
		register_setting( 'neowebp_webp_converter_options', 'neowebp_webp_quality', 'sanitize_webp_quality' );
		register_setting( 'neowebp_webp_converter_options', 'neowebp_avif_quality', 'sanitize_avif_quality' );
		// コーディング規約によりavifencは削除
		// register_setting( 'neowebp_webp_converter_options', 'neowebp_avifenc_path' );
		// register_setting( 'neowebp_webp_converter_options', 'neowebp_avifenc_jobs' );

		//基本設定
		add_settings_section( 'neowebp_webp_converter_section', 'ja' === $this->getlang() ? '基本設定' : 'Basic Settings' , null, 'neowebp_webp_converter' );

		add_settings_field( 'neowebp_webp_quality', 'ja' === $this->getlang() ? 'WebP 画質 (0-100 80が標準)' : 'WebP Quality (0-100 Default 80)', array($this, 'neowebp_webp_quality_callback'), 'neowebp_webp_converter', 'neowebp_webp_converter_section' );
		add_settings_field( 'neowebp_avif_quality', 'ja' === $this->getlang() ? 'AVIF 画質 (0-100 30が標準)' : 'AVIF Quality (0-100 Default 30)', array($this, 'neowebp_avif_quality_callback'), 'neowebp_webp_converter', 'neowebp_webp_converter_section' );
		// コーディング規約によりavifencは削除
		//add_settings_field( 'neowebp_avifenc_path', 'ja' === $this->getlang() ? 'avifencの絶対PATH' : 'avifenc\'s Absolute Path', array($this, 'neowebp_avifenc_path_callback'), 'neowebp_webp_converter', 'neowebp_webp_converter_section' );
		//add_settings_field( 'neowebp_avifenc_jobs', 'ja' === $this->getlang() ? 'avifencの使用CPUコア数' : 'avifenc using CPU Cores used', array($this, 'neowebp_avifenc_jobs_callback'), 'neowebp_webp_converter', 'neowebp_webp_converter_section' );
	}

	// 画質設定の入力フィールド
	public function neowebp_webp_quality_callback() {
		$quality = get_option( 'neowebp_webp_quality', 80 );
		echo '<input type="number" name="neowebp_webp_quality" value="' . esc_html( $quality ) . '" min="0" max="100" />';
	}

	public function neowebp_avif_quality_callback() {
		$quality = get_option( 'neowebp_avif_quality', 30 );
		echo '<input type="number" name="neowebp_avif_quality" value="' . esc_html( $quality ) . '" min="0" max="100" />';
	}

	// avifencのパス、コーディング規約により無効
/**
	public function neowebp_avifenc_path_callback() {
		$avifenc_path = get_option( 'neowebp_avifenc_path', '' );
		echo 'ja' === $this->getlang() ? "<input type='text' name='neowebp_avifenc_path' value='$avifenc_path' /> 空欄でPATHを参照する" : "<input type='text' name='neowebp_avifenc_path' value='$avifenc_path' /> Refer to PATH if blank";
	}
**/

	// avifencのjobs、コーディング規約により無効
/**
	public function neowebp_avifenc_jobs_callback() {
		$avifenc_jobs = get_option( 'neowebp_avifenc_jobs', 1 );
		if( $avifenc_jobs < 1 ) {
			$avifenc_jobs = 1;
		}
		echo $this->getlang() == 'ja' ? "<input type='number' name='neowebp_avifenc_jobs' value='$avifenc_jobs' /> 実コア・契約コア数と同じ程度 レンタルサーバーでは最大4ぐらいにして下さい" : "<input type='number' name='neowebp_avifenc_jobs' value='$avifenc_jobs' /> Set it to be approximately the same as the actual cores and contracted cores. For rental servers, please limit it to a maximum of around 4.";
	}
**/

	// 一括変換のボタンを追加
	public function neowebp_webp_converter_add_bulk_convert_button() {
		?>
		<form method="post" action="">
			<input type="hidden" name="neowebp_webp_bulk_convert" value="1">
			<?php
			// Nonce を生成
			wp_nonce_field('neowebp_bulk_convert_action', 'neowebp_bulk_convert_nonce'); 

			// submitボタン
			submit_button('ja' === $this->getlang() ? '既存画像を WebP / AVIF に変換' : 'Convert existing images to WebP / AVIF'); 
			?>
		</form>
		<?php
		if ( isset($_POST['neowebp_webp_bulk_convert']) ) {
			// Nonce の検証
			if ( isset( $_POST['neowebp_bulk_convert_nonce'] ) && wp_verify_nonce ( sanitize_text_field ( wp_unslash( $_POST['neowebp_bulk_convert_nonce'] ) ), 'neowebp_bulk_convert_action') ) {
				// Nonce が正しい場合、処理を実行
				$this->neowebp_webp_avif_convert_existing_images();
			} else {
				// Nonce 検証が失敗した場合
				die('Forbidden');
			}
		}
	}

	// 画像をスキャンする
	protected function neowebp_get_all_images( $dir ) {
		$files = [];
		$items = scandir( $dir );
		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) continue;
			$path = $dir . '/' . $item;
			if ( is_dir($path) ) {
				$files = array_merge( $files, $this->neowebp_get_all_images( $path ) );
			} elseif ( preg_match('/\.(jpg|jpeg|png|JPG|JPEG|PNG)$/i', $item) ) {
				$files[] = $path;
			}
		}
		return $files;
	}

	protected function chkavifpath( $string ) {
		// 文字列が '/' で始まり、'avifenc' で終わるかをチェック
		if ( '/' === substr( $string, 0, 1 )  && 'avifenc' === substr( $string, -7 ) ) {
			return true;
		}
		return false;
	}

	// 既存画像をWebp/AVIFに変換
	protected function neowebp_webp_avif_convert_existing_images() {
		$this->errorlog( "webp_avif_convert_existing_images() called" );
		$upload_dir = wp_upload_dir();
		$theme_dir = get_theme_root();
		$base_dir = $upload_dir['basedir']; // /home/username/public_html/wp-content/uploads
		$compressed_dir_uploads = WP_CONTENT_DIR . '/compressed-image/uploads'; // 保存先を変更
		$compressed_dir_themes = WP_CONTENT_DIR . '/compressed-image/themes'; // 保存先を変更

		$webp_count_uploads=$this->neowebp_webp_convert( $base_dir, $compressed_dir_uploads );
		$webp_count_themes=$this->neowebp_webp_convert( $theme_dir, $compressed_dir_themes );

		$webp_converted_count = $webp_count_uploads + $webp_count_themes;

		$avif_count_uploads=$this->neowebp_avif_convert( $base_dir, $compressed_dir_uploads );
		$avif_count_themes=$this->neowebp_avif_convert( $theme_dir, $compressed_dir_themes );

		$avif_converted_count = $avif_count_uploads + $avif_count_themes;

		echo '<div class="notice notice-success"><p>' . esc_html( $webp_converted_count ) . ('ja' === $this->getlang() ? ' 件の画像を WebP に変換しました。' : ' image has been converted to WebP.') . '</p></div>';


		// コーディング規約により無効
/**
		// avifencが動作するか再確認
		$avifenc_path = get_option( 'neowebp_avifenc_path', '' );
		if( '' === $avifenc_path ) {
			$avifenc_path = 'avifenc';
		} elseif(false === chkavifpath( $avifenc_path ) ) {
			$avifenc_path = '/dev/null';
		}
**/

		$output = [];
		$result = 0;

		// コーディング規約により無効
/**
		if( ! (imagetypes() & IMG_AVIF) ) {

			exec("$avifenc_path 2>&1", $output, $result);
			if ( 127 === $result ) {
				echo 'ja' === $this->getlang() ? '<div class="notice notice-error"><p>avifencが動作していません。インストールされているかサーバーでexec()が許可されているか確認して下さい。</p></div>' : '<div class="notice notice-error"><p>avifenc is not working. Please check if it is installed and if exec() is allowed on the server.</p></div>';
				return;
			}
		}
**/
		if( ! (imagetypes() & IMG_AVIF) ) {
			echo 'ja' === $this->getlang() ? '<div class="notice notice-error"><p>imageavif関数が動作していません。php8.1以降、libgd 2.3.0が必要です。</p></div>' : '<div class="notice notice-error"><p>The imageavif function is not working. PHP 8.1 or later and libgd 2.3.0 or later are required.</p></div>';
			return;
		}
		echo '<div class="notice notice-success"><p>' . esc_html( $avif_converted_count ) . ('ja' === $this->getlang() ? ' 件の画像を AVIF に変換しました。' : ' image has been converted to AVIF.') . '</p></div>';
	}

	// 既存画像を WebPに変換
	protected function neowebp_webp_convert( $base_dir, $compressed_dir ) {
		$this->errorlog( "webp_convert() called" );

		// `compressed-image` ディレクトリがなければ作成
		if ( ! file_exists( $compressed_dir ) ) {
			$this->neomkdir( $compressed_dir, 0755 );
		}

		$files = $this->neowebp_get_all_images( $base_dir );

		if ( ! $files ) {
			echo 'ja' === $this->getlang() ? '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>' : '<div class="notice notice-error"><p>Image to convert not found.</p></div>';
			return;
		}

		$converted_count = 0;

		foreach ( $files as $file ) {
			$relative_path = str_replace( $base_dir, '', $file );
			$webp_path = $compressed_dir . $relative_path . '.webp';
			$avif_path = $compressed_dir . $relative_path . '.avif';
			$this->errorlog( 'webp_path:' . $webp_path );
			$this->errorlog( 'avif_path:' . $avif_path );
			// サブフォルダも作成
			$webp_folder = dirname( $webp_path );
			if ( ! file_exists( $webp_folder ) ) {
				$this->neomkdir( $webp_folder, 0755 );
			}
			$converted_count += $this->towebp( $file, $webp_path );
		}
		return $converted_count;
	}

	// WebP 変換
	protected function towebp( $file_path, $webp_path ) {
		$quality = get_option( 'neowebp_webp_quality', 80 );

		if ( ! file_exists( $webp_path ) && function_exists( 'imagewebp' ) ) {
			$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
			if ( 'jpg' === $ext || 'jpeg' === $ext ) {
				$image = @imagecreatefromjpeg( $file_path );
			} elseif ( 'png' === $ext ) {
				$image = @imagecreatefrompng( $file_path );
				imagepalettetotruecolor( $image ); // パレット PNG を変換
			} else {
				$image = false;
			}

			if ( $image ) {
				imagewebp( $image, $webp_path, $quality );
				imagedestroy( $image );
				return 1;
			}
		}
		return 0;
	}

	// AVIF 変換
	protected function toavif( $file_path, $avif_path ) {
		$converted_count = 0;
		$avif_quality = get_option( 'neowebp_avif_quality', 30 ); // AVIF は 30 くらいが推奨
		// コーディング規約により無効
/*
		$avifenc_path = get_option( 'neowebp_avifenc_path', '' );
		$avifenc_jobs = get_option( 'neowebp_avifenc_jobs', 1 );

		// AVIF 変換（`avifenc` バイナリを使用）
		if ( ! file_exists($avif_path) ) {
			if( $avifenc_path === '' ) {
				$avifenc_path = "avifenc";
			}
			$cmd = $avifenc_path . " -j " . $avifenc_jobs . " --min " . " " . escapeshellarg( $avif_quality ) . " --max " . escapeshellarg( $avif_quality ) . " " . escapeshellarg( $file_path ) . " " . escapeshellarg( $avif_path );
			$this->errorlog( "cmd:". $cmd );
			exec( $cmd, $output, $result );
			if ( 0 === $result ) {
				$converted_count++;
			} else {
*/
		// AVIF 変換（phpを使用）
		if ( ! file_exists($avif_path) ) {
			$this->errorlog( 'exec: toavif' );

			if ( imagetypes() & IMG_AVIF) {
				$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
				if ( 'jpg' === $ext || 'jpeg' === $ext ) {
					$image = @imagecreatefromjpeg( $file_path );
				} elseif ( 'png' === $ext ) {
					$image = @imagecreatefrompng( $file_path );
					imagepalettetotruecolor($image); // パレット PNG を変換
				} else {
					$image = false;
				}
				imageavif( $image, $avif_path, $avif_quality );
				imagedestroy( $image );
				$converted_count++;
			} else {
				$this->errorlog( "AVIF conversion failed : " . $file_path );
			}
		}
		return $converted_count;
	}

	// 既存画像を AVIFに変換
	protected function neowebp_avif_convert( $base_dir, $compressed_dir ) {

		// `compressed-image` ディレクトリがなければ作成
		if ( ! file_exists($compressed_dir) ) {
			$this->neomkdir( $compressed_dir, 0755 );
		}

		$files = $this->neowebp_get_all_images( $base_dir );

		if ( ! $files ) {
			echo 'ja' === $this->getlang() ? '<div class="notice notice-error"><p>変換する画像が見つかりません。</p></div>' : '<div class="notice notice-error"><p>Image to convert not found.</p></div>';
			return;
		}

		$converted_count = 0;

		foreach ( $files as $file ) {
			$relative_path = str_replace( $base_dir, '', $file );
			$avif_path = $compressed_dir . $relative_path . '.avif';
			$this->errorlog('avif_path:' . $avif_path );
			// サブフォルダも作成
			$avif_folder = dirname( $avif_path );
			if ( ! file_exists( $avif_folder ) ) {
				$this->neomkdir( $avif_folder, 0755 );
			}

			// AVIF 変換（`avifenc` バイナリを使用）
			$converted_count += $this->toavif( $file, $avif_path );
		}
		return $converted_count;
	}

	// アップロード時に WebP / AVIF に変換する
	public function neowebp_convert_uploaded_image_to_webp_avif( $metadata ) {
		$upload_dir = wp_upload_dir();
		// フルパスを生成
		$file_path = trailingslashit( $upload_dir['path'] ) . $size['file']; 
		// 年月を取得
		preg_match( '/(\d{4})\/(\d{2})\//', $file_path, $matches );
		if ( ! $matches ) {
			$this->errorlog( '[Error]Failed to retrieve the year and month from the file path : ' . $file_path);
		}
		$year  = $matches[1]; // 例: 2025
		$month = $matches[2]; // 例: 02
		$ymdir = '/' . $year . '/'	. $month . '/';
		$base_dir = $upload_dir['basedir'];  // /wp-content/uploads
		$compressed_dir = WP_CONTENT_DIR . '/compressed-image/uploads'; // WebP / AVIF の保存先

		// `compressed-image` ディレクトリがなければ作成
		if ( ! file_exists($compressed_dir) ) {
			$this->neomkdir($compressed_dir, 0755);
		}

		// オリジナルファイルを圧縮
		$file_path = $base_dir . '/' . $metadata['file']; // 
		$relative_path = str_replace($base_dir, '', $file_path);
		$webp_path = $compressed_dir . $relative_path . '.webp';
		$avif_path = $compressed_dir . $relative_path . '.avif';
		$this->errorlog("Original File:".$file_path);
		$this->errorlog("webp path:".$webp_path);
		$this->errorlog("webp path:".$avif_path);

		// サブフォルダも作成
		$folder_path = dirname($webp_path);
		if ( ! file_exists($folder_path) ) {
			$this->neomkdir($folder_path, 0755);
		}

		// WebP 変換
		$this->towebp($file_path, $webp_path);

		// AVIF 変換（`avifenc` コマンドを使用）
		$this->toavif($file_path, $avif_path);


		// サイズごとのファイルを圧縮
		$base_dir = $upload_dir['basedir'] . $ymdir . '/';	// /wp-content/uploads
		$compressed_dir = WP_CONTENT_DIR . '/compressed-image/uploads' . $ymdir . '/'; // WebP / AVIF の保存先
		foreach ( $metadata['sizes'] as $size ) {
			$file_path = $base_dir . $size['file'];
			$relative_path = str_replace($base_dir, '', $file_path);
			$webp_path = $compressed_dir . $relative_path . '.webp';
			$avif_path = $compressed_dir . $relative_path . '.avif';

			// WebP 変換
			$this->towebp($file_path, $webp_path);

			// AVIF 変換（`avifenc` コマンドを使用）
			$this->toavif($file_path, $avif_path);
		}

		return $metadata;
	}

	// 記事のHTML変換 (picture)
	public function neowebp_convert_images_to_webp_avif( $attr, $attachment, $size ) {
		$this->errorlog("convert_images_to_webp_avif() called");

		$upload_dir = wp_upload_dir();
		$theme_dir = get_theme_root();

		if ( ! isset($attr['src']) ) {
			$this->errorlog("No src attribute found in image");
			return $attr;
		}

		if ( ! isset($_SERVER['HTTP_ACCEPT']) ) {
			$this->errorlog("HTTP_ACCEPT is not set");
			return $attr;
		}

		$accept = esc_html( sanitize_text_field ( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) );
		$this->errorlog("HTTP_ACCEPT: " . $accept);

		if ( false === strpos($accept, 'image/avif') ) {
			$this->errorlog("Browser does not support AVIF");
		}
		if ( false === strpos($accept, 'image/webp') ) {
			$this->errorlog("Browser does not support WebP");
		}

		$src = $attr['src'];
		$wp_dir = ABSPATH;
		$upload_dir = $upload_dir['basedir'];
		$theme_dir = get_theme_root();
		$upload_dir = str_replace($wp_dir, '',$upload_dir);
		$theme_dir = str_replace($wp_dir, '', $theme_dir);
		$compressed_dir_uploads = WP_CONTENT_DIR . '/compressed-image/uploads'; // 保存先を変更
		$compressed_dir_themes = WP_CONTENT_DIR . '/compressed-image/themes';
		$compressed_dir_uploads = str_replace($wp_dir, '',$compressed_dir_uploads);
		$compressed_dir_themes = str_replace($wp_dir, '', $compressed_dir_themes);
		$webp_src = $src . '.webp';
		$avif_src = $src . '.avif';

		$webp_src = str_replace($upload_dir, $compressed_dir_uploads, $webp_src);
		$avif_src = str_replace($upload_dir, $compressed_dir_uploads, $avif_src);
		$webp_src = str_replace($theme_dir, $compressed_dir_themes, $webp_src);
		$avif_src = str_replace($theme_dir, $compressed_dir_themes, $avif_src);

		$this->errorlog("webp_src: " . $webp_src);
		$this->errorlog("avif_src: " . $avif_src);

		$webp_exists = file_exists(str_replace(site_url(), ABSPATH, $webp_src));
		$avif_exists = file_exists(str_replace(site_url(), ABSPATH, $avif_src));

		$this->errorlog("Checking: " . $src);
		$this->errorlog("WebP Exists: " . ($webp_exists ? "Yes" : "No"));
		$this->errorlog("AVIF Exists: " . ($avif_exists ? "Yes" : "No"));

		if ( $webp_exists || $avif_exists ) {
			$attr['srcset'] = '';
			if ( $avif_exists ) {
				$attr['srcset'] .= $avif_src . ' 1x, ';
			}
			if ( $webp_exists ) {
				$attr['srcset'] .= $webp_src . ' 1x';
			}
			//$attr['src'] = $avif_exists ? $avif_src : $webp_src;
		}

		return $attr;
	}
}
