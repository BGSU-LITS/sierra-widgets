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

use DiDom\Document;

/**
 * A class to be invoked for the index action.
 */
class MarcAction
{
    /**
     * Unproxy replacement.
     * @var string
     */
    private $unproxy;

    /**
     * Construct the action with objects and configuration.
     * @param string $unproxy Unproxy replacement.
     */
    public function __construct(string $unproxy) {
        $this->unproxy = $unproxy;
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
        $args;

        $res = $res->withHeader('Content-Type', 'text/plain;charset=UTF-8');

        $permalink = $req->getParam('permalink');

        if (empty($permalink)) {
            return $res;
        }

        if ($this->unproxy) {
            $permalink = str_replace(
                $this->unproxy,
                '',
                $permalink
            );
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

        try {
            $document = new Document($url, true);
            $response = ltrim($document->first('pre')->text());

            if (!empty($response)) {
                $res->write($response);
            }
        } catch (Exception $e) {
            // Do nothing.
        }

        return $res;
    }
}
