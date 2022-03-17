<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Config\Repository as Config;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;
use Symfony\Component\VarDumper\VarDumper;
use TiMacDonald\Log\ChannelFake;
use TiMacDonald\Log\LogFake;
use function assert;
use function config;
use function is_array;

/**
 * @small
 */
class LogFakeTest extends TestCase
{
    public const MESSAGE = 'Expected logged message';

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::setInstance(new Container());

        $container->singleton('config', static function (): Repository {
            return new Config(['logging' => ['default' => 'stack']]);
        });

        $container->singleton('log', static function (Container $app): LoggerInterface {
            return new LogManager($app);
        });

        Facade::setFacadeApplication($container);
    }

    public function testAssertLogged(): void
    {
        $log = new LogFake();

        try {
            $log->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [stack] channel.'));
        }
        $log->info('xxxx');
        $log->assertLogged('info');

        try {
            $log->channel('channel')->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [channel] channel.'));
        }
        $log->channel('channel')->info('xxxx');
        $log->channel('channel')->assertLogged('info');

        try {
            $log->stack(['channel'], 'name')->assertLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [Stack:name.channel] channel.'));
        }
        $log->stack(['channel'], 'name')->info('xxxx');
        $log->stack(['channel'], 'name')->assertLogged('info');
    }

    public function testAssertLoggedWithCallback(): void
    {
        $log = new LogFake();

        try {
            $log->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [stack] channel.'));
        }
        $log->info('xxxx');
        $log->assertLogged('info', static function () {
            return true;
        });

        try {
            $log->channel('channel')->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [channel] channel.'));
        }
        $log->channel('channel')->info('xxxx');
        $log->channel('channel')->assertLogged('info', static function () {
            return true;
        });

        try {
            $log->stack(['channel'], 'name')->assertLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [Stack:name.channel] channel.'));
        }
        $log->stack(['channel'], 'name')->info('xxxx');
        $log->stack(['channel'], 'name')->assertLogged('info', static function () {
            return true;
        });
    }

    public function testAssertLoggedTimesWithCallback(): void
    {
        $log = new LogFake();

        $log->info('xxxx');
        try {
            $log->assertLoggedTimes('info', 2, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [stack] channel.'));
        }
        $log->assertLoggedTimes('info', 1, static function () {
            return true;
        });

        $log->channel('channel')->info('xxxx');
        try {
            $log->channel('channel')->assertLoggedTimes('info', 2, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [channel] channel.'));
        }
        $log->channel('channel')->assertLoggedTimes('info', 1, static function () {
            return true;
        });

        $log->stack(['channel'], 'name')->info('xxxx');
        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 2, static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [Stack:name.channel] channel.'));
        }
        $log->stack(['channel'], 'name')->assertLoggedTimes('info', 1, static function () {
            return true;
        });
    }

    public function testAssertLoggedMessage(): void
    {
        $log = new LogFake();

        try {
            $log->assertLoggedMessage('info', 'expected message');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [stack] channel.'));
        }
        $log->info('expected message');
        $log->assertLoggedMessage('info', 'expected message');

        try {
            $log->channel('channel')->assertLoggedMessage('info', 'expected message');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [channel] channel.'));
        }
        $log->channel('channel')->info('expected message');
        $log->channel('channel')->assertLoggedMessage('info', 'expected message');

        try {
            $log->stack(['channel'], 'name')->assertLoggedMessage('info', 'expected message');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An expected log with level [info] was not logged in the [Stack:name.channel] channel.'));
        }
        $log->stack(['channel'], 'name')->info('expected message');
        $log->stack(['channel'], 'name')->assertLoggedMessage('info', 'expected message');
    }

    public function testAssertLoggedTimes(): void
    {
        $log = new LogFake();

        $log->info('xxxx');
        try {
            $log->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [stack] channel.'));
        }
        $log->assertLoggedTimes('info', 1);

        $log->channel('channel')->info('xxxx');
        try {
            $log->channel('channel')->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [channel] channel.'));
        }
        $log->channel('channel')->assertLoggedTimes('info', 1);

        $log->stack(['channel'], 'name')->info('xxxx');
        try {
            $log->stack(['channel'], 'name')->assertLoggedTimes('info', 2);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('A log with level [info] was logged [1] times instead of an expected [2] times in the [Stack:name.channel] channel.'));
        }
        $log->stack(['channel'], 'name')->assertLoggedTimes('info', 1);
    }

    public function testAssertNotLogged(): void
    {
        $log = new LogFake();

        $log->assertNotLogged('xxxx');
        $log->info('xxxx');
        try {
            $log->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [stack] channel.'));
        }

        $log->channel('channel')->assertNotLogged('info');
        $log->channel('channel')->info('xxxx');
        try {
            $log->channel('channel')->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [channel] channel.'));
        }

        $log->stack(['channel'], 'name')->assertNotLogged('info');
        $log->stack(['channel'], 'name')->info('xxxx');
        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info');
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [Stack:name.channel] channel.'));
        }
    }

    public function testAssertNotLoggedWithCallback(): void
    {
        $log = new LogFake();

        $log->assertNotLogged('info', static function () {
            return true;
        });
        $log->info('xxxx');
        try {
            $log->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [stack] channel.'));
        }

        $log->channel('channel')->assertNotLogged('info', static function () {
            return true;
        });
        $log->channel('channel')->info('expected message');
        try {
            $log->channel('channel')->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [channel] channel.'));
        }

        $log->stack(['channel'], 'name')->assertNotLogged('info', static function () {
            return true;
        });
        $log->stack(['channel'], 'name')->info('expected message');
        try {
            $log->stack(['channel'], 'name')->assertNotLogged('info', static function () {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('An unexpected log with level [info] was logged [1] times in the [Stack:name.channel] channel.'));
        }
    }

    public function testAssertNothingLogged(): void
    {
        $log = new LogFake();

        $log->assertNothingLogged();
        $log->info('xxxx');
        try {
            $log->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Found [1] logs in the [stack] channel. Expected to find [0].'));
        }

        $log->channel('channel')->assertNothingLogged();
        $log->channel('channel')->info('expected message');
        try {
            $log->channel('channel')->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Found [1] logs in the [channel] channel. Expected to find [0].'));
        }

        $log->stack(['channel'], 'name')->assertNothingLogged();
        $log->stack(['channel'], 'name')->info('xxxx');
        try {
            $log->stack(['channel'], 'name')->assertNothingLogged();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Found [1] logs in the [Stack:name.channel] channel. Expected to find [0].'));
        }
    }

    public function testLogged(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info')->isEmpty());
        $log->info('xxxx');
        $this->assertFalse($log->logged('info')->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info')->isEmpty());
        $log->channel('channel')->info('xxxx');
        $this->assertFalse($log->channel('channel')->logged('info')->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info')->isEmpty());
        $log->stack(['channel'], 'name')->info('xxxx');
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info')->isEmpty());
    }

    public function testLoggedWithCallback(): void
    {
        $log = new LogFake();

        $this->assertTrue($log->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->info('expected message');
        $this->assertFalse($log->logged('info', static function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->channel('channel')->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->channel('channel')->info('expected message');
        $this->assertFalse($log->channel('channel')->logged('info', static function () {
            return true;
        })->isEmpty());

        $this->assertTrue($log->stack(['channel'], 'name')->logged('info', static function () {
            return true;
        })->isEmpty());
        $log->stack(['channel'], 'name')->info('expected message');
        $this->assertFalse($log->stack(['channel'], 'name')->logged('info', static function () {
            return true;
        })->isEmpty());
    }

    public function testLoggingLevelMethods(): void
    {
        $log = new LogFake();

        $log->emergency('emergency log');
        $log->alert('alert log');
        $log->critical('critical log');
        $log->error('error log');
        $log->warning('warning log');
        $log->info('info log');
        $log->notice('notice log');
        $log->debug('debug log');
        $log->log('custom', 'custom log');
        $log->write('custom_2', 'custom log 2');

        $log->assertLogged('emergency', static function (string $message) {
            return $message === 'emergency log';
        });
        $log->assertLogged('alert', static function (string $message) {
            return $message === 'alert log';
        });
        $log->assertLogged('critical', static function (string $message) {
            return $message === 'critical log';
        });
        $log->assertLogged('error', static function (string $message) {
            return $message === 'error log';
        });
        $log->assertLogged('warning', static function (string $message) {
            return $message === 'warning log';
        });
        $log->assertLogged('info', static function (string $message) {
            return $message === 'info log';
        });
        $log->assertLogged('notice', static function (string $message) {
            return $message === 'notice log';
        });
        $log->assertLogged('debug', static function (string $message) {
            return $message === 'debug log';
        });
        $log->assertLogged('custom', static function (string $message) {
            return $message === 'custom log';
        });
        $log->assertLogged('custom_2', static function (string $message) {
            return $message === 'custom log 2';
        });
    }

    public function assertChannelAndDriverMethodsCanBeUsedInterchangably(): void
    {
        $log = new LogFake();

        $log->driver('channel')->info('expected message');
        $log->channel('channel')->assertLogged('info', static function () {
            return true;
        });
    }

    public function testCurrentStackIsTakenIntoAccount(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->assertNotLogged('info');
        $log->stack(['bugsnag', 'sentry'], 'dev_team')->assertLogged('info');
    }

    public function testCanHaveStackChannelsInAnyOrder(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');

        $log->assertNotLogged('info');
        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertLogged('info');
    }

    public function testDifferentiatesBetweenStacksWithANameAndThoseWithout(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'], 'dev_team')->info('expected message');
        $log->stack(['bugsnag', 'sentry'])->alert('expected message');

        $log->stack(['sentry', 'bugsnag'], 'dev_team')->assertNotLogged('alert');
        $log->stack(['sentry', 'bugsnag'])->assertNotLogged('info');
    }

    public function testDifferentiatesBetweenStacksAndChannelsWithTheSameName(): void
    {
        $log = new LogFake();

        $log->stack(['bugsnag', 'sentry'])->info('expected message');
        $log->channel('bugsnag.sentry')->alert('expected message');

        $log->stack(['bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('bugsnag.sentry')->assertNotLogged('info');

        $log->stack(['bugsnag', 'sentry'], 'name')->info('expected message');
        $log->channel('name.bugsnag.sentry')->alert('expected message');

        $log->stack(['name', 'bugsnag', 'sentry'])->assertNotLogged('alert');
        $log->channel('name.bugsnag.sentry')->assertNotLogged('info');
    }

    public function testAssertLoggedInStackDotNotatesSortedChannels(): void
    {
        $logFake = new LogFake();

        $logFake->stack(['c', 'b', 'a'], 'name')->info('expected message');

        $this->assertSame('Stack:name.a.b.c', $logFake->allLogs()->first()['channel']);
    }

    public function testClosuresProvideMessageAndContext(): void
    {
        $log = new LogFake();
        $log->info('expected message', ['key' => 'expected']);

        $items = $log->logged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $this->assertTrue($items->isNotEmpty());
        $log->assertLogged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return true;
        });
        $log->assertNotLogged('info', function (string $message, array $context) {
            $this->assertSame('expected message', $message);
            $this->assertSame(['key' => 'expected'], $context);

            return false;
        });
    }

    public function testSetDefaultDriver(): void
    {
        $log = new LogFake();
        $log->setDefaultDriver('expected-driver');

        $this->assertSame('expected-driver', config()->get('logging.default'));
    }

    public function testLoggedClosureWithNonBooleanReturn(): void
    {
        $log = new LogFake();
        $log->info('xxxx');

        $log->logged('info', function () {
            $this->assertTrue(true);

            return 1;
        });
    }

    public function testAssertionCallbacksRecieveTimesForgottenAsAParameter(): void
    {
        $log = new LogFake();
        $forgotten = [];

        $log->info('foo');
        $log->assertLogged('info', function (string $message, array $context, int $timesForgotten) use (&$forgotten) {
            if ($message === 'foo') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $log->info('bar');
        $log->assertLogged('info', function ($message, $context, $timesForgotten) use (&$forgotten) {
            if ($message === 'bar') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $log->info('baz');
        $log->assertLogged('info', function ($message, $context, $timesForgotten) use (&$forgotten) {
            if ($message === 'baz') {
                $forgotten[] = $timesForgotten;
            }

            return true;
        });
        $log->forgetChannel('stack');

        $this->assertSame([0, 1, 2], $forgotten);
    }

    public function testDummyMethods(): void
    {
        $logFake = new LogFake();

        $logFake->listen(function () {
            //
        });
        $logFake->extend('misc', function () {
            //
        });
        $logFake->setEventDispatcher(new class () implements Dispatcher {
            public function listen($events, $listener = null)
            {
                //
            }

            public function hasListeners($eventName)
            {
                return false;
            }

            public function subscribe($subscriber)
            {
                //
            }

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false)
            {
                return null;
            }

            public function push($event, $payload = [])
            {
                //
            }

            public function flush($event)
            {
                //
            }

            public function forget($event)
            {
                //
            }

            public function forgetPushed()
            {
                //
            }
        });
        $logFake->getEventDispatcher();
        $this->assertSame($logFake->getLogger(), $logFake->channel());
    }

    public function testItCanDumpDefaultChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump();

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpALevelForTheDefaultChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('expected log');
        $log->debug('missing log');
        $log->channel('channel')->info('missing channel log');
        $log = $log->dump('info');

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(1, $logs);
        $this->assertSame([
            'level' => 'info',
            'message' => 'expected log',
            'context' => [],
            'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
            'channel' => 'stack',
        ], $logs[0]);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log 1');
        $log->channel('known')->debug('expected log 2');
        $log = $log->channel('known')->dump();

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);
        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpALevelForAChannel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });
        $log->info('missing log');
        $log->channel('unknown')->info('missing log');
        $log->channel('known')->info('expected log');
        $log->channel('known')->debug('missing log');
        $log = $log->channel('known')->dump('info');

        $this->assertInstanceOf(ChannelFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];
        $this->assertTrue(is_array($logs));
        $this->assertCount(1, $logs);
        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'known',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAllLogsForAllChannels(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('expected log 2');
        $log->channel('channel')->info('expected log 3');
        $log->channel('channel')->debug('expected log 4');
        $log = $log->dumpAll();

        $this->assertInstanceOf(LogFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(4, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 3',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
            [
                'level' => 'debug',
                'message' => 'expected log 4',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCanDumpAllLogsForAllChannelsButFilterByLevel(): void
    {
        $log = new LogFake();
        $dumps = [];
        VarDumper::setHandler(static function (array $logs) use (&$dumps) {
            assert(is_array($dumps));

            $dumps[] = $logs;
        });

        $log->info('expected log 1');
        $log->debug('missing log');
        $log->channel('channel')->info('expected log 2');
        $log->channel('channel')->debug('missing log');
        $log = $log->dumpAll('info');

        $this->assertInstanceOf(LogFake::class, $log);
        $this->assertTrue(is_array($dumps));
        $this->assertCount(1, $dumps);
        $logs = $dumps[0];

        $this->assertTrue(is_array($logs));
        $this->assertCount(2, $logs);

        $this->assertSame([
            [
                'level' => 'info',
                'message' => 'expected log 1',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'stack',
            ],
            [
                'level' => 'info',
                'message' => 'expected log 2',
                'context' => [],
                'times_channel_has_been_forgotten_at_time_of_writing_log' => 0,
                'channel' => 'channel',
            ],
        ], $logs);

        VarDumper::setHandler(null);
    }

    public function testItCannotCallDumpAllFromChannel(): void
    {
        $log = new LogFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LogFake::dumpAll() should not be called from a channel.');

        $log->channel('channel')->dumpAll();
    }

    public function testItCannotCallDdAllFromAChannel(): void
    {
        $log = new LogFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`ddAll()` should not be called from a channel.');

        $log->channel('channel')->ddAll();
    }

    public function testItHandlesNullDriverConfig(): void
    {
        $log = new LogFake();
        config()->set('logging.default', null);

        $log->info('xxxx');
        $log->channel('null')->assertLogged('info');
    }

    public function testItCanLogStringableObjects(): void
    {
        $log = new LogFake();
        $stringable = new class () implements Stringable {
            public function __toString(): string
            {
                return 'expected message';
            }
        };

        $log->info($stringable);

        $this->assertSame($log->logged('info')->first()['message'], 'expected message');
    }

    public function testItAddsContextToLogs(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'xxxx'])
            ->withContext(['bar' => 'xxxx'])
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);

        $this->assertSame($log->logged('info')->first()['context'], [
            'foo' => 'xxxx',
            'bar' => 'xxxx',
            'baz' => 'xxxx',
        ]);
    }

    public function testItCanClearContext(): void
    {
        $log = new LogFake();

        $log->withContext(['foo' => 'xxxx'])
            ->withoutContext()
            ->info('expected message', [
                'baz' => 'xxxx',
            ]);

        $this->assertSame($log->logged('info')->first()['context'], [
            'baz' => 'xxxx',
        ]);
    }

    public function testItCanAssertAChannelHasBeenForgotten(): void
    {
        $log = new LogFake();

        try {
            $log->assertForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [stack] channel to be forgotten [1] times. It was forgotten [0] times.'));
        }
        $log->forgetChannel('stack');
        $log->assertForgotten();

        try {
            $log->channel('channel')->assertForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [channel] channel to be forgotten [1] times. It was forgotten [0] times.'));
        }
        $log->forgetChannel('channel');
        $log->channel('channel')->assertForgotten();

        try {
            $log->stack(['channel'], 'name')->assertForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [Stack:name.channel] channel to be forgotten [1] times. It was forgotten [0] times.'));
        }
        $log->forgetChannel('Stack:name.channel');
        $log->stack(['channel'], 'name')->assertForgotten();
    }

    public function testItCanAssertAChannelHasNotBeenForgotten(): void
    {
        $log = new LogFake();

        $log->assertNotForgotten();
        $log->forgetChannel('stack');
        try {
            $log->assertNotForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [stack] channel to be forgotten [0] times. It was forgotten [1] times.'));
        }

        $log->channel('channel')->assertNotForgotten();
        $log->forgetChannel('channel');
        try {
            $log->channel('channel')->assertNotForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [channel] channel to be forgotten [0] times. It was forgotten [1] times.'));
        }

        $log->stack(['channel'], 'name')->assertNotForgotten();
        $log->forgetChannel('Stack:name.channel');
        try {
            $log->stack(['channel'], 'name')->assertNotForgotten();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Expected the [Stack:name.channel] channel to be forgotten [0] times. It was forgotten [1] times.'));
        }
    }

    public function testItCanFakeOnDemandChannels(): void
    {
        $logFake = new LogFake();

        $logFake->build([])->info('expected message');

        $logFake->channel('ondemand')->assertLoggedMessage('info', 'expected message');
    }

    public function testItCanRetrieveChannels(): void
    {
        $logFake = new LogFake();

        $channel = $logFake->channel('expected-channel');

        $this->assertSame(['expected-channel' => $channel], $logFake->getChannels());
    }

    public function testItCanBindItselfToTheContainer(): void
    {
        $this->assertNotInstanceOf(LogFake::class, app('log'));

        $logFake = LogFake::bind();

        $this->assertSame($logFake, app('log'));
    }
}
