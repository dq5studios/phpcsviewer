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
}
