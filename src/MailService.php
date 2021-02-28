<?php

namespace Bermuda\Mail;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class MailService
 * @package Bermuda\Mail
 */
final class MailService implements MailServiceInterface
{
    private PHPMailer $mailer;

    public function __construct(PHPMailer $mailer = null)
    {
        $this->mailer = $mailer ?? new PHPMailer();
    }

    public function __clone()
    {
        $this->mailer = clone $this->mailer;
    }

    /**
     * @param PHPMailer $mailer
     * @return self
     */
    public function setMailer(PHPMailer $mailer): self
    {
        $copy = clone $this;
        $copy->mailer = $mailer;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function setBody(Body $body): MailServiceInterface
    {
        $copy = clone $this;

        if ($body->isHTML())
        {
            $copy->mailer->isHTML(true);
            $copy->mailer->AltBody = strip_tags((string) $body);
            $copy->mailer->Body = (string) $body;
        }

        else
        {
            $copy->mailer->Body = (string) $body;
            $copy->mailer->AltBody = (string) $body;
        }

        return $copy;
    }

    /**
     * @param Address $address
     * @return self
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function addAddress(Address $address): self
    {
        $copy = clone $this;
        $copy->mailer->addAddress((string) $address, $address->getName());

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function addAddresses($addresses): MailServiceInterface
    {
        $addresses = is_array($addresses) ?: [$addresses];

        if ($addresses == [])
        {
            throw new \InvalidArgumentException('Empty addresses');
        }

        foreach ($addresses as $address)
        {
            $copy = $this->addAddress($address);
        }

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function addAttachments($attachments): MailServiceInterface
    {
        $attachments = (array) $attachments;

        if ($attachments == [])
        {
            throw new \InvalidArgumentException('Empty attachments');
        }

        foreach ($attachments as $attachment)
        {
            $copy = $this->addAttachment($attachment);
        }

        return $copy;
    }

    /**
     * @param Attachment $attachment
     * @return self
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function addAttachment(Attachment $attachment): self
    {
        $copy = clone $this;
        $copy->mailer->addAttachment(
            $attachment->getPath(),
            $attachment->getFilename(),
            $attachment->getEncoding(),
            $attachment->getType(),
            $attachment->getDisposition()
        );

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject): MailServiceInterface
    {
        $copy = clone $this;
        $copy->mailer->Subject = $subject;

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function send(?string $subject = null, ?Body $body = null, $addresses = null): void
    {
        $self = $this;

        if ($subject != null)
        {
            $self = $this->setSubject($subject);
        }

        if ($body != null)
        {
            $self = $this->setBody($body);
        }

        if ($addresses != null)
        {
            $self = $this->addAddresses($addresses);
        }

        try
        {
            if (!$self->mailer->send() && $self->mailer->isError())
            {
                throw new \RuntimeException($self->mailer->isError());
            }
        }

        catch (\Throwable $e)
        {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
