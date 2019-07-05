<?php
/**
 * Application Routes
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2019 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Slim\Container;
use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;

// Index action.
$container[IndexAction::class] = function (Container $container) {
    return new IndexAction($container['settings']['app']['redirect']);
};

$app->get('/', IndexAction::class);

$container[IndexJsAction::class] = function (Container $container) {
    return new IndexJsAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class]
    );
};

$app->get('/index.js', IndexJsAction::class);

$container[CitationsAction::class] = function(Container $container) {
    return new CitationsAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class],
        $container['settings']['citations']['wskey']
    );
};

$app->get('/citations/{oclc}', CitationsAction::class);

$container[MarcAction::class] = function(Container $container) {
    return new MarcAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class]
    );
};

$app->get('/marc', MarcAction::class);
