<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('display_name', $this->displayName(...)),
            new TwigFilter('eur', $this->eur(...)),
        ];
    }

    /** Derive a human name from an email local part: sophie.martin -> Sophie Martin. */
    public function displayName(?string $email): string
    {
        if (null === $email || '' === $email) {
            return '—';
        }
        $local = explode('@', $email)[0];
        $local = str_replace(['.', '-', '_'], ' ', $local);

        return ucwords($local);
    }

    public function eur(int|float|string|null $value): string
    {
        return number_format((float) $value, 2, ',', ' ');
    }
}
