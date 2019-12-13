#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Dq5studios;

use Dq5studios\PhpcsViewer\Runner;

require_once __DIR__ . "/vendor/autoload.php";

$runner = new Runner();
$runner->scan();
