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
class CitationsAction extends AbstractAction
{
    /**
     * WorldCat Search API web services key.
     * @var string
     */
    private $wskey;

    /**
     * Construct the action with configuration.
     * @param string $wskey WorldCat Search API web services key.
     */
    public function __construct(
        Messages $flash,
        Session $session,
        Twig $view,
        $wskey
    ) {
        parent::__construct($flash, $session, $view);
        $this->wskey = $wskey;
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

        if (empty($args['oclc']) || !preg_match('/^\d+$/', $args['oclc'])) {
            return $res;
        }

        $response = file_get_contents(
            'http://www.worldcat.org/webservices/catalog/content/citations/' .
            $args['oclc'] . '?cformat=all&wskey=' . $this->wskey
        );

        preg_match_all(
            ':<p class="citation_style_(.+?)">(.+?)</p>:',
            $response,
            $matches,
            PREG_SET_ORDER
        );

        $args['citations'] = [];

        foreach ($matches as $match) {
            $key = $match[1];

            if ($key !== 'APA' && $key !== 'MLA') {
                $key = ucwords(strtolower($key));
            }

            $args['citations'][$key] = $match[2];
        }

        if (empty($args['citations'])) {
            return $res;
        }

        // Render the template.
        return $this->view->render(
            $res->withHeader('Access-Control-Allow-Origin', '*'),
            'dialog/citations.html.twig',
            $args
        );
    }
}
