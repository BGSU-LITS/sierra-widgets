<?php
/**
 * Index Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use App\Exception\RequestException;

use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Swift_Mailer;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use DiDom\Document;

/**
 * A class to be invoked for the index action.
 */
class TextAction extends AbstractAction
{
    /**
     * PSR-3 logger.
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Email sender.
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * Email from.
     * @var array
     */
    private $from;

    /**
     * Text message carriers.
     * @var array
     */
    private $carriers;

    /**
     * Construct the action with objects and configuration.
     * @param Messages $flash Flash messenger.
     * @param Session $session Session manager.
     * @param Twig $view View renderer.
     * @param LoggerInterface $logger PSR-3 logger.
     * @param Swift_Mailer Email sender.
     * @param array $from Email from address.
     * @param array carriers Text message carriers.
     */
    public function __construct(
        Messages $flash,
        Session $session,
        Twig $view,
        LoggerInterface $logger,
        Swift_Mailer $mailer,
        array $from,
        array $carriers
    ) {
        parent::__construct($flash, $session, $view);
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->from = $from;
        $this->carriers = $carriers;
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
        $args['id'] = bin2hex(random_bytes(8));

        foreach ($this->carriers as $carrier) {
            $args['carriers'][$carrier['host']] = $carrier['name'];
        }

        if ($req->isPost()) {
            $args = $this->processPost($req, $args);
        }

        // Render the template.
        return $this->view->render(
            $res->withHeader('Access-Control-Allow-Origin', '*'),
            'dialog/text.html.twig',
            $args
        );
    }

    protected function processPost(Request $req, array $args)
    {
        try {
            $args['tel'] = $req->getParam('tel');

            if (empty($args['tel'])) {
                throw new RequestException(
                    'A phone number was not specified.'
                );
            }

            if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $args['tel'])) {
                throw new RequestException(
                    'The specified phone number was invalid.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'Please enter a valid phone number.'
            ];

            return $args;
        }

        try {
            $args['carrier'] = $req->getParam('carrier');

            if (empty($args['carrier'])) {
                throw new RequestException(
                    'A carrier was not specified.'
                );
            }

            if (empty($args['carriers'][$args['carrier']])) {
                throw new RequestException(
                    'The specified carrier was invalid.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'Please select a valid carrier.'
            ];

            return $args;
        }

        try {
            $permalink = $req->getParam('permalink');

            if (empty($permalink)) {
                throw new RequestException(
                    'A permalink was not specified.'
                );
            }

            $document = new Document($permalink, true);

            foreach ($document->find('.bibInfoData') as $data) {
                $label = $data->parent()->first('.bibInfoLabel');

                if ($label) {
                    $label = rtrim(trim($label->text()), ':');

                    if ($label === 'Title') {
                        $args['title'] = trim($data->text());
                        break;
                    }
                }
            }

            $items = $document->find('.bibDisplayItems tr');
            $headers = [];

            foreach ($items as $row) {
                if (empty($headers)) {
                    foreach ($row->find('th') as $header) {
                        $headers[] = rtrim(trim($header->text()), ':');
                    }

                    continue;
                }

                $data = array_combine($headers, $row->find('td'));

                if (!empty($data['Location'])) {
                    $args['location'] = trim($data['Location']->text());
                }

                if (!empty($data['Call Number'])) {
                    $args['callnumber'] = trim($data['Call Number']->text());
                }

                break;
            }

            if (empty($args['title'])
             && empty($args['location'])
             && empty($args['callnumber'])) {
                throw new RequestException(
                    'Record information could not be retrieved.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'An unexpected error has occurred.'
            ];

            return $args;
        }

        try {
            $from = $this->from['address'];
            $to = str_replace('-', '', $args['tel']) . '@' . $args['carrier'];
            $body = '';

            foreach (['location', 'callnumber', 'title'] as $key) {
                if (!empty($body)) {
                    $body .= ' | ';
                }

                $body .= $args[$key];
            }

            $body = substr($body, 0, 160);

            // Create a message with all of the provided data.
            $message = $this->mailer->createMessage()
                ->setFrom($from)
                ->setTo($to)
                ->setBody($body, 'text/plain');

            // Send the message.
            if (!$this->mailer->send($message)) {
                throw new RequestException(
                    'Could not send text message.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'The text could not be sent.'
            ];

            return $args;
        }

        $args['messages'][] = [
            'level' => 'success',
            'message' => 'The text has been sent.'
        ];

        return $args;
    }
}
