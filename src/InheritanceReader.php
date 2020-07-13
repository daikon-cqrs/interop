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
    protected static function getInheritance(ReflectionClass $classReflection, bool $includeTraits = false): array
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
