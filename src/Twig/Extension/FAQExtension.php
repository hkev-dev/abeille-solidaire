<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FAQExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight_search', [$this, 'highlightSearch'], ['is_safe' => ['html']]),
        ];
    }

    public function highlightSearch(string $text, ?string $search): string
    {
        if (!$search) {
            return $text;
        }

        $pattern = '/' . preg_quote($search, '/') . '/i';
        return preg_replace($pattern, '<strong>$0</strong>', $text);
    }
}
