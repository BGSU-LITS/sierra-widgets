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
use Psr\Log\LoggerInterface;
use Swift_Mailer;

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

$app->get('/js/index.js', IndexJsAction::class);

$container[CitationsAction::class] = function(Container $container) {
    return new CitationsAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class],
        $container['settings']['citations']['wskey']
    );
};

$app->get('/citations/{oclc}', CitationsAction::class);

$container[EmailAction::class] = function(Container $container) {
    return new EmailAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class],
        $container[LoggerInterface::class],
        $container[Swift_Mailer::class],
        $container['settings']['smtp']['from'],
        $container['settings']['smtp']['subject'],
        $container['settings']['widgets']['unproxy']
    );
};

$app->map(['GET', 'POST'], '/email', EmailAction::class);

$container[TextAction::class] = function(Container $container) {
    return new TextAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class],
        $container[LoggerInterface::class],
        $container[Swift_Mailer::class],
        $container['settings']['smtp']['from'],
        $container['settings']['smtp']['carriers'],
        $container['settings']['widgets']['unproxy']
    );
};

$app->map(['GET', 'POST'], '/text', TextAction::class);

$container[MarcAction::class] = function(Container $container) {
    return new MarcAction(
        $container['settings']['widgets']['unproxy']
    );
};

$app->get('/marc', MarcAction::class);
