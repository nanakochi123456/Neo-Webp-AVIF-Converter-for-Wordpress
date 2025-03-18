<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('neowebp_webp_quality');
delete_option('neowebp_avif_quality');
delete_option('neowebp_avifenc_path');
delete_option('neowebp_avifenc_jobs');
