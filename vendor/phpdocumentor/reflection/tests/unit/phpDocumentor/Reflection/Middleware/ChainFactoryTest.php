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

namespace phpDocumentor\Reflection\Middleware;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ChainFactory::class)]
final class ChainFactoryTest extends TestCase
{
    public function testItCreatesAChainOfCallablesThatWillInvokeAllMiddlewares(): void
    {
        $exampleCommand = new class implements Command {
        };

        $middleware1 = $this->givenAMiddleware('c');
        $middleware2 = $this->givenAMiddleware('b');

        $chain = ChainFactory::createExecutionChain(
            [$middleware1, $middleware2],
            static function (): stdClass {
                $result = new stdClass();
                $result->counter = 'a';

                return $result;
            },
        );

        $this->assertInstanceOf(stdClass::class, $chain(new $exampleCommand()));
        $this->assertSame('abc', $chain(new $exampleCommand())->counter);
    }

    public function testItThrowsAnExceptionIfAnythingOtherThanAMiddlewareIsPassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Middleware must be an instance of phpDocumentor\Reflection\Middleware\Middleware but string was given',
        );
        $middleware = '1';

        ChainFactory::createExecutionChain(
            [$middleware],
            static fn (): stdClass => new stdClass(),
        );
    }

    private function givenAMiddleware(string $exampleValue): Middleware
    {
        return new class ($exampleValue) implements Middleware {
            public function __construct(private readonly string $exampleAddedValue)
            {
            }

            public function execute(Command $command, callable $next): object
            {
                $result = $next($command);
                $result->counter .= $this->exampleAddedValue;

                return $result;
            }
        };
    }
}
