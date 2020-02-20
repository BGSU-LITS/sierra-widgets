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
class EmailAction extends AbstractAction
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
     * Email subject.
     * @var string
     */
    private $subject;

    /**
     * Unproxy replacement.
     * @var string
     */
    private $unproxy;

    /**
     * Construct the action with objects and configuration.
     * @param Messages $flash Flash messenger.
     * @param Session $session Session manager.
     * @param Twig $view View renderer.
     * @param LoggerInterface $logger PSR-3 logger.
     * @param Swift_Mailer Email sender.
     * @param array $from Email from address.
     * @param string $subject Email subject.
     * @param string $unproxy Unproxy replacement.
     */
    public function __construct(
        Messages $flash,
        Session $session,
        Twig $view,
        LoggerInterface $logger,
        Swift_Mailer $mailer,
        array $from,
        string $subject,
        string $unproxy
    ) {
        parent::__construct($flash, $session, $view);
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->from = $from;
        $this->subject = $subject;
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
        $args['id'] = bin2hex(random_bytes(8));

        if ($req->isPost()) {
            $args = $this->processPost($req, $args);
        }

        // Render the template.
        return $this->view->render(
            $res->withHeader('Access-Control-Allow-Origin', '*'),
            'dialog/email.html.twig',
            $args
        );
    }

    protected function processPost(Request $req, array $args)
    {
        $from = $this->from['address'];

        if (!empty($this->from['name'])) {
            $from = [$from => $this->from['name']];
            $args['from'] = $this->from['name'];
        }

        $subject = $this->subject;

        try {
            $args['email'] = $req->getParam('email');

            if (empty($args['email'])) {
                throw new RequestException(
                    'An email address was not specified.'
                );
            }

            if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RequestException(
                    'The specified email address was invalid.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'Please enter a valid email address.'
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

            if ($this->unproxy) {
                $permalink = str_replace(
                    $this->unproxy,
                    '',
                    $permalink
                );
            }

            $document = new Document($permalink, true);

            foreach ($document->find('.bibInfoData') as $data) {
                $args['details'][] = $data->parent()->html();

                $label = $data->parent()->first('.bibInfoLabel');

                if ($label) {
                    $label = rtrim(trim($label->text()), ':');

                    if ($label === 'Title') {
                        $subject .= ': ' . $data->text();
                    }

                    if ($label === 'Permalink') {
                        break;
                    }
                }
            }

            $items = $document->find('.bibDisplayItems tr');

            foreach ($items as $row) {
                $args['items'][] = $row->html();
            }

            if (empty($args['details']) && empty($args['items'])) {
                throw new RequestException(
                    'Details and item records could not be retrieved.'
                );
            }

            preg_match('/^https?:\/\/.+?\//', $permalink, $matches);

            foreach (['details', 'items'] as $arg) {
                foreach (array_keys($args[$arg]) as $key) {
                    $args[$arg][$key] = preg_replace(
                        ['/\s(class|valign|width)=".*?"/', '/\xc2\xa0/'],
                        ['', ' '],
                        $args[$arg][$key]
                    );

                    if (!empty($matches[0])) {
                        $args[$arg][$key] = str_replace(
                            '="/',
                            '="' . $matches[0],
                            $args[$arg][$key]
                        );
                    }
                }
            }

            $jacket = $document->first('.bibDisplayJacket a');
            $args['jacket'] = $jacket->html();
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'An unexpected error has occurred.'
            ];

            return $args;
        }

        try {
            // Create a message with all of the provided data.
            $message = $this->mailer->createMessage()
                ->setSubject($subject)
                ->setFrom($from)
                ->setTo($args['email'])
                ->setBody(
                    $this->view->fetch('email/email.html.twig', $args),
                    'text/html'
                );

            // Send the message.
            if (!$this->mailer->send($message)) {
                throw new RequestException(
                    'Could not send email.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception', ['exception' => $e]);

            $args['messages'][] = [
                'level' => 'failure',
                'message' => 'The email could not be sent.'
            ];

            return $args;
        }

        $args['messages'][] = [
            'level' => 'success',
            'message' => 'The email has been sent.'
        ];

        return $args;
    }
}
