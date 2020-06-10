<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Interop\Fixture;

use Daikon\Interop\FromNativeInterface;
use Daikon\Interop\FromToNativeTrait;
use Daikon\Interop\MakeEmptyInterface;
use Daikon\Interop\ToNativeInterface;

final class MockValue implements FromNativeInterface, ToNativeInterface, MakeEmptyInterface
{
    use FromToNativeTrait;

    private ?string $value;

    public static function makeEmpty(): self
    {
        return new self;
    }

    public function isEmpty(): bool
    {
        return !isset($this->value);
    }

    public static function customFactory(array $state): self
    {
        return new self($state['custom']);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    private function __construct(string $value = null)
    {
        $this->value = $value;
    }
}
