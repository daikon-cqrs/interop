<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use Assert\InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements DaikonException
{
    /** @param mixed $value */
    public function __construct(
        string $message = '',
        int $code = 0,
        string $propertyPath = null,
        $value = null,
        array $constraints = []
    ) {
        parent::__construct($message, $code, $propertyPath, $value, $constraints);
    }
}
