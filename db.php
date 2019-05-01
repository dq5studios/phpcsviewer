<?php
/**
 * DB wrapper
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

/**
 * DB wrapper
 *
 * @category PHPCSView
 * @package  PHPCSView
 * @author   Ben Dusinberre <ben@dq5studios.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/dq5studios/phpcsviewer
 */
class DB
{
    /**
     * Database handler
     *
     * @var PDO Database handler
     */
    private static $_dbh;

    /**
     * Do nothing, use ::factory() instead
     */
    public function __construct()
    {
    }

    /**
     * Create a PDO connection if needed
     *
     * @return self Database wrapper
     */
    public static function factory(): self
    {
        if (!is_null(self::$_dbh)) {
            return new self();
        }
        $user = $_SERVER["db_user"] ?? "username";
        $pass = $_SERVER["db_pass"] ?? "password";
        self::$_dbh = new PDO("pgsql:dbname=phpcsview;host=localhost", $user, $pass);
        self::$_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return new self();
    }

    /**
     * Query database
     *
     * @param string $sql  Query
     * @param array  $parm Bound parameters
     *
     * @return array Results
     */
    public function query(string $sql, array $parm = []): ?array
    {
        $sth = self::$_dbh->prepare($sql);

        // Bind
        foreach ($parm as $key => $value) {
            switch (gettype($value)) {

            case "integer":
                $type = PDO::PARAM_INT;
                break;
            case "boolean":
                $type = PDO::PARAM_BOOL;
                break;
            default:
                $type = PDO::PARAM_STR;
                break;
            }
            $sth->bindValue($key, $value, $type);
        }
        $sth->execute();

        $fetch_style = PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE | PDO::FETCH_UNIQUE;
        $results = $sth->fetchAll($fetch_style);
        if ($results === false) {
            return null;
        }

        return $results;
    }
}
