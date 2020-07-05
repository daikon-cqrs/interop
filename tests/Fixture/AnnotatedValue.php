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
use Daikon\Interop\ToNativeInterface;

/**
 * @map(mockValue, Daikon\Tests\Interop\Fixture\MockValue::fromNative)
 * @map(otherMockValue, Daikon\Tests\Interop\Fixture\MockValue::customFactory)
 * @map(defaultFactoryMockValue, Daikon\Tests\Interop\Fixture\MockValue)
 */
final class AnnotatedValue implements FromNativeInterface, ToNativeInterface
{
    use FromToNativeTrait;

    private ?MockValue $mockValue;

    private MockValue $otherMockValue;

    private MockValue $defaultFactoryMockValue;

    public function getMockValue(): MockValue
    {
        return $this->mockValue ?? MockValue::makeEmpty();
    }

    public function getOtherMockValue(): MockValue
    {
        return $this->otherMockValue;
    }

    public function getDefaultFactoryMockValue(): MockValue
    {
        return $this->defaultFactoryMockValue;
    }
}
