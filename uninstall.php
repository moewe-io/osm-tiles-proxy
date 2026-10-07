<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

foreach ([
             'osm-tiles-proxy-rest-api-enabled',
             'osm-tiles-proxy-cache-enabled',
             'osm-tiles-proxy-osm-url',
             'osm_tiles_proxy_min_zoom',
             'osm_tiles_proxy_max_zoom',
             'osm_tiles_proxy_min_x',
             'osm_tiles_proxy_max_x',
             'osm_tiles_proxy_min_y',
             'osm_tiles_proxy_max_y',
             'osm_tiles_proxy_out_of_range_image_url',
             'osm_tiles_proxy_download_error_image_url',
         ] as $option) {
    delete_option($option);
}

$osm_tiles_proxy_cache = WP_CONTENT_DIR . '/cache/osm-tiles';

if (is_dir($osm_tiles_proxy_cache)) {
    $osm_tiles_proxy_files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($osm_tiles_proxy_cache, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($osm_tiles_proxy_files as $osm_tiles_proxy_file) {
        $osm_tiles_proxy_file->isDir() && !$osm_tiles_proxy_file->isLink()
            ? rmdir($osm_tiles_proxy_file->getPathname())
            : wp_delete_file($osm_tiles_proxy_file->getPathname());
    }
    rmdir($osm_tiles_proxy_cache);
}
