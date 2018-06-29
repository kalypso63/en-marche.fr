<?php

namespace AppBundle\Mailer\Message;

use AppBundle\Entity\Adherent;
use Ramsey\Uuid\Uuid;

final class AdherentChangeEmailMessage extends Message
{
    public static function createFromAdherent(Adherent $adherent, string $confirmationLink): self
    {
        return new self(
            Uuid::uuid4(),
            '465792',
            $adherent->getEmailAddress(),
            $adherent->getFullName(),
            'Confirmez votre nouvelle adresse email En-Marche.fr',
            [],
            static::getRecipientVars($adherent->getFirstName(), $confirmationLink)
        );
    }

    private static function getRecipientVars(string $firstName, string $confirmationLink): array
    {
        return [
            'first_name' => self::escape($firstName),
            'activation_link' => $confirmationLink,
        ];
    }
}
