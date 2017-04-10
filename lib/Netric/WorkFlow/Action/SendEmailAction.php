<?php
/**
 * @author joe, sky.stebnicki@aereus.com
 * @copyright Copyright (c) 2015 Aereus Corporation (http://www.aereus.com)
 */
namespace Netric\WorkFlow\Action;

use Netric\Entity\EntityInterface;
use Netric\EntityLoader;
use Netric\Mail\SenderService;
use Netric\WorkFlow\WorkFlowInstance;

/**
 * Action to send email messages
 *
 * Params in the 'data' field
 *
 *  fid     int OPTIONAL The id of an html_template entity (it's called fid because it used to be a file)
 *  subject string The subject of the message to send (if not fid has been supplied)
 *  body    string The body to send (if no fid has been supplied)
 *  from    string Email address to be sent from
 *  to      string[] Array of addresses to send to
 *  to_other string Comma separated list of people to send to - appended to 'to'
 *  cc      string[] Array of addresses to cc
 *  cc_other string Comma separated list of people to cc - appended to 'cc'
 *  bcc     string[] Array of addresses to bcc
 *  bcc_other string Comma separated list of people to bcc - appended to 'bcc'
 *
 */
class SendEmailAction extends AbstractAction implements ActionInterface
{
    /**
     * Senders service for sending messages
     *
     * @var SenderService
     */
    private $senderService = null;

    /**
     * Construct an email action with the required dependencies
     *
     * @param EntityLoader $entityLoader
     * @param ActionFactory $actionFactory
     * @param SenderService $senderService
     */
    public function __construct(EntityLoader $entityLoader, ActionFactory $actionFactory, SenderService $senderService)
    {
        $this->senderService = $senderService;
        parent::__construct($entityLoader, $actionFactory);
    }

    /**
     * Execute this action
     *
     * @param WorkFlowInstance $workflowInstance The workflow instance we are executing in
     * @return bool true on success, false on failure
     */
    public function execute(WorkFlowInstance $workflowInstance)
    {
        // Get the entity we are executing against
        $entity = $workflowInstance->getEntity();

        // Get merged params
        $params = $this->getParams($entity);

        // Create the email message entity
        $email = $this->entityLoader->create("email_message");

        // Check if we are using an html_template id for loading a template
        if (isset($params['fid'])) {
            $template = $this->entityLoader->get("html_template", $params['fid']);
            $templateBody = $template->getValue("body_html");
            $templateSubject = ($template->getValue("subject")) ? $template->getValue("subject") : $template->getValue("name");

            // Merge any variable with the entity we are executing against
            $templateBody = $this->replaceParamVariables($entity, $templateBody);
            $templateSubject = $this->replaceParamVariables($entity, $templateSubject);

            // Set subject and body
            $email->setValue("subject", $templateSubject);
            $email->setValue("body", $templateBody);
            $email->setValue("body_type", "html");
        } else {
            $email->setValue("subject", $params['subject']);
            $email->setValue("body", $params['body']);
            // The action form only allows plain text emails if not using a template
            $email->setValue("body_type", "plain");
        }

        // From
        $email->setValue("sent_from", $params['from']);
        $email->setValue("reply_to", $params['from']);

        // To
        $to = "";
        if (isset($params['to']) && is_array($params['to']))
        {
            foreach ($params['to'] as $rec)
            {
                if ($to) $to .= ", ";
                $to .= $rec;
            }
        }
        if (isset($params['to_other']))
        {
            if ($to) $to .= ", ";
            $to .= $params['to_other'];
        }

        $email->setValue("send_to", $to);

        // Cc
        $to = "";
        if (isset($params['cc']) && is_array($params['cc']))
        {
            foreach ($params['cc'] as $rec)
            {
                if ($to) $to .= ", ";
                $to .= $rec;
            }
        }
        if (isset($params['cc_other']))
        {
            if ($to) $to .= ", ";
            $to .= $params['cc_other'];
        }
        $email->setValue("cc", $to);

        // Bcc
        $to = "";
        if (isset($params['bcc']) && is_array($params['bcc']))
        {
            foreach ($params['bcc'] as $rec)
            {
                if ($to) $to .= ", ";
                $to .= $rec;
            }
        }
        if (isset($params['bcc_other']))
        {
            if ($to) $to .= ", ";
            $to .= $params['bcc_other'];
        }
        $email->setValue("bcc", $to);

        if ($this->senderService->send($email)) {
            return true;
        } else {
            $this->errors[] = $this->senderService->getLastError();
            return false;
        }
    }
}