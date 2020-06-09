<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Interop;

use Daikon\Interop\InvalidArgumentException;
use Daikon\Tests\Interop\Fixture\MockValue;
use PHPUnit\Framework\TestCase;
use TypeError;

class FromToNativeTest extends TestCase
{
    public function testMakeEmpty(): void
    {
        $mock = MockValue::makeEmpty();
        $this->assertNull($mock->getValue());
    }

    public function testFromNativeWithNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MockValue::fromNative(null);
    }

    public function testFromNativeWithScalar(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MockValue::fromNative('test');
    }

    public function testFromNativeWithUnknownKey(): void
    {
        $mock = MockValue::fromNative(['what' => 'no']);
        $this->assertNull($mock->getValue());
    }

    public function testFromNativeWithInvalidType(): void
    {
        $this->expectException(TypeError::class);
        MockValue::fromNative(['value' => 123]);
    }

    public function testFromNative(): void
    {
        $mock = MockValue::fromNative([]);
        $this->assertNull($mock->getValue());

        $mock = MockValue::fromNative(['value' => null]);
        $this->assertNull($mock->getValue());

        $mock = MockValue::fromNative(['value' => 'yo']);
        $this->assertEquals('yo', $mock->getValue());
    }
}
