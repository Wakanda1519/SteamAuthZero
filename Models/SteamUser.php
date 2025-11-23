<?php

namespace Wakanda\SteamAuthZero;

class SteamUser
{
    public function __construct(
        public string $steamId,
        public string $personaName,
        public string $avatarUrl,
        public string $profileUrl
    ) {}

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
