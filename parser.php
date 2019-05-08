<?php
/**
 * Parse the PHP_CodeSniffer source folder and create a
 * json file for later consumption
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

define("SOURCE", "../PHP_CodeSniffer/src/");

spl_autoload_register(
    function ($class) {
        $class = str_replace("PHP_CodeSniffer", ".", $class);
        $class .= ".php";
        $class = str_replace("\\", "/", $class);
        include_once SOURCE . $class;
    }
);

// Include all the sniff class files
$class_list = get_declared_classes();
require_once SOURCE . "Util/Tokens.php";
$sniff_list = glob(SOURCE  . "Standards/*/Sniffs/*/*.php");

foreach ($sniff_list as $filename) {
    include_once $filename;
}
$class_check = get_declared_classes();
// Get the list of classes that now exist
$sniff_classes = array_diff($class_check, $class_list);

$sniffs = [];
foreach ($sniff_classes as $i => $sniff_class) {

    // Not a standard
    if (strpos($sniff_class, "Standards") === false) {
        continue;
    }

    $sniff_detail = [];
    $reflect = new ReflectionClass($sniff_class);

    // Clean up sniff name
    $parts = explode("\\", $sniff_class);
    $parts[5] = str_replace("Sniff", "", $parts[5]);
    $sniff_detail["id"] = "{$parts[2]}.{$parts[4]}.{$parts[5]}";

    // Grab parent if it's extending another sniff
    $parent = null;
    if ($reflect->getParentClass()) {
        $parent_class = $reflect->getParentClass()->getName();
        if ($parent_class !== "Sniff"
            && strpos($parent_class, "Abstract") === false
        ) {
            $parent = $parent_class;
        }
    }
    if (!empty($parent)) {
        $parts = explode("\\", $parent);
        $parts[5] = str_replace("Sniff", "", $parts[5]);
        $parent = "{$parts[2]}.{$parts[4]}.{$parts[5]}";
    }
    $sniff_detail["parent"] = $parent;

    // Description
    $doc = file_get_contents($reflect->getFileName());
    preg_match("/\*\*\s+\*\s+([^@]*)(\*\s+@|\*\/)/sm", $doc, $matched);
    $desc = "Undocumented";
    if (!empty($matched) && count($matched) > 1) {
        $desc = preg_replace("/^\s+\*\s?|\s+$/m", "", $matched[1]);
        $desc = trim(str_replace("\n\n", "\n", $desc));
    }
    $sniff_detail["descrip"] = $desc;

    // Get class public properties as those are the sniff options
    $def_vals = $reflect->getDefaultProperties();
    $param_list = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
    $props = [];
    foreach ($param_list as $prop) {
        $ref = new ReflectionProperty($sniff_class, $prop->getName());
        if ($prop->getName() == "supportedTokenizers") {
            // Don't care to list
            continue;
        }
        $doc = $prop->getDocComment();
        preg_match("/\*\*\s+\*\s+([^@]*)(\*\s+@|\*\/)/sm", $doc, $matched);
        $desc = "Undocumented";
        if (!empty($matched) && count($matched) > 1) {
            $desc = preg_replace("/^\s+\*\s?|\s+$/m", "", $matched[1]);
            $desc = trim(str_replace("\n", " ", $desc));
        }
        preg_match("/@var\s+(\S+)(\s+([^*]*))?\s*$/m", $doc, $matched);
        if (!empty($matched) && (count($matched) === 2 || count($matched) === 4)) {
            $subdesc = trim(isset($matched[3]) ? $matched[3] : "");
            if (!empty($subdesc)) {
                $desc .= " ({$subdesc})";
            }
            $type = trim($matched[1]);
        }
        $props[] = [
            "name" => $prop->getName(),
            "desc" => $desc,
            "default" => $def_vals[$prop->getName()],
            "type" => $type
        ];
    }
    $sniff_detail["opt"] = $props;

    // <4.0 There are CSS & JS sniffs
    $sniff_detail["supported"] = ["PHP"];
    if (!empty($def_vals["supportedTokenizers"])) {
        $sniff_detail["supported"] = $def_vals["supportedTokenizers"];
    }

    $sniffs[$sniff_detail["id"]] = $sniff_detail;
}
sort($sniffs);

$version = PHP_CodeSniffer\Config::VERSION;

$json = json_encode(["version" => $version, "sniff" => $sniffs], JSON_PRETTY_PRINT);

file_put_contents("phpcs_v{$version}.json", $json);
