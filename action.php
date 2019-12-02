#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace dq5studios;

use dq5studios\phpcsviewer\Runner;

require_once __DIR__ . "/vendor/autoload.php";

$runner = new Runner();
$runner->scan();
