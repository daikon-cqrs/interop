<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use Assert\AssertionFailedException as BaseAssertionFailedException;

interface AssertionFailedException extends BaseAssertionFailedException, DaikonException
{
}
