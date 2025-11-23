<?php

// Если не используешь namespaces, просто убери эту строку. 
// Но лучше оставить, чтобы имена классов не конфликтовали с другими либами.
namespace Wakanda\SteamAuthZero;

// readonly работает в PHP 8.1+. Если версия ниже, убери слово readonly.
class SteamUser
{
    public function __construct(
        public string $steamId,
        public string $personaName,
        public string $avatarUrl,
        public string $profileUrl
    ) {}

    /**
     * Фабричный метод для создания объекта из массива Steam API
     */
    public static function fromArray(array $data): self
    {
        return new self(
            steamId: $data['steamid'],
            personaName: $data['personaname'],
            avatarUrl: $data['avatarfull'],
            profileUrl: $data['profileurl']
        );
    }
}