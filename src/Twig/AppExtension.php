<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('country_flag', $this->countryFlag(...)),
        ];
    }

    public function countryFlag(string $code): string
    {
        $code = strtoupper(trim($code));

        if (2 !== strlen($code)) {
            return '🌍';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A')).mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}
