<?php
/**
 * Sniff structure
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
 * Sniff structure
 *
 * @category PHPCSView
 * @package  PHPCSView
 * @author   Ben Dusinberre <ben@dq5studios.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/dq5studios/phpcsviewer
 */
class Sniff
{
    /**
     * Sequence
     *
     * @var int
     */
    public $seq;

    /**
     * Sniff id
     *
     * @var string
     */
    public $id;

    /**
     * Description
     *
     * @var string
     */
    public $descrip;

    /**
     * Parent sniff
     *
     * @var string
     */
    public $parent;

    /**
     * Version added
     *
     * @var string
     */
    public $added;

    /**
     * Version removed
     *
     * @var string
     */
    public $removed;

    /**
     * Sniff options
     *
     * @var SniffOpt[]
     */
    public $opts;

    /**
     * Example documentation
     *
     * @var array
     */
    public $docs;

    /**
     * Get the sniff details, all or one
     *
     * @param int $seq Specific sniff
     *
     * @return Sniff[] Sniff details
     */
    public static function get($seq = null): array
    {
        $parm = [];

        $sql = "SELECT 'Sniff', seq, *
                  FROM sniff
              ORDER BY id";
        if (!empty($seq)) {
            $sql .= "\n\tWHERE seq = :seq";
            $parm[":seq"] = $seq;
        }

        $sniff_list = DB::factory()->query($sql, $parm);

        $opts = SniffOpt::get();
        foreach ($opts as $seq => $opt) {
            if (!array_key_exists($opt->sniff_seq, $sniff_list)) {
                continue;
            }
            $sniff_list[$opt->sniff_seq]->opts[$seq] = $opt;
        }
        return $sniff_list;
    }
}
