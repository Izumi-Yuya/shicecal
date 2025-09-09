<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TCPDF Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for TCPDF PDF generation library.
    |
    */

    // Page settings
    'page_format' => 'A4',
    'page_orientation' => 'P',
    'page_units' => 'mm',

    // Character encoding
    'unicode' => true,
    'encoding' => 'UTF-8',

    // Directories
    'font_directory' => '',
    'image_directory' => '',

    // Features
    'tcpdf_throw_exception' => false,
    'use_fpdi' => false,
    'use_original_header' => false,
    'use_original_footer' => false,
    'pdfa' => false, // PDF/A compliance: false, 1, or 3
];
