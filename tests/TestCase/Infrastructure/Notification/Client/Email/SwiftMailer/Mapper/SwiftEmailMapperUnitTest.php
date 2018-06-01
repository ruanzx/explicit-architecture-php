<?php

declare(strict_types=1);

/*
 * This file is part of the Explicit Architecture POC,
 * which is created on top of the Symfony Demo application.
 *
 * (c) Herberto Graça <herberto.graca@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Acme\App\Test\TestCase\Infrastructure\Notification\Client\Email\SwiftMailer\Mapper;

use Acme\App\Core\Port\Notification\Client\Email\Email;
use Acme\App\Core\Port\Notification\Client\Email\EmailAddress;
use Acme\App\Core\Port\Notification\Client\Email\EmailAttachment;
use Acme\App\Infrastructure\Notification\Client\Email\SwiftMailer\Mapper\SwiftEmailMapper;
use Acme\App\Test\Framework\AbstractUnitTest;
use DateTime;
use Swift_Attachment;
use Swift_Message;
use Swift_Mime_Headers_DateHeader;
use Swift_Mime_Headers_IdentificationHeader;
use Swift_Mime_Headers_ParameterizedHeader;
use Swift_Mime_SimpleHeaderSet;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;
use Swift_RfcComplianceException;

/**
 * @author Herberto Graca <herberto.graca@gmail.com>
 * @author Marijn Koesen
 */
class SwiftEmailMapperUnitTest extends AbstractUnitTest
{
    /**
     * @var string
     */
    private $base64EncodedPng = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAp1JREFUeNqEU21IU1EYfu7unW5Ty6aBszYs6MeUjGVYokHYyH5E1B9rZWFEFPQnAwmy6Hc/oqhfJsRKSSZGH1JIIX3MNCsqLTD9o1Oj6ebnnDfvvefezrnbdCHhCw/n433P8z7nPe/hBEEAtX0U7hc164uwuvVSXKwZLoOmaRDim+7m9vZa0WiEKSUFFpNpCWlmMyypqTDRuYn6t3k8vmQ2gRDCxs0t9fW45F52aBTROJLtZl7nEZad2m+KtoQCQ0FBARyOCGRZ/q92I1WgqqXlfdd95VsrK8/pChIEqqpCkiQsiCII0aBQZZoWl8lzFDwsFjMl0DBLY8Lj41hBwK4jSQrWOIphL6xYyhwJDWGo6wFSaH1Y3PTCAsITE1oyAa8flhWkbSiCLX8vun11eiGIpiJ/z2nYdx5HqLdVV7elrOzsuqysL3rmBIGiKPizKCHHWY4PLVeQbnXAdegqdhy+hu8dDTBnbqQJZJ1A7u+vz7RaiymWCZgCRSF6Edk8b9cx+B/W6WuVxPaZnyiqXoPpyUmVYvkKTIFClHigEieKjYuSvETUllaF4GAUM1NT6ooaJDKx+aDfC9fByxj90REb+9ppmIoAscH/6leg8MS9DJXPAM9xHCM443K57C6biMjcHDaVVCHw9RmCA2/RGC5C00AqXk/m4p20HZK4CM/J3Zk9n0ecMBhDQnJHcrTisyMfdQXOilrdMfxcwoHq/fg5R59TiQV3hYGKo6X2J/c7LyQIjOx9GXhOw/zoJ8wEevRGyp53o/lGMNYsBgPtEwLecwov7/jGDKa1twT6o3KpL4MdZgGsWZLtfPr7f1q58k1JNHy7YYaM+J+K3Y2PmAIbRavX66229hrGVvvL5uzsHDEUvUu+NT1my78CDAAMK1a8/QaZCgAAAABJRU5ErkJggg==';

    /**
     * @test
     * @dataProvider getMapData
     *
     * @throws Swift_RfcComplianceException
     */
    public function map(Email $email, Swift_Mime_SimpleMessage $expectedMessage): void
    {
        $givenMessage = $this->getMapper()->map($email);

        $this->assertEquals($expectedMessage->getSubject(), $givenMessage->getSubject());
        $this->assertEquals($expectedMessage->getFrom(), $givenMessage->getFrom());
        $this->assertEquals($expectedMessage->getTo(), $givenMessage->getTo());
        $this->assertEquals($expectedMessage->getCc(), $givenMessage->getCc());
        $this->assertEquals($expectedMessage->getBcc(), $givenMessage->getBcc());
        $this->assertHeaders($givenMessage->getHeaders(), $expectedMessage->getHeaders());
        $this->assertParts($givenMessage->getChildren(), $expectedMessage->getChildren());
    }

