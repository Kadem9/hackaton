<?php

namespace App\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly string $iconVersion, private readonly RequestStack $requestStack, private readonly Security $security)
    {
    }

    public function getFunctions() : array
    {
        return [
            new TwigFunction('icon', [$this, 'icon'], ['is_safe' => ['html']]),
            new TwigFunction('current_route_class', [$this, 'currentRouteClass']),
        ];
    }

    public function icon(string $icon, bool $small = false) : string
    {
        $class = ["icon", "icon-{$icon}"];
        if($small) $class[] = "icon-sm";
        $classStr = implode(" ", $class);
        return <<<HTML
<svg class="{$classStr}">
    <use xlink:href="/icons.svg?{$this->iconVersion}=1&logo#{$icon}"></use>
</svg>
HTML;

    }

    public function currentRouteClass(string $partialRoute, ?string $excludeRoute = null, string $activeClass = "active") : string
    {
        $request = $this->requestStack->getCurrentRequest();
        return strstr($request->attributes->get('_route', ''), $partialRoute) &&
            ($excludeRoute === null || !strstr($request->attributes->get('_route', ''), $excludeRoute)) ? $activeClass : '';
    }

}