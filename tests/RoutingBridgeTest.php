<?php

declare(strict_types=1);

namespace PhrameCMS\RoutingBridge\Tests;

use PHPUnit\Framework\TestCase;
use PhrameCMS\Core\CoreContainer;
use PhrameCMS\Core\Http\HttpMethod;
use PhrameCMS\Core\Http\Request;
use PhrameCMS\Core\Http\Response;
use PhrameCMS\Core\Routing\Route;
use PhrameCMS\RoutingBridge\RoutingBridge;

final class RoutingBridgeTest extends TestCase
{
    public function testDispatchMatchesRouteAndReturnsHandlerResponse(): void
    {
        if (!RoutingBridge::isAvailable()) {
            self::markTestSkipped('Symfony Routing is unavailable in this environment.');
        }

        $bridge = new RoutingBridge();
        $container = new CoreContainer();

        $routes = [
            Route::create(HttpMethod::GET, '/ping', static fn (): Response => Response::json(['pong' => true])),
        ];

        $response = $bridge->dispatch(
            new Request(HttpMethod::GET, '/ping', [], [], null),
            $routes,
            $container,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->status());
    }

    public function testDispatchReturnsNullForUnknownPath(): void
    {
        if (!RoutingBridge::isAvailable()) {
            self::markTestSkipped('Symfony Routing is unavailable in this environment.');
        }

        $bridge = new RoutingBridge();
        $container = new CoreContainer();

        $routes = [
            Route::create(HttpMethod::GET, '/ping', static fn (): Response => Response::json(['pong' => true])),
        ];

        $response = $bridge->dispatch(
            new Request(HttpMethod::GET, '/unknown', [], [], null),
            $routes,
            $container,
        );

        self::assertNull($response);
    }

    public function testDispatchReturnsNullForMethodMismatch(): void
    {
        if (!RoutingBridge::isAvailable()) {
            self::markTestSkipped('Symfony Routing is unavailable in this environment.');
        }

        $bridge = new RoutingBridge();
        $container = new CoreContainer();

        $routes = [
            Route::create(HttpMethod::GET, '/ping', static fn (): Response => Response::json(['pong' => true])),
        ];

        $response = $bridge->dispatch(
            new Request(HttpMethod::POST, '/ping', [], [], null),
            $routes,
            $container,
        );

        self::assertNull($response);
    }
}
