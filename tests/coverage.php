<?php

return new class extends phpunit_coverage_info {
    /** @var array The list of folders relative to the plugin root to whitelist in coverage generation. */
    protected $includelistfiles = [
        'classes',
    ];

    /** @var array The list of files relative to the plugin root to whitelist in coverage generation. */
    protected $includelistfolders = [];

    /** @var array The list of folders relative to the plugin root to excludelist in coverage generation. */
    protected $excludelistfolders = [];

    /** @var array The list of files relative to the plugin root to excludelist in coverage generation. */
    protected $excludelistfiles = [];
};