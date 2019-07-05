<?php
/**
 * Application Middleware
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2019 Bowling Green State University Libraries
 * @license MIT
 */

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;

use Slim\Csrf\Guard;
use Slim\Http\Body;

// Add middleware for CSRF protection.
$app->add($container[Guard::class]);

// Add middleware to convert JSON to JSONP when callback is specified.
$app->add(
    function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        $response = $next($request, $response);
        $callback = $request->getParam('callback');

        if ($callback) {
            $type = $response->getHeaderLine('Content-type');

            if (preg_match(':\bapplication/json\b:', $type)) {
                $body = new Body(fopen('php://temp', 'r+'));
                $body->write($callback . '(' . $response->getBody() . ');');

                return $response
                    ->withBody($body)
                    ->withHeader('Content-type', 'application/javascript');
            }
        }

        return $response;
    }
);
