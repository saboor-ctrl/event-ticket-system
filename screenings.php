<?php

function sts_get_screenings() {
    delete_transient('sts_migration_done');
    wp_cache_flush();
    return get_option('sts_screenings', []);
}

