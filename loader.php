<?php
declare(strict_types=1);
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

$sniffs = [];
foreach ($sniff_list as $filename) {
    include_once $filename;
}
$class_check = get_declared_classes();
$sniff_classes = array_diff($class_check, $class_list);

foreach ($sniff_classes as $i => $sniff_class) {
    if (strpos($sniff_class, "Standards") === false) {
        continue;
    }
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

    $doc = file_get_contents($reflect->getFileName());
    preg_match("/\*\*\s+\*\s+([^@]*)(\*\s+@|\*\/)/sm", $doc, $matched);
    $desc = "Undocumented";
    if (!empty($matched) && count($matched) > 1) {
        $desc = preg_replace("/^\s+\*\s?|\s+$/m", "", $matched[1]);
        $desc = trim(str_replace("\n\n", "\n", $desc));
    }

    // Look for sub sniffs
    preg_match_all("/->add(Fixable)?(Error|Warning|Message)(OnLine)?\(([^,]*),([^,]*), *([^,)]*)/", $doc, $sub_sniff);
    $search_vars = [];
    $sub_sniff_list = [];
    if (!empty($sub_sniff[6])) {
        foreach ($sub_sniff[6] as $sniff) {
            $sub_sniff = trim($sniff, "' \n");
            if ($sub_sniff[0] === "\$") {
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
                    $sub_sniff = trim($sniff, "' \n");
                    $sub_sniff_list[] = $sub_sniff;
                }
            } else {
                $sub_sniff_list[] = "\${$search}";
            }
        }
    }
    sort($sub_sniff_list);
    $sub_sniff_list = array_unique($sub_sniff_list);

    // Parse xml documentation
    $example = [];
    $xml_name = str_replace(["/Sniffs/", "Sniff.php"], ["/Docs/", "Standard.xml"], $reflect->getFileName());
    if (file_exists($xml_name)) {
        $example = [];
        $xml = simplexml_load_file($xml_name, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml->code_comparison) {
            foreach ($xml->code_comparison->code as $value) {
                $key = (string) $value->attributes()[0];
                $example[$key] = trim((string) $value);
            }
        }
    }

    $parts = explode("\\", $sniff_class);
    $parts[5] = str_replace("Sniff", "", $parts[5]);
    $ref = "{$parts[2]}.{$parts[4]}.{$parts[5]}";
    $sniffs[] = [
        "name" => $ref,
        "desc" => $desc,
        "code" => $example,
        "opts" => $props,
        "sniffs" => $sub_sniff_list,
        "getname" => $reflect->getName(),
        "filename" => $reflect->getFileName(),
        "i" => $i
    ];
    // if ($ref == "Generic.PHP.DiscourageGoto") {
    // }
}

// var_dump($sniffs[56]);
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
        <meta charset="utf-8">
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
            integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
            integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
        </script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
            integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
            integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
        </script>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css"
            integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
        <!-- <link rel="stylesheet"
            href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/styles/default.min.css">
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.6/highlight.min.js"></script> -->
        <link rel="stylesheet" href="phpcs.css">
        <script src="phpcs.js"></script>
        <title>Smashing</title>
    </head>
    <body>
        <div class="container">
            <h1>PHP Code Sniffer Configuration</h1>
            <form autocomplete="off">
                <div class="form-group row sticky-top bg-white">
                    <label for="search_filter" class="col-1 col-form-label">Filter</label>
                    <div class="col-3">
                        <input type="text" class="form-control" id="search_filter">
                    </div>
                    <label for="search_filter" class="col-1 col-form-label">Version</label>
                    <div class="col-2">
                        <select class="custom-select">
                            <option value="3.4.0">V3.4.0</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <div class="custom-control custom-switch custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="enabled_only">
                            <label class="custom-control-label" for="enabled_only">Only enabled</label>
                        </div>
                    </div>
                    <div class="col-3 text-right">
                        <button type="button" class="btn btn-primary" id="import">Import
                        <i class="fas fa-file-upload"></i>
                        </button>
                        <button type="button" class="btn btn-primary" id="export">Export
                        <i class="fas fa-file-download"></i>
                        </button>
                    </div>
                </div>\n
