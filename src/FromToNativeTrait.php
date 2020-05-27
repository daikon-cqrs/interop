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
    /** @psalm-suppress MissingParamType */
    public static function fromNative($state): object
    {
        if (!is_array($state)) {
            throw new RuntimeException('This trait only works with array state.');
        }

        $classReflection = new ReflectionClass(static::class);
        list($valueFactories, $product) = static::construct($classReflection, $state);
        foreach ($valueFactories as $propertyName => $factory) {
            if (array_key_exists($propertyName, $state)) {
                $product->$propertyName = call_user_func($factory, $state[$propertyName]);
            } elseif (is_callable($emptyFactory = [$factory[0], 'makeEmpty'])) {
                $product->$propertyName = call_user_func($emptyFactory);
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
                if (is_callable($nativeFactory = [$value, 'toNative'])) {
                    $state[$propertyName] = call_user_func($nativeFactory);
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
        if (!$classReflection->hasMethod('__construct')) {
            /** @psalm-suppress TooFewArguments */
            return [$valueFactories, new static];
        }

        $constructorArgs = [];
        $constructorParams = $classReflection->getMethod('__construct')->getParameters();
        foreach ($constructorParams as $constructorParam) {
            $paramName = $constructorParam->getName();
            if (isset($payload[$paramName])) {
                if (isset($valueFactories[$paramName])) {
                    $constructorArgs[] = call_user_func($valueFactories[$paramName], $payload[$paramName]);
                    unset($valueFactories[$paramName]);
                } else {
                    $constructorArgs[] = $payload[$paramName];
                }
            } elseif ($constructorParam->allowsNull()) {
                $constructorArgs[] = null;
            } else {
                throw new RuntimeException(
                    "Missing required value for key '$paramName' while constructing from native state."
                );
            }
        }

        /** @psalm-suppress TooManyArguments */
        return [$valueFactories, /** @scrutinizer ignore-call */ new static(...$constructorArgs)];
    }

    private static function inferValueFactories(ReflectionClass $classReflection): array
    {
        $valueFactories = [];
        foreach (static::getInheritanceTree($classReflection, true) as $currentClass) {
            if (!($docComment = $currentClass->getDocComment())) {
                continue;
            }
            preg_match_all('#@(?:id|rev|map)\(((.+),(.+))\)#', $docComment, $matches);
            //@todo don't allow duplicate id/rev
            foreach ($matches[2] as $index => $propertyName) {
                $callable = array_map('trim', explode('::', $matches[3][$index]));
                if (!is_callable($callable)) {
                    throw new RuntimeException("Value factory '$callable[0]' is not callable in ".static::class);
                }
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
