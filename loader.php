<?php
/**
 * Get file list
 * crawl file list
 * include file
 * get_declared_classes
 * ReflectionClass
 * getProperties(ReflectionProperty::IS_PUBLIC)
 * build property list
 * ref = namespace + class - PHP_CodeSniffer\Standards
 */

spl_autoload_register(
    function ($class) {
        $class = str_replace("PHP_CodeSniffer", ".", $class);
        $class .= ".php";
        $class = str_replace("\\", "/", $class);
        include_once "../PHP_CodeSniffer/src/{$class}";
    }
);
require_once "../PHP_CodeSniffer/src/Util/Tokens.php";


$class_list = get_declared_classes();
$sniff_list = glob("../PHP_CodeSniffer/src/Standards/*/Sniffs/*/*.php");
$doc_list = glob("../PHP_CodeSniffer/src/Standards/*/Docs/*/*.xml");

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

    // Look for sub sniffs
    preg_match_all("/add(Fixable)?(Error|Warning|Message)(OnLine)?\(([^,]*),([^,]*), *([^,)]*)/", $doc, $sub_sniff);
    $search_vars = [];
    $sub_sniff_list = [];
    if (!empty($sub_sniff[6])) {
        foreach ($sub_sniff[6] as $sniff) {
            if ($sniff == "'Found'") {
                continue;
            }
            $sub_sniff = trim($sniff, "' \n");
            if ($sub_sniff[0] === '$') {
                $search_vars[] = substr($sub_sniff, 1);
                continue;
            }
            $sub_sniff_list[] = $sub_sniff;
        }
    }
    if (!empty($search_vars)) {
        foreach ($search_vars as $search) {
            preg_match_all("/{$search} +.?= +([^;]*);/", $doc, $sub_sniff);
            if (!empty($sub_sniff[1])) {
                foreach ($sub_sniff[1] as $sniff) {
                    if ($sniff == "'Found'") {
                        continue;
                    }
                    $sub_sniff = trim($sniff, "' \n");
                    $sub_sniff_list[] = $sub_sniff;
                }
            } else {
                $sub_sniff_list[] = '$' . $search;
            }
        }
    }
    sort($sub_sniff_list);
    $sub_sniff_list = array_unique($sub_sniff_list);

    $parts = explode("\\", $sniff_class);
    $parts[5] = str_replace("Sniff", "", $parts[5]);
    $ref = "{$parts[2]}.{$parts[4]}.{$parts[5]}";
    $sniffs[] = [
        "name" => $ref,
        "desc" => $desc,
        "opts" => $props,
        "sniffs" => $sub_sniff_list
    ];
}

// var_dump($sniffs[0]);
// exit;

// foreach ($sniffs as $sniff) {
//     echo "## {$sniff["name"]}\n";
//     $sniff["desc"] = str_replace(["<code>", "</code>"], ["```php", "```"], $sniff["desc"]);
//     echo "{$sniff["desc"]}\n";
//     if (!empty($sniff["opts"])) {
//         echo "> ### Options\n";
//         $c = count($sniff["opts"]);
//         foreach ($sniff["opts"] as $i => $opt) {
//             echo "> `{$opt["name"]}` _{$opt["type"]}_\\\n";
//             echo "> {$opt["desc"]}\n";
//             if ($i < ($c - 1)) {
//                 echo ">\n";
//             }
//         }
//     }
//     if (!empty($sniff["sniffs"])) {
//         echo "### Additional Sniffs\n";
//         foreach ($sniff["sniffs"] as $sub) {
//             echo "- {$sub}\n";
//         }
//     }
//     echo "\n";
// }

echo <<<HTML
<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="phpcs.css">
        <title>Smashing</title>
    </head>
    <body>
        <div class="container">
            <form>\n
HTML;
foreach ($sniffs as $sniff) {
    $sniff["desc"] = str_replace(["<code>", "</code>"], ["<pre><code>", "</code></pre>"], $sniff["desc"]);
    echo <<<HTML
    <div class="form-group">
        <div class="col-sm-6">
            <h4>
                <div class="custom-control custom-switch">
                    <input class="custom-control-input" type="checkbox" id="{$sniff["name"]}">
                    <label class="custom-control-label" for="{$sniff["name"]}">{$sniff["name"]}</label>
                </div>
            </h4>
            <p>{$sniff["desc"]}</p>
            <input class="hider" type="radio" name="{$sniff["name"]}" checked hidden>
            <dl>\n
HTML;
    if (!empty($sniff["sniffs"])) {
        echo <<<HTML
                <dd class="form-group row">
                    <div class="col-sm-2">Rules</div>
                    <div class="col-sm-10">\n
HTML;
        foreach ($sniff["sniffs"] as $sub) {
            echo <<<HTML
            <div class="custom-control custom-switch">
                <input class="custom-control-input" type="checkbox" id="{$sniff["name"]}.{$sub}">
                <label class="custom-control-label" for="{$sniff["name"]}.{$sub}">{$sub}</label>
            </div>\n
HTML;
        }
        echo <<<HTML
                </dd>\n
HTML;
    }
    if (!empty($sniff["opts"])) {
        foreach ($sniff["opts"] as $opt) {
            $type = "text";
            if (in_array($opt["type"], ["int", "integer"])) {
                $type = "number";
            }
            echo <<<HTML
                    <dt class="form-group row">
                        <span class="col-sm">{$opt["desc"]}</span>
                    </dt>
                    <dd class="form-group row">
                        <label class="col-sm-3 col-form-label">{$opt["name"]}</label>
                        <div class="col-sm-9">
                            <input type="{$type}" class="form-control">
                        </div>
                    </dd>\n
HTML;
        }
    }
    echo <<<HTML
            </dl>
        </div>
    </div>\n
HTML;
}
echo <<<HTML
            </form>
        </div>
    </body>
</html>
HTML;
