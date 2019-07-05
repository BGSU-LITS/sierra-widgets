<?php
/**
 * Index Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * A class to be invoked for the index action.
 */
class MarcAction
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
        $args;

        $res = $res->withHeader('Content-Type', 'text/plain;charset=UTF-8');

        $permalink = $req->getParam('permalink');

        if (empty($permalink)) {
            return $res;
        }

        $parsed = parse_url($permalink);

        if (empty($parsed['scheme'])
         || empty($parsed['host'])
         || empty($parsed['path'])
         || !preg_match('/record=(b\d+)/', $parsed['path'], $matches)) {
            return $res;
        }

        $url = $parsed['scheme'] . '://';

        if (!empty($parsed['user'])) {
            $url .= $parsed['user'];

            if (!empty($parsed['pass'])) {
                $url .= ':' . $parsed['pass'];
            }

            $url .= '@';
        }

        $url .= $parsed['host'];

        if (!empty($parsed['port'])) {
            $url .= ':' . $parsed['port'];
        }

        $url .= '/search/.' . $matches[1] . '/.' . $matches[1];
        $url .= '/1,1,1,B/marc~' . $matches[1];

        $response = file_get_contents($url);

        if (preg_match(':<pre.*?>(.+?)</pre>:s', $response, $matches)) {
            return $res->write(ltrim($matches[1]));
        }

        return $res;
    }
}
