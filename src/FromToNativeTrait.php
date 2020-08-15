<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use ReflectionClass;

trait FromToNativeTrait
{
    use SupportsAnnotations;

    /**
     * @psalm-suppress MissingParamType
     * @return static
     */
    public static function fromNative($state): self
    {
        Assertion::isArray($state, 'This trait only works with array state.');

        list($valueFactories, $product) = static::construct($state);
        foreach ($valueFactories as $propertyName => $factory) {
            if (array_key_exists($propertyName, $state)) {
                $product->$propertyName = $factory($state[$propertyName]);
            } elseif (is_a($factory[0], MakeEmptyInterface::class, true)) {
                $product->$propertyName = ([$factory[0], 'makeEmpty'])();
            }
        }

        return $product;
    }

    public function toNative(): array
    {
        $state = [];
        $reflectionClass = new ReflectionClass($this);
        foreach (static::getInheritance() as $currentClass) {
            foreach ($currentClass->getProperties() as $property) {
                $propertyName = $property->getName();
                if ($currentClass->isTrait()) {
                    $property = $reflectionClass->getProperty($propertyName);
                }
                $property->setAccessible(true);
                $value = $property->getValue($this);
                if (is_a($value, ToNativeInterface::class)) {
                    $state[$propertyName] = $value->toNative();
                } else {
                    $state[$propertyName] = $value;
                }
            }
        }

        return $state;
    }

    private static function construct(array $payload): array
    {
        $valueFactories = static::inferValueFactories();
        $constructor = (new ReflectionClass(static::class))->getConstructor();
        if (is_null($constructor) || $constructor->getNumberOfParameters() === 0) {
            /** @psalm-suppress UnsafeInstantiation */
            return [$valueFactories, new static];
        }

        $constructorArgs = [];
        foreach ($constructor->getParameters() as $constructorParam) {
            $paramName = $constructorParam->getName();
            if (isset($payload[$paramName])) {
                if (isset($valueFactories[$paramName])) {
                    $constructorArgs[] = $valueFactories[$paramName]($payload[$paramName]);
                    unset($valueFactories[$paramName]);
                } else {
                    $constructorArgs[] = $payload[$paramName];
                }
            } elseif ($constructorParam->allowsNull()) {
                $constructorArgs[] = null;
            } else {
                throw new InvalidArgumentException(
                    "Missing required value for key '$paramName' while constructing from native state."
                );
            }
        }

        /** @psalm-suppress UnsafeInstantiation */
        return [$valueFactories, new static(...$constructorArgs)];
    }
}
