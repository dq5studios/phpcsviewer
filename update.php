<?php
/**
 * Parse the json file and compare to what is in the database to allow
 * selective updating of the database
 *
 * PHP version 7.3+
 *
 * @category  PHPCSView
 * @package   PHPCSView
 * @author    Ben Dusinberre <ben@dq5studios.com>
 * @copyright 2019 Ben Dusinberre
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/dq5studios/phpcsviewer
 */

declare(strict_types=1);

spl_autoload_register();

try {
    $db = DB::factory();
} catch(Exception $e) {
    echo "Unavailable", PHP_EOL;
    exit;
}


// hardcoded local file for testing
$filename = "phpcs_v3.4.1.json";

$pending = json_decode(file_get_contents($filename));

$sniff_list = Sniff::list();
$sniff_id_list = array_column($sniff_list, "id", "seq");

$pend_ver = $pending->version;

$pending_sniffs = array_column($pending, "id");
$new_sniffs = array_diff($pending_sniffs, $sniff_id_list);
$update_sniffs = array_intersect($pending_sniffs, $sniff_id_list);
