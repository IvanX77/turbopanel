<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Reflection\Php\Factory;

use Iterator;
use Override;
use phpDocumentor\Reflection\Fqsen;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Const_;

/** @implements Iterator<int, GlobalConstantIterator> */
final class GlobalConstantIterator implements Iterator
{
    /** @var int index of the current constant to use */
    private int $index = 0;

    /**
     * Initializes the class with source data.
     */
    public function __construct(private readonly Const_ $constant)
    {
    }

    /**
     * Gets line the node started in.
     *
     * @return int Line
     */
    public function getLine(): int
    {
        return $this->constant->getLine();
    }

    /**
     * Gets line the node ended in.
     *
     * @return int Line
     */
    public function getEndLine(): int
    {
        return $this->constant->getEndLine();
    }

    /**
     * Returns the name of the current constant.
     */
    public function getName(): string
    {
        return (string) $this->constant->consts[$this->index]->name;
    }

    /**
     * Returns the fqsen of the current constant.
     */
    public function getFqsen(): Fqsen
    {
        return $this->constant->consts[$this->index]->getAttribute('fqsen');
    }

    /**
     * Gets the doc comment of the node.
     *
     * The doc comment has to be the last comment associated with the node.
     */
    public function getDocComment(): Doc|null
    {
        $docComment = $this->constant->consts[$this->index]->getDocComment();
        if ($docComment === null) {
            $docComment = $this->constant->getDocComment();
        }

        return $docComment;
    }

    public function getValue(): Expr
    {
        return $this->constant->consts[$this->index]->value;
    }

    /** @link http://php.net/manual/en/iterator.current.php */
    #[Override]
    public function current(): self
    {
        return $this;
    }

    /** @link http://php.net/manual/en/iterator.next.php */
    #[Override]
    public function next(): void
    {
        ++$this->index;
    }

    /** @link http://php.net/manual/en/iterator.key.php */
    #[Override]
    public function key(): int|null
    {
        return $this->index;
    }

    /** @link http://php.net/manual/en/iterator.valid.php */
    #[Override]
    public function valid(): bool
    {
        return isset($this->constant->consts[$this->index]);
    }

    /** @link http://php.net/manual/en/iterator.rewind.php */
    #[Override]
    public function rewind(): void
    {
        $this->index = 0;
    }
}
