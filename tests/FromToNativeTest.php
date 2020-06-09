<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Interop;

use Daikon\Interop\AssertionFailedException;
use Daikon\Tests\Interop\Fixture\AnnotatedValue;
use Daikon\Tests\Interop\Fixture\MockValue;
use PHPUnit\Framework\TestCase;
use TypeError;

class FromToNativeTest extends TestCase
{
    public function testMakeEmpty(): void
    {
        $mock = MockValue::makeEmpty();
        $this->assertNull($mock->getValue());

        $mock = AnnotatedValue::makeEmpty();
        $this->assertInstanceOf(MockValue::class, $mock->getMockValue());
        $this->assertNull($mock->getMockValue()->getValue());
    }

    public function testFromNativeWithNull(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        MockValue::fromNative(null);
    }

    public function testInferredFromNativeWithNull(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        AnnotatedValue::fromNative(null);
    }

    public function testFromNativeWithScalar(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        MockValue::fromNative('test');
    }

    public function testInferredFromNativeWithScalar(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        AnnotatedValue::fromNative('test');
    }

    public function testFromNativeWithUnknownKey(): void
    {
        $mock = MockValue::fromNative(['what' => 'no']);
        $this->assertNull($mock->getValue());
    }

    public function testInferredFromNativeWithUnknownKey(): void
    {
        $mock = AnnotatedValue::fromNative(['what' => 'no']);
        $this->assertInstanceOf(MockValue::class, $mock->getMockValue());
        $this->assertNull($mock->getMockValue()->getValue());
    }

    public function testFromNativeWithInvalidType(): void
    {
        $this->expectException(TypeError::class);
        MockValue::fromNative(['value' => 123]);
    }

    public function testInferredFromNativeWithInvalidType(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        AnnotatedValue::fromNative(['mockValue' => 123]);
    }

    public function testInferredFromNativeWithNullValueState(): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('This trait only works with array state.');
        AnnotatedValue::fromNative(['mockValue' => null]);
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

    public function testInferredFromNative(): void
    {
        $mock = AnnotatedValue::fromNative(['mockValue' => []]);
        $this->assertInstanceOf(MockValue::class, $mock->getMockValue());
        $this->assertNull($mock->getMockValue()->getValue());

        $mock = AnnotatedValue::fromNative(['mockValue' => ['valuex' => '']]);
        $this->assertNull($mock->getMockValue()->getValue());

        $mock = AnnotatedValue::fromNative(['mockValue' => ['value' => '']]);
        $this->assertEmpty($mock->getMockValue()->getValue());

        $mock = AnnotatedValue::fromNative(['mockValue' => ['value' => '123']]);
        $this->assertEquals('123', $mock->getMockValue()->getValue());

        $mock = AnnotatedValue::fromNative(['otherMockValue' => ['custom' => 'ABC']]);
        $this->assertEquals('ABC', $mock->getOtherMockValue()->getValue());

        $this->expectException(TypeError::class);
        AnnotatedValue::fromNative(['otherMockValue' => 'ABC']);
    }
}
