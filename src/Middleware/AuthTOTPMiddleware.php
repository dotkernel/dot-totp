<?php

namespace Dot\TOTP\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthTOTPMiddleware implements MiddlewareInterface
{
    private array $routes;
    private array $validationRoute;
    private array $enableTotpRoute;

    public function __construct(
        protected RouterInterface $router,
        array $config
    ) {
        $this->routes          = $config['dot_totp']['totp_required_routes'] ?? [];
        $this->validationRoute = $config['dot_totp']['validate_totp_route'] ?? [];
        $this->enableTotpRoute = $config['dot_totp']['enable_totp_route'] ?? [];
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface|RedirectResponse {
        $subject = $request->getAttribute('entity');

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $routeName   = $routeResult->getMatchedRouteName();

        if (! isset($this->routes[$routeName]) || ! $this->routes[$routeName]) {
            return $handler->handle($request);
        }

        if ($this->routes[$routeName] && $subject->isTotpEnabled()) {
            return new RedirectResponse($this->router->generateUri($this->validationRoute));
        } else {
            return new RedirectResponse($this->router->generateUri($this->enableTotpRoute));
        }
    }
}
