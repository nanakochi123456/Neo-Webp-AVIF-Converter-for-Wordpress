# Free Wordpress webp/AVIF Converter

## Features
100% Pure ChatGPT Based Code<br>
Compatible with nginx+fpm environment for WordPress<br>
No need to modify nginx.conf or similar files"<br>
No need for mod_rewrite or .htaccess<br>
Faster performance as no redirects occur even in Apache (or nginx+Apache) environments<br>
Replace images with img srcset

## install
making directory WPROOT/wp-content/plugins/Neo-WebP-Converter
- Copy the files in the src/ folder and activate them

## Settings
The settings screen is located under Settings → Neo Webp/AVIF Converter

## About avif
AVIF conversion is only supported on PHP 8.1 or later and libgd 2.3.0 or later.

sudo apt install libgd-dev

Please install it as well.

Alternatively, it cannot be used if the avifenc command is not in the PATH.

sudo apt install libavif-bin<br>
sudo pkg install libavif

Please install it as well.

## About Compression
Using avifenc not only puts a heavy load on the server, but it also causes a 504 error when there are many files.

Please compress it again.

## Uninstall

Disable and delete.

Delete all of wp-content/compressed-image.

## Version History
v1.0 - Registered in the WordPress directory, security strengthened. As exec cannot be used, support for avifenc has been discontinued. Please use the version linked here: GitHub Commit : Registered in the WordPress directory, security strengthened. As exec cannot be used, support for avifenc has been discontinued. Please use the version linked here: GitHub Commit : https://github.com/nanakochi123456/Neo-Webp-AVIF-Converter-for-Wordpress/commit/6d0a27ebd9c30406205fc85e295328cdbefee0c1#diff-b5875aebd416d1596845a822436c7a2bca2a865a800bec54d24ee17c47842ff1

v0.99 - Classified for release, simplified internationalization

v0.34 - Created uninstall.php; note that *.webp and *.avif images will not be deleted.

v0.33 - Added support for AVIF conversion using the PHP encoder.

v0.32 - Added error output when avifenc is not found during manual batch conversion.

v0.31 - Added support for specifying the number of jobs with the -j option in avifenc.

v0.30 - Dynamic conversion during media upload.

v0.23 - Made it possible to configure the path for avifenc, allowing it to work on user permission servers. Split the conversion process for WebP and AVIF, and ensured normal operation even without avifenc.

v0.22 - Made significant changes, particularly fixing issues where fetching from websites (such as blog rankings, SNS, etc.) would fail if WebP/AVIF was not supported.

v0.21 - Changed the directory for compressed image files, with theme support.

v0.2 - Support avif

v0.1 - First version
