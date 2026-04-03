<?php
// Redirector: forwards to the working inner copy at hpc/generate_pdf.php
// This ensures old cached links (from OPcache) still work
require_once __DIR__ . '/cards/generate_pdf.php';
