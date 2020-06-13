<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use Assert\Assert as BaseAssert;

class Assert extends BaseAssert
{
    protected static $lazyAssertionExceptionClass = LazyAssertionException::class;

    protected static $assertionClass = Assertion::class;

    public static function lazy(): LazyAssertion
    {
        $lazyAssertion = new LazyAssertion;

        return $lazyAssertion
            ->setAssertClass(static::class)
            ->setExceptionClass(static::$lazyAssertionExceptionClass);
    }
}
