<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use ReflectionClass;

trait InheritanceReader
{
    /** @return ReflectionClass[] */
    protected static function getInheritance(ReflectionClass $classReflection, bool $includeTraits = true): array
    {
        $current = $classReflection;
        $classes = $includeTraits
            ? array_merge([$classReflection], static::flatMapTraits($classReflection))
            : [$classReflection];

        while ($current = $current->getParentClass()) {
            $classes = $includeTraits
                ? array_merge($classes, [$current], static::flatMapTraits($current))
                : array_merge($classes, [$classReflection]);
        }

        return $classes;
    }

    /** @return ReflectionClass[] */
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