HTML;
foreach ($sniffs as $sniff) {
    $code = "";
    preg_match_all("/<code>((.|\n)*)<\/code>/m", $sniff["desc"], $desc);
    if (count($desc) > 1 && !empty($desc[1])) {
        $desc = highlight_string("<?php{$desc[1][0]}?>", true);
        $sniff["desc"] = preg_replace("/<code>((.|\n)*)<\/code>/m", "", $sniff["desc"]);
        // $code = "<pre>{$desc}</pre>";
    }
    echo <<<HTML
    <div class="form-group row" data-sniff="{$sniff["name"]}">
        <div class="col-8">
            <h4>
                <div class="custom-control custom-switch">
                    <input class="custom-control-input" type="checkbox" id="{$sniff["name"]}" name="{$sniff["name"]}">
                    <label class="custom-control-label" for="{$sniff["name"]}">{$sniff["name"]}</label>\n
HTML;
    if (!empty($sniff["code"])) {
        echo <<<HTML
                    <i class="example_toggle fas fa-info-circle text-muted float-right"></i>
                    <div hidden>
                        <dl class="examples">
HTML;
        foreach ($sniff["code"] as $label => $example) {
            $example = str_replace(["&lt;em&gt;", "&lt;/em&gt;"], ["<kbd>", "</kbd>"], htmlentities($example));
            echo <<<HTML
            <dt class="form-group row">
                <span class="col">{$label}</span>
            </dt>
            <dd class="form-group row">
                <pre class="col"><code>{$example}</code></pre>
            </dd>\n
HTML;
        }
        echo <<<HTML
                        </dl>
                    </div>
HTML;
    }
    echo <<<HTML
                </div>
            </h4>
            <p>{$sniff["desc"]}</p>
            {$code}
            <dl class="subs" hidden>\n
HTML;
    if (!empty($sniff["sniffs"])) {
        echo <<<HTML
                <dd class="form-group row rules">
                    <div class="col-2">Rules</div>
                    <div class="col-10">\n
HTML;
        foreach ($sniff["sniffs"] as $sub) {
            echo <<<HTML
            <div class="form-group">
                <div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-outline-secondary">
                        <input type="radio" name="{$sniff["name"]}.{$sub}" id="{$sniff["name"]}.{$sub}.off" value="off">
                        Off
                    </label>
                    <label class="btn btn-outline-warning">
                        <input type="radio" name="{$sniff["name"]}.{$sub}" id="{$sniff["name"]}.{$sub}.warning" value="warning">
                        Warning
                    </label>
                    <label class="btn btn-outline-danger active">
                        <input type="radio" name="{$sniff["name"]}.{$sub}" id="{$sniff["name"]}.{$sub}.error" value="error" checked>
                        Error
                    </label>
                </div>
                <label class="col-check-label" for="{$sniff["name"]}.{$sub}.error">{$sub}</label>
            </div>
            <!-- <div class="custom-control custom-switch">
                <input class="custom-control-input" type="checkbox" id="{$sniff["name"]}.{$sub}">
                <label class="custom-control-label" for="{$sniff["name"]}.{$sub}">{$sub}</label>
            </div>-->\n
HTML;
        }
        echo <<<HTML
                    </div>
                </dd>\n
HTML;
    }
    if (!empty($sniff["opts"])) {
        foreach ($sniff["opts"] as $opt) {
            $type = "text";
            if (in_array($opt["type"], ["int", "integer"])) {
                $type = "number";
            }
            $input = <<<HTML
                <label class="col-3 col-form-label" for="{$sniff["name"]}[{$opt["name"]}]">{$opt["name"]}</label>
                <div class="col-6">
                    <input type="{$type}" class="form-control property" id="{$sniff["name"]}.{$opt["name"]}" name="{$sniff["name"]}.{$opt["name"]}">
                </div>
HTML;
            if (in_array($opt["type"], ["bool", "boolean"])) {
                $input = <<<HTML
                <div class="col-6">
                    <div class="custom-control custom-switch">
                        <input class="custom-control-input property" id="{$sniff["name"]}.{$opt["name"]}" name="{$sniff["name"]}.{$opt["name"]}" type="checkbox" value="1">
                        <label class="custom-control-label" for="{$sniff["name"]}.{$opt["name"]}">{$opt["name"]}</label>
                    </div>
                </div>
HTML;
            }
            echo <<<HTML
                    <dt class="form-group row">
                        <label for="{$sniff["name"]}[{$opt["name"]}]" class="col">{$opt["desc"]}</label>
                    </dt>
                    <dd class="form-group row">
                        {$input}
                    </dd>\n
HTML;
        }
    }
    echo <<<HTML
            </dl>
        </div>
        <div class="col">
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
