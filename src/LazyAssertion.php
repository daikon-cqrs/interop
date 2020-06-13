<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/interop project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Interop;

use Assert\AssertionChain;
use Assert\LazyAssertion as BaseLazyAssertion;
use LogicException;

class LazyAssertion extends BaseLazyAssertion
{
    /** @var bool */
    private $currentChainFailed = false;

    /** @var bool */
    private $alwaysTryAll = false;

    /** @var bool */
    private $thisChainTryAll = false;

    /** @var null|AssertionChain */
    private $currentChain;

    /** @var array */
    private $errors = [];

    /** @var string The class to use as AssertionChain factory */
    private $assertClass = Assert::class;

    /** @var string|LazyAssertionException The class to use for exceptions */
    private $exceptionClass = LazyAssertionException::class;

    /**
     * @param mixed $value
     * @param string|callable|null $defaultMessage
     * @return static
     */
    public function that($value, string $propertyPath = null, $defaultMessage = null)
    {
        $this->currentChainFailed = false;
        $this->thisChainTryAll = false;
        $assertClass = $this->assertClass;
        $this->currentChain = $assertClass::that($value, $defaultMessage, $propertyPath);

        return $this;
    }

    /** @return static */
    public function tryAll()
    {
        if (!$this->currentChain) {
            $this->alwaysTryAll = true;
        }

        $this->thisChainTryAll = true;

        return $this;
    }

    /**
     * @param string $method
     * @param array $args
     * @return static
     */
    public function __call($method, $args)
    {
        if (false === $this->alwaysTryAll
            && false === $this->thisChainTryAll
            && true === $this->currentChainFailed
        ) {
            return $this;
        }

        try {
            call_user_func_array([$this->currentChain, $method], $args);
        } catch (AssertionFailedException $e) {
            $this->errors[] = $e;
            $this->currentChainFailed = true;
        }

        return $this;
    }

    /** @throws LazyAssertionException */
    public function verifyNow(): bool
    {
        if ($this->errors) {
            throw call_user_func([$this->exceptionClass, 'fromErrors'], $this->errors);
        }

        return true;
    }

    /** @return static */
    public function setAssertClass(string $className)
    {
        if (Assert::class !== $className && !is_subclass_of($className, Assert::class)) {
            throw new LogicException($className.' is not (a subclass of) '.Assert::class);
        }

        $this->assertClass = $className;

        return $this;
    }

    /** @return static */
    public function setExceptionClass(string $className)
    {
        if (LazyAssertionException::class !== $className
            && !is_subclass_of($className, LazyAssertionException::class)
        ) {
            throw new LogicException($className.' is not (a subclass of) '.LazyAssertionException::class);
        }

        $this->exceptionClass = $className;

        return $this;
    }
}
