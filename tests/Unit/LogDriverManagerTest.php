<?php

declare(strict_types=1);

namespace Parvion\Msg91\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Parvion\Msg91\Logging\LogDriverManager;
use Parvion\Msg91\Logging\Drivers\NullLogDriver;
use Parvion\Msg91\Logging\Drivers\ChannelLogDriver;
use Parvion\Msg91\Logging\Drivers\DatabaseLogDriver;
use Parvion\Msg91\Logging\Drivers\StackLogDriver;

/**
 * Class LogDriverManagerTest
 *
 * Unit tests for the logging driver resolution and proxy behaviour.
 *
 * @package Parvion\Msg91\Tests\Unit
 */
class LogDriverManagerTest extends TestCase
{
    // TODO: Phase 7
    // test_resolves_null_driver_when_driver_is_null()
    // test_resolves_channel_driver_when_driver_is_log()
    // test_resolves_database_driver_when_driver_is_database()
    // test_resolves_stack_driver_when_driver_is_stack()
    // test_throws_on_unknown_driver_name()
    // test_driver_is_cached_after_first_resolution()
    // test_swap_replaces_resolved_driver()
}
