<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Component\DataSource\Driver\Doctrine\Extension\Core\Field;

use FSi\Component\DataSource\Driver\Doctrine\DoctrineFieldInterface;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\Field\Number as BaseNumber;

/**
 * Number field.
 * @deprecated since version 1.4
 */
class Number extends BaseNumber implements DoctrineFieldInterface
{
}
