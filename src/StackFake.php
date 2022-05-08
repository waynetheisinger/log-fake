<?php

declare(strict_types=1);

namespace TiMacDonald\Log;

use Closure;
use RuntimeException;

/**
 * @no-named-arguments
 */
class StackFake extends ChannelFake
{
    /**
     * @link https://github.com/timacdonald/log-fake#assertcurrentcontext Documentation
     * @param (Closure(array<array-key, mixed>): bool)|array<array-key, mixed> $context
     */
    public function assertCurrentContext(Closure|array $context): StackFake
    {
        throw new RuntimeException('Cannot call [Log::stack(...)->assertCurrentContext(...)] as stack contexts are reset each time they are resolved from the LogManager.');
    }
}