    /**
     * @throws Swift_RfcComplianceException
     */
    protected function assertHeaders(Swift_Mime_SimpleHeaderSet $givenHeaders, Swift_Mime_SimpleHeaderSet $expectedHeaders): void
    {
        $this->assertCount(\count($expectedHeaders->listAll()), $givenHeaders->listAll());

        $this->resetAutoGeneratedIdAndBoundaryAndTimestamp($expectedHeaders);
        $this->resetAutoGeneratedIdAndBoundaryAndTimestamp($givenHeaders);

        $this->assertEquals($expectedHeaders, $givenHeaders);
    }

    /**
     * @throws Swift_RfcComplianceException
     */
    private function resetAutoGeneratedIdAndBoundaryAndTimestamp(Swift_Mime_SimpleHeaderSet $headers): void
    {
        foreach ($headers->getAll() as $h) {
            if ($h instanceof Swift_Mime_Headers_IdentificationHeader) {
                $h->setId('32477d241737c7491a995b92018a2254@swift.generated');
            } elseif ($h instanceof Swift_Mime_Headers_ParameterizedHeader) {
                $h->setParameter('boundary', null);
            } elseif ($h instanceof Swift_Mime_Headers_DateHeader) {
                $h->setDateTime(new DateTime('2000-12-10 10:00:00'));
            }
        }
    }

    /**
     * @param Swift_Mime_SimpleMimeEntity[] $givenParts
     * @param Swift_Mime_SimpleMimeEntity[] $expectedParts
     */
    protected function assertParts(array $givenParts, array $expectedParts): void
    {
        $this->assertCount(\count($expectedParts), $givenParts);

        foreach ($expectedParts as $i => $expectedPart) {
            $this->assertPart($givenParts[$i], $expectedPart);
        }
    }

    protected function assertPart(Swift_Mime_SimpleMimeEntity $givenPart, Swift_Mime_SimpleMimeEntity $expectedPart): void
    {
        $this->assertSame($expectedPart->getContentType(), $givenPart->getContentType());
        $this->assertSame($expectedPart->getBody(), $givenPart->getBody());
        $this->assertSame($expectedPart->toString(), $givenPart->toString());
    }

    public function getMapData(): array
    {
        return [[$this->getEmail(), $this->getSwiftMessage()]];
    }

    protected function getEmail(): Email
    {
        $email = new Email('subject', new EmailAddress('from@address.com'));

        $email->addTo(new EmailAddress('to-1@address.com'));
        $email->addTo(new EmailAddress('to-2@address.com', 'to-2'));

        $email->addCc(new EmailAddress('cc-1@address.com'));
        $email->addCc(new EmailAddress('cc-2@address.com', 'cc-2'));

        $email->addBcc(new EmailAddress('bcc-1@address.com'));
        $email->addBcc(new EmailAddress('bcc-2@address.com', 'bcc-2'));

        $email->setBodyHtml('html');
        $email->setBodyText('text');

        $email->addHeader('header-1');
        $email->addHeader('header-2', 'some cool value');

        $email->addAttachment(
            new EmailAttachment('title.png', 'image/png', base64_decode($this->base64EncodedPng, true))
        );

        return $email;
    }

    protected function getSwiftMessage(): Swift_Message
    {
        $swiftMessage = new Swift_Message('subject');
        $swiftMessage->setFrom('from@address.com');
        $swiftMessage->addTo('to-1@address.com');
        $swiftMessage->addTo('to-2@address.com', 'to-2');

        $swiftMessage->addCc('cc-1@address.com');
        $swiftMessage->addCc('cc-2@address.com', 'cc-2');

        $swiftMessage->addBcc('bcc-1@address.com');
        $swiftMessage->addBcc('bcc-2@address.com', 'bcc-2');

        $swiftMessage->addPart('html', 'text/html');
        $swiftMessage->addPart('text', 'text/plain');

        $swiftMessage->getHeaders()->addTextHeader('header-1');
        $swiftMessage->getHeaders()->addTextHeader('header-2', 'some cool value');

        $swiftMessage->attach(
            new Swift_Attachment(base64_decode($this->base64EncodedPng, true), 'title.png', 'image/png')
        );

        return $swiftMessage;
    }

    protected function getMapper(): SwiftEmailMapper
    {
        return new SwiftEmailMapper();
    }
}