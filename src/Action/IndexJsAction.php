<?php
/**
 * Index Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use App\Exception\RequestException;
use App\Exception\NotFoundException;

use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * A class to be invoked for the index action.
 */
class IndexJsAction extends AbstractAction
{

    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    public function __invoke(Request $req, Response $res, array $args)
    {
        // Unused.
        $req;

        // Render form template.
        return $this->view->render(
            $res->withHeader('Content-Type', 'application/javascript'),
            'index.js.twig',
            $args
        );
    }
}
