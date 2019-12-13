<?php

declare(strict_types=1);

namespace Dq5studios\PhpcsViewer;

use PHP_CodeSniffer\Config;
use PhpParser\ParserFactory;

/**
 * Perform some actions
 */
class Runner
{
    /**
     * Scan the local phpcs install
     */
    public function scan(): void
    {
        $sniffs = [];
        $sniff_list = glob(__DIR__ . "/../vendor/squizlabs/php_codesniffer/src/Standards/*/Sniffs/*/*.php");
        if (empty($sniff_list)) {
            echo "No sniffs found", PHP_EOL;
            return;
        }

        $version = Config::VERSION;

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        foreach ($sniff_list as $filename) {
            $code = file_get_contents($filename);
            if (empty($code)) {
                echo "Could not parse {$filename}", PHP_EOL;
                continue;
            }
            $file_stmts = $parser->parse($code);
            if (empty($file_stmts)) {
                echo "Could not parse {$filename}", PHP_EOL;
                continue;
            }

            // Grab the primary statement and get to work
            $file_ast = array_shift($file_stmts);
            assert($file_ast instanceof \PhpParser\Node\Stmt\Namespace_);
            $namespace_stmt = $file_ast->name;
            assert($namespace_stmt instanceof \PhpParser\Node\Name);

            $standard = $namespace_stmt->parts[2];
            $category = $namespace_stmt->parts[4];

            // Cleanup file docblock
            $file_docblock = $file_ast->getDocComment();
            if ($file_docblock instanceof \PhpParser\Comment\Doc) {
                $file_docblock = (string) (preg_replace("/^(\s+\*\s@.*$|\s+\*\s|\/\*\*|\s+\*\/)|\s+$/m", "", $file_docblock->getText()) ?? "Undocumented");
                $file_docblock = trim(str_replace("\n\n", "\n", $file_docblock));
            }

            $class_stmt = null;
            foreach ($file_ast->stmts as $stmt) {
                if ($stmt->getType() == "Stmt_Class") {
                    $class_stmt = $stmt;
                }
            }
            if (empty($class_stmt)) {
                continue;
            }
            assert($class_stmt instanceof \PhpParser\Node\Stmt\Class_);
            $classname = (string) $class_stmt->name;
            $sniff_name = "{$standard}.{$category}.{$classname}";

            // We only care if the parent is a sniff
            $parent = $class_stmt->extends;
            if ($parent instanceof \PhpParser\Node\Name) {
                if ($parent != "Sniff" && strpos($parent->toCodeString(), "Abstract") === false) {
                    $parent = $parent->toCodeString();
                } else {
                    $parent = null;
                }
            }

            // Find any config options
            $options = [];
            foreach ($class_stmt->stmts as $stmt) {
                if ($stmt->getType() != "Stmt_Property") {
                    continue;
                }
                assert($stmt instanceof \PhpParser\Node\Stmt\Property);
                if (!$stmt->isPublic()) {
                    continue;
                }
                $property = array_shift($stmt->props);
                assert($property instanceof \PhpParser\Node\Stmt\PropertyProperty);
                if ($property->name->toString() == "supportedTokenizers") {
                    continue;
                }

                $doc = (string) ($stmt->getDocComment() ?? "");
                $type = "mixed";
                preg_match("/\*\*\s+\*\s+([^@]*)(\*\s+@|\*\/)/sm", $doc, $matched);
                $desc = "Undocumented";
                if (!empty($matched) && count($matched) > 1) {
                    $desc = preg_replace("/^\s+\*\s?|\s+$/m", "", $matched[1]);
                    assert(is_string($desc));
                    $desc = trim(str_replace("\n", " ", $desc));
                }
                preg_match("/@var\s+(.*)$/m", $doc, $matched);
                if (!empty($matched) && count($matched) > 1) {
                    $type = trim($matched[1]);
                }

                // Calculate default value
                $default = null;
                $default_stmt = $property->default;
                if ($default_stmt instanceof \PhpParser\Node\Expr) {
                    switch ($default_stmt->getType()) {
                        case "Scalar_LNumber":
                            assert($default_stmt instanceof \PhpParser\Node\Scalar\LNumber);
                            $default = $default_stmt->value;
                            break;
                        case "Scalar_String":
                            assert($default_stmt instanceof \PhpParser\Node\Scalar\String_);
                            $default = $default_stmt->value;
                            break;
                        case "Expr_Array":
                            assert($default_stmt instanceof \PhpParser\Node\Expr\Array_);
                            $contents = $default_stmt->items;
                            $default = [];
                            foreach ($contents as $default_value) {
                                assert($default_value instanceof \PhpParser\Node\Expr\ArrayItem);
                                $array_value = $default_value->value;
                                if ($array_value instanceof \PhpParser\Node\Scalar\String_) {
                                    $array_value = $array_value->value;
                                }
                                if ($array_value instanceof \PhpParser\Node\Expr\ConstFetch) {
                                    $array_value = $array_value->name->toCodeString();
                                }
                                $array_key = $default_value->key;
                                if ($array_key instanceof \PhpParser\Node\Scalar\String_) {
                                    $default[$array_key->value] = $array_value;
                                } else {
                                    $default[] = $array_value;
                                }
                            }
                            break;
                        case "Expr_ConstFetch":
                            assert($default_stmt instanceof \PhpParser\Node\Expr\ConstFetch);
                            $default = $default_stmt->name->toCodeString();
                            break;
                        default:
                            $default = $default_stmt;
                    }
                }

                $options[] = [
                    "name" => $property->name->toString(),
                    "desc" => $desc,
                    "default" => $default,
                    "type" => $type
                ];
            }

            // Parse xml documentation
            $example = [];
            $xml_name = str_replace(["/Sniffs/", "Sniff.php"], ["/Docs/", "Standard.xml"], realpath($filename));
            if (file_exists($xml_name)) {
                $example = [];
                $xml = simplexml_load_file($xml_name, "SimpleXMLElement", LIBXML_NOCDATA);
                if ($xml->code_comparison && $xml->code_comparison->code) {
                    foreach ($xml->code_comparison->code as $value) {
                        $key = (string) $value->attributes()[0];
                        $example[$key] = trim((string) $value);
                    }
                }
            }

            $sniffs[$sniff_name] = [
                "name" => $sniff_name,
                "parent" => $parent,
                "desc" => $file_docblock,
                "code" => $example,
                "opts" => $options,
                "fqdn" => $namespace_stmt->toCodeString(),
                "classname" => $classname,
                "filename" => realpath($filename),
            ];
        }

        echo print_r($sniffs, true);
    }
}
