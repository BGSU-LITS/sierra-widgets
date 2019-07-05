<?php
/**
 * Index Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use App\Exception\NotFoundException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * A class to be invoked for the index action.
 */
class IndexAction
{
    /**
     * The URL to be redirected to.
     * @var string
     */
    private $redirect;

    /**
     * Construct the action with configuration.
     * @param string $redirect The link to be redirected to.
     * @throws NotFoundException The link to redirect to is undefined.
     */
    public function __construct($redirect)
    {
        // The link to be redirected to must be an non-empty string.
        if (!is_string($redirect) || $redirect === '') {
            throw new NotFoundException('No link to redirect to is defined');
        }

        // Set the link to be redirected to.
        $this->redirect = $redirect;
    }

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
        $args;

        // Redirect to the specified URL.
        return $res->withStatus(302)->withHeader('Location', $this->redirect);
    }
}
