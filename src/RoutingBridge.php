<?php

declare(strict_types=1);

namespace PhrameCMS\RoutingBridge;

use PhrameCMS\Core\Contracts\ContainerBuilderInterface;
use PhrameCMS\Core\Contracts\RoutingEngineInterface;
use PhrameCMS\Core\Http\Request;
use PhrameCMS\Core\Http\Response;
use PhrameCMS\Core\Routing\Route;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

final class RoutingBridge implements RoutingEngineInterface
{
    public static function isAvailable(): bool
    {
        return class_exists(RouteCollection::class) && class_exists(UrlMatcher::class);
    }

    /**
     * @param array<int, Route> $routes
     */
    public function dispatch(Request $request, array $routes, ContainerBuilderInterface $container): ?Response
    {
        if (!self::isAvailable()) {
            return null;
        }

        $collection = new RouteCollection();

        foreach ($routes as $index => $route) {
            $collection->add(
                sprintf('route_%d', $index),
                new SymfonyRoute(
                    $route->path,
                    ['_route_index' => $index],
                    [],
                    [],
                    '',
                    [],
                    [$route->method->value],
                )
            );
        }

        $context = (new RequestContext())
            ->setMethod($request->method->value)
            ->setPathInfo($request->path);

        $matcher = new UrlMatcher($collection, $context);

        try {
            $match = $matcher->match($request->path);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return null;
        }

        $routeIndex = $match['_route_index'] ?? null;
        if (!is_int($routeIndex) || !isset($routes[$routeIndex])) {
            return null;
        }

        $route = $routes[$routeIndex];

        return ($route->handler)($request, $container);
    }
}
