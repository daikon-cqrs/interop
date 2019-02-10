<?php
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\Interop;

interface ValueObjectInterface extends FromNativeInterface, ToNativeInterface
{
    public function equals(ValueObjectInterface $value): bool;

    public function __toString(): string;
}
