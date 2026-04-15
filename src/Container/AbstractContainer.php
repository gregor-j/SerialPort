<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

/**
 * PSR-11 container for dependencies.
 * Extend this abstract class with a constructor.
 */
abstract class AbstractContainer implements ContainerInterface
{
    /**
     * @var array<class-string, object>
     */
    protected array $dependencies;

    /**
     * @inheritDoc
     */
    final public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Missing required dependency "%s" in container.', $id));
        }
        return $this->dependencies[$id];
    }

    /**
     * @inheritDoc
     */
    final public function has(string $id): bool
    {
        if ($id === '') {
            return false;
        }
        return isset($this->dependencies[$id]);
    }
}
