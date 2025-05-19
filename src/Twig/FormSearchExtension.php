<?php

namespace App\Twig;

use App\Helper\FormSearchHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormSearchExtension extends AbstractExtension
{
    public function __construct(private readonly Environment $twig, private readonly RequestStack $requestStack, private readonly RouterInterface $router)
    {
    }

    public function getFunctions() : array
    {
        return [
            new TwigFunction('sort_render', [$this, 'sortRender']),
        ];
    }
    public function sortRender(string $label, FormSearchHelper $formSearchHelper, string $sort) : string
    {
        $sortDir = 'asc';
        $icon = 'sort';
        $current = false;
        if($formSearchHelper->getSortValue() === $sort) {
            $sortDir = $formSearchHelper->getSortDir() === 'asc' ? 'desc' : 'asc';
            $icon = $sortDir === 'asc' ? 'sort-up' : 'sort-down';
            $current = true;
        }
        $request = $this->requestStack->getCurrentRequest();
        $route = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params');
        $query = $request->query->all();
        $url = $this->router->generate($route, array_merge($routeParams, $query, [$formSearchHelper->getSortName() => $sort, $formSearchHelper->getSortDirName() => $sortDir]));
        return $this->twig->render('_partial/sort.html.twig', [
            'label' => $label,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'current' => $current,
            'icon' => $icon,
            'url' => $url,
            'formSearch' => $formSearchHelper
        ]);
    }
}