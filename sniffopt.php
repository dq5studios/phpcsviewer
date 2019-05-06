<?php
/**
 * Sniff_opt structure
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
 * Sniff_opt structure
 *
 * @category PHPCSView
 * @package  PHPCSView
 * @author   Ben Dusinberre <ben@dq5studios.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/dq5studios/phpcsviewer
 */
class SniffOpt
{
    /**
     * Sequence
     *
     * @var int
     */
    public $seq;

    /**
     * Sniff seq
     *
     * @var int
     */
    public $sniff_seq;

    /**
     * Name
     *
     * @var string
     */
    public $name;

    /**
     * Description
     *
     * @var string
     */
    public $descrip;

    /**
     * Default value
     *
     * @var string
     */
    public $def;

    /**
     * Data type
     *
     * @var string
     */
    public $type;

    /**
     * Get the sniff options
     *
     * @return SniffOpt[] Sniff details
     */
    public static function list(): array
    {
        $sql = "SELECT 'SniffOpt', seq, *
                  FROM sniff_opt";

        $sniff_opts = $db = DB::factory()->query($sql);
        return $sniff_opts;
    }
}
