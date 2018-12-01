<?php
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Daikon\Interop;

use Assert\Assertion;

trait FromToNativeTrait
{
    public static function fromNative($payload)
    {
        if (!is_array($payload)) {
            throw new \RuntimeException("This trait only works for complex state (array based).");
        }
        $classReflection = new \ReflectionClass(static::class);
        list($valueFactories, $sendable) = static::construct($classReflection, $payload);
        foreach ($valueFactories as $propName => $factory) {
            if (array_key_exists($propName, $payload)) {
                $sendable->$propName = call_user_func($factory, $payload[$propName]);
            }
        }
        return $sendable;
    }

    public function toNative(): array
    {
        $data = [];
        $classReflection = new \ReflectionClass($this);
        foreach (self::getInheritanceTree($classReflection, true) as $curClass) {
            foreach ($curClass->getProperties() as $prop) {
                $propName = $prop->getName();
                if ($curClass->isTrait()) {
                    $prop = $classReflection->getProperty($propName);
                }
                $prop->setAccessible(true);
                $value = $prop->getValue($this);
                $toNative = [ $value, "toNative" ];
                $toArray = [ $value, "toArray" ];
                if (is_callable($toNative)) {
                    $data[$propName] = call_user_func($toNative);
                } elseif (is_callable($toArray)) {
                    $data[$propName] = call_user_func($toArray);
                } else {
                    $data[$propName] = $value;
                }
            }
        }
        return $data;
    }

    private static function construct(\ReflectionClass $classReflection, array $payload): array
    {
        $valueFactories = self::inferValueFactories($classReflection);
        if (!$classReflection->hasMethod("__construct")) {
            /** @psalm-suppress TooFewArguments */
            return [ $valueFactories, new static ];
        }

        $ctorArgs = [];
        foreach ($classReflection->getMethod("__construct")->getParameters() as $argReflection) {
            $argName = $argReflection->getName();
            if (isset($payload[$argName])) {
                if (isset($valueFactories[$argName])) {
                    $ctorArgs[] = call_user_func($valueFactories[$argName], $payload[$argName]);
                    unset($valueFactories[$argName]);
                } else {
                    // missing factory annoation, throw exception or ignore?
                }
            } elseif ($argReflection->allowsNull()) {
                $ctorArgs[] = null;
            } else {
                throw new \Exception("Missing required value for array-key: $argName while constructing from array");
            }
        }
        return [$valueFactories, new static(...$ctorArgs)];
    }

    private static function inferValueFactories(\ReflectionClass $classReflection): array
    {
        $valueFactories = [];
        foreach (self::getInheritanceTree($classReflection, true) as $curClass) {
            if (!($docComment = $curClass->getDocComment())) {
                continue;
            }
            preg_match_all("~@map\(((.*)\,(.*))\)~", $curClass->getDocComment(), $matches);
            foreach ($matches[2] as $idx => $propName) {
                $callable = array_map("trim", explode("::", $matches[3][$idx]));
                Assertion::isCallable($callable);
                $valueFactories[$propName] = $callable;
            }
        }
        return $valueFactories;
    }

    private static function getInheritanceTree(\ReflectionClass $classReflection, bool $includeTraits = false): array
    {
        $parent = $classReflection;
        $classes = $includeTraits
            ? array_merge([ $classReflection ], self::flatMapTraits($classReflection))
            : [ $classReflection ];
        while ($parent = $parent->getParentClass()) {
            $classes = $includeTraits
                ? array_merge($classes, [ $parent ], self::flatMapTraits($parent))
                : array_merge($classes, [ $classReflection ]);
        }
        return $classes;
    }

    private static function flatMapTraits(\ReflectionClass $classReflection): array
    {
        $traits = [];
        $curTrait = $classReflection;
        foreach ($curTrait->getTraits() as $trait) {
            $traits = array_merge($traits, [ $trait ], self::flatMapTraits($trait));
        }
        return $traits;
    }
}
