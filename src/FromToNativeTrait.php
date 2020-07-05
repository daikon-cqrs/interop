<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use ReflectionClass;
use ReflectionParameter;

trait FromToNativeTrait
{
    /** @psalm-suppress MissingParamType */
    public static function fromNative($state): object
    {
        Assertion::isArray($state, 'This trait only works with array state.');

        $classReflection = new ReflectionClass(static::class);
        list($valueFactories, $product) = static::construct($classReflection, $state);
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
        $classReflection = new ReflectionClass($this);
        foreach (static::getInheritanceTree($classReflection, true) as $currentClass) {
            foreach ($currentClass->getProperties() as $property) {
                $propertyName = $property->getName();
                if ($currentClass->isTrait()) {
                    $property = $classReflection->getProperty($propertyName);
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

    private static function construct(ReflectionClass $classReflection, array $payload): array
    {
        $valueFactories = static::inferValueFactories($classReflection);
        $constructor = $classReflection->getConstructor();
        if (is_null($constructor) || $constructor->getNumberOfParameters() === 0) {
            /** @psalm-suppress TooFewArguments */
            return [$valueFactories, new static];
        }

        $constructorArgs = [];
        /** @var ReflectionParameter $constructorParam */
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

        /** @psalm-suppress TooManyArguments */
        return [$valueFactories, new static(...$constructorArgs)];
    }

    private static function inferValueFactories(ReflectionClass $classReflection): array
    {
        $valueFactories = [];
        /** @var ReflectionClass $currentClass */
        foreach (static::getInheritanceTree($classReflection, true) as $currentClass) {
            if (!($docComment = $currentClass->getDocComment())) {
                continue;
            }
            preg_match_all('#@(?:id|rev|map)\(((.+),(.+))\)#', $docComment, $matches);
            //@todo don't allow duplicate id/rev
            foreach ($matches[2] as $index => $propertyName) {
                $callable = array_map('trim', explode('::', $matches[3][$index]));
                if (count($callable) === 1 && is_a($callable[0], FromNativeInterface::class, true)) {
                    $callable[1] = 'fromNative';
                }
                Assertion::isCallable(
                    $callable,
                    sprintf("Value factory '%s' is not callable in '%s'.", implode('::', $callable), static::class)
                );
                $valueFactories[$propertyName] = $callable;
            }
        }

        return $valueFactories;
    }

    private static function getInheritanceTree(ReflectionClass $classReflection, bool $includeTraits = false): array
    {
        $parent = $classReflection;
        $classes = $includeTraits
            ? array_merge([$classReflection], static::flatMapTraits($classReflection))
            : [$classReflection];

        while ($parent = $parent->getParentClass()) {
            $classes = $includeTraits
                ? array_merge($classes, [$parent], static::flatMapTraits($parent))
                : array_merge($classes, [$classReflection]);
        }

        return $classes;
    }

    private static function flatMapTraits(ReflectionClass $classReflection): array
    {
        $traits = [];
        $currentTrait = $classReflection;
        foreach ($currentTrait->getTraits() as $trait) {
            $traits = array_merge($traits, [$trait], static::flatMapTraits($trait));
        }

        return $traits;
    }
}
