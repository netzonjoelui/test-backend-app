# Mail

Use the Mail module work with mime emails from netric.

## Theory of Operation

The Mail module utilizes the follow components to compose and send messages.

### Transport
The transport is the actual transportation of the message from the code composing the message to the mail server.

We use a factory to setup the transport based on the settings for the installation and for each account.

#### Regular Mail Transport
This transport is used for sending notices to users of netric, and email on behalf of users.

    $serviceManager = $application->getAccount()->getServiceManager();
    $transport = $serviceManager->get("Netric/Mail/Transport/Transport");
    // Send any Netric\Mail\Message with $transport->send();
    
#### Bulk Mail Transport
This is the transport used for bulk mailers.

    $serviceManager = $application->getAccount()->getServiceManager();
    $transport = $serviceManager->get("Netric/Mail/Transport/BulkTransport");
    // Send any Netric\Mail\Message with $transport->send();
    
In both cases the transports can be configured per account. Account administrators can setup alternate
SMTP servers to use for either.

In the future we may create an API based transport as well for clients to want to expose a mailer database
of some kind to queue message from their network.

### Message
The Message class represents an individual message to send via a transport.

    $message = new \Netric\Mail\Message();
    $message->addFrom("noreply@netric.com");
    $message->addTo($emailAddress);
    $message->setBody($body);
    $message->setEncoding('UTF-8');
    $message->setSubject($subject);

## Example Usage

### Sending a Plain-Text Message

    // Create a new message
    $message = new \Netric\Mail\Message();
    $message->addFrom("noreply@netric.com");
    $message->addTo("test@netric.com");
    $message->setBody("Body Content Here");
    $message->setEncoding('UTF-8');
    $message->setSubject("Test Message");
    
    $serviceManager = $application->getAccount()->getServiceManager();
    $transport = $serviceManager->get("Netric/Mail/Transport/Transport");
    $transport->send($message);
    
### Adding an Attachment with Mime

    use Netric\Mime;

    // First create the parts
    $text = new Mime\Part("Body content Here");
    $text->setType(Mime\Mime::TYPE_TEXT);
    $text->setCharset('utf-8');
    
    $fileStreamHandle = fopen($somefilePath, 'r');
    $attachment = new Mime\Part($fileStreamHandle);
    $attachment->setType('image/jpg');
    $attachment->setFileName('image-file-name.jpg');
    $attachment->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT);
    // Setting the encoding is recommended for binary data
    $attachment->setEncoding(Mime\Mime::ENCODING_BASE64);
    
    // Add all the parts to the message
    $mimeMessage = new Mime\Message();
    $mimeMessage->setParts(array($text, $attachment));
    
    // Create the actual email
    $message = new \Netric\Mail\Message();
    $message->setBody($mimeMessage);

### Creating a MultiPart/Alternative Message with Attachments

    use Netric\Mime;
    
    $html = "<b>my body</b>";
    $text = "*my body*";

    // HTML part
    $htmlPart = new Mime\Part($html);
    $htmlPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
    $htmlPart->setType(Mime\Mime::TYPE_HTML);
    $htmlPart->setCharset("UTF-8");
    
    // Plain text part
    $textPart = new Mime\Part($text);
    $textPart->setEncoding(Mime\Mime::ENCODING_QUOTEDPRINTABLE);
    $textPart->setType(Mime\Mime::TYPE_TEXT);
    $textPart->setCharset("UTF-8");
    
    // Create a content message for the parts
    $content = new Mime\Message();
    $content->addPart($textPart);
    $content->addPart($htmlPart);
    
    // Create mime message and add the content and the attachments as separate parts
    $mimeMessage = new Mime\Message();
    
    // Add text & html alternative
    $contentPart = new Mime\Part($content->generateMessage());        
    $contentPart->setType(Mime\Mime::MULTIPART_ALTERNATIVE);
    $contentPart->setBoundary($content->getMime()->boundary());
    $mimeMessage->addPart($contentPart);

    // Add attachment
    $fileStreamHandle = fopen($somefilePath, 'r');
    $attachment = new Mime\Part($fileStreamHandle);
    $attachment->setType('image/jpg');
    $attachment->setFileName('image-file-name.jpg');
    $attachment->setDisposition(Mime\Mime::DISPOSITION_ATTACHMENT);
    // Setting the encoding is recommended for binary data
    $attachment->setEncoding(Mime\Mime::ENCODING_BASE64);
    $mimeMessage->addPart($attachment);
    
    // Create the actual email
    $message = new \Netric\Mail\Message();
    $message->setBody($mimeMessage);

    
### Unit Testing
To make unit testing easier, there is an in memory transport that never actually sends the message.

This is also used automatically by the transport factory if the system config has email.suppress = true
 to keep the development environment (and unit tests) from sending any actual email messages.

    $transport = new \Netric\Mail\Transport\InMemory.php
    $transport->send($message); // This will not send anything
    $transport->getSentMessages(); // Returns an array of \Netric\Mail\Message
    