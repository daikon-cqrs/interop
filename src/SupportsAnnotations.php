<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use ReflectionClass;

trait SupportsAnnotations
{
    protected static function inferValidTypes(): array
    {
        return array_map(
            fn(array $annotation): string => explode('::', $annotation['value'])[0],
            static::getClassAnnotations('type')
        );
    }

    /** @return callable[] */
    protected static function inferTypeFactories(): array
    {
        $typeFactories = [];
        foreach (static::getClassAnnotations('type') as $annotation) {
            $callable = explode('::', $annotation['value']);
            if (interface_exists($callable[0], false)) {
                continue; //skip interfaces
            }
            if (!isset($callable[1]) && is_a($callable[0], FromNativeInterface::class, true)) {
                $callable[1] = 'fromNative';
            }
            Assertion::isCallable(
                $callable,
                sprintf("Type factory '%s' is not callable in '%s'.", $annotation['value'], static::class)
            );
            $typeFactories[$callable[0]] = $callable;
        }

        return $typeFactories;
    }

    /** @return callable[] */
    protected static function inferValueFactories(string ...$filter): array
    {
        $valueFactories = [];
        foreach (static::getClassAnnotations(...($filter ?: ['id', 'rev', 'map'])) as $annotation) {
            list($property, $factory) = array_map('trim', explode(',', $annotation['value']));
            $callable = explode('::', $factory);
            if (!isset($callable[1]) && is_a($callable[0], FromNativeInterface::class, true)) {
                $callable[1] = 'fromNative';
            }
            Assertion::isCallable(
                $callable,
                sprintf("Value factory '%s' is not callable in '%s'.", $factory, static::class)
            );
            $valueFactories[$property] = $callable;
        }

        return $valueFactories;
    }

    private static function getClassAnnotations(string ...$keys): array
    {
        $annotations = $matches = [];
        foreach (static::getInheritance() as $class) {
            if (!($docComment = $class->getDocComment())
                || !preg_match_all('/^[\s\*]+@(?<key>\w+)\((?<value>.+)\)$/m', $docComment, $matches, PREG_SET_ORDER)
            ) {
                continue;
            }
            foreach ($matches as $match) {
                if (empty($keys) || in_array($match['key'], $keys)) {
                    $annotations[] = ['key' => trim($match['key']), 'value' => trim($match['value'])];
                }
            }
        }

        return $annotations;
    }

    /** @return ReflectionClass[] */
    private static function getInheritance(): array
    {
        $currentReflection = new ReflectionClass(static::class);
        $reflectionClasses = array_merge([$currentReflection], static::flatMapTraits($currentReflection));

        while ($currentReflection = $currentReflection->getParentClass()) {
            $reflectionClasses = array_merge(
                $reflectionClasses,
                [$currentReflection],
                static::flatMapTraits($currentReflection)
            );
        }

        return $reflectionClasses;
    }

    /** @return ReflectionClass[] */
    private static function flatMapTraits(ReflectionClass $reflectionClass): array
    {
        $reflectionClasses = [];
        foreach ($reflectionClass->getTraits() as $trait) {
            $reflectionClasses = array_merge($reflectionClasses, [$trait], static::flatMapTraits($trait));
        }

        return $reflectionClasses;
    }
}
