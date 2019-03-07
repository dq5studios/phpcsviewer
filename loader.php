<?php

// get file list
// crawl file list
// include file
// get_declared_classes
// ReflectionClass
// getProperties(ReflectionProperty::IS_PUBLIC)
// build property list
// ref = namespace + class - PHP_CodeSniffer\Standards
//

spl_autoload_register(
    function ($class) {
        $class = str_replace("PHP_CodeSniffer", ".", $class);
        $class .= ".php";
        $class = str_replace("\\", "/", $class);
        include_once $class;
    }
);
require_once "Util/Tokens.php";


$class_list = get_declared_classes();
$sniff_list = glob("Standards/*/Sniffs/*/*.php");
$doc_list = glob("Standards/*/Docs/*/*.xml");

$sniffs = [];
foreach ($sniff_list as $filename) {
    include_once $filename;
    $class_check = get_declared_classes();
    $sniff_class = array_diff($class_check, $class_list);
    $sniff_class = array_pop($sniff_class);

    $reflect = new ReflectionClass($sniff_class);
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

    $doc = file_get_contents($filename);
    preg_match("/\*\*\s+\*\s+([^@]*)(\*\s+@|\*\/)/sm", $doc, $matched);
    $desc = "Undocumented";
    if (!empty($matched) && count($matched) > 1) {
        $desc = preg_replace("/^\s+\*\s?|\s+$/m", "", $matched[1]);
        $desc = trim(str_replace("\n\n", "\n", $desc));
    }

    $parts = explode("\\", $sniff_class);
    $parts[5] = str_replace("Sniff", "", $parts[5]);
    $ref = "{$parts[2]}.{$parts[4]}.{$parts[5]}";
    $sniffs[] = [
        "name" => $ref,
        "desc" => $desc,
        "opts" => $props
    ];
}

// var_dump($sniffs[14]);

foreach ($sniffs as $j => $sniff) {
    echo "## {$sniff["name"]}\n";
    $sniff["desc"] = str_replace(["<code>", "</code>"], ["```php", "```"], $sniff["desc"]);
    echo "{$sniff["desc"]}\n";
    if (!empty($sniff["opts"])) {
        echo "> ### Options\n";
        $c = count($sniff["opts"]);
        foreach ($sniff["opts"] as $i => $opt) {
            echo "> `{$opt["name"]}` _{$opt["type"]}_\\\n";
            echo "> {$opt["desc"]}\n";
            if ($i < ($c - 1)) {
                echo ">\n";
            }
        }
    }
    echo "\n";
}
