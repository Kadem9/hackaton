<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;

class FormSearchHelper
{
    private array $searchParams = [];
    private array $valueLabels = [];
    private ?array $sortParams = null;

    public function __construct(private readonly array $searchNames, ?Request $request = null, ?array $defaultSort = null, string $method = Request::METHOD_GET, private readonly string $sortName = 'sort', private readonly string $sortDirName = 'sortDir')
    {
        $this->sortParams = $defaultSort;
        foreach ($this->searchNames as $searchName) {
            $this->searchParams[$searchName] = null;
            $this->valueLabels[$searchName] = [];
        }
        if($request) {
            $this->handleRequest($request, $method);
        }
    }

    public function handleRequest(Request $request, string $method = Request::METHOD_GET): void
    {
        $data = $method === Request::METHOD_GET ? $request->query : $request->request;
        foreach ($this->searchNames as $searchName) {
            if($data->has($searchName)) {
                $this->searchParams[$searchName] = $data->get($searchName);
            }
        }
        if($data->has($this->sortName)) {
            $this->sortParams = [
                'sort' => $data->get($this->sortName),
                'sortDir' => $data->get($this->sortDirName, 'asc')
            ];
        }
    }

    public function set(string $name, mixed $value) : self
    {
        $this->searchParams[$name] = $value;
        return $this;
    }

    public function get(string $name) : ?string
    {
        return array_key_exists($name, $this->searchParams) ? $this->searchParams[$name] : null;
    }

    public function getInt(string $name) : ?int
    {
        return (array_key_exists($name, $this->searchParams) && $this->searchParams[$name]) ? intval($this->searchParams[$name]) : null;
    }

    public function isSearching() : bool
    {
        foreach ($this->searchParams as $searchParam) {
            if($searchParam || strlen($searchParam) > 0) return true;
        }
        return false;
    }

    public function setValueLabel(string $name, string $label) : self
    {
        if($value = $this->get($name)) {
            $this->valueLabels[$name][$value] = $label;
        }
        return $this;
    }

    public function getValueLabel(string $name) : ?string
    {
        if($value = $this->get($name)) {
            return array_key_exists($value, $this->valueLabels[$name]) ? $this->valueLabels[$name][$value] : null;
        }
        return null;
    }

    public function getSortParams() : ?array
    {
        return $this->sortParams;
    }

    public function getSortValue() : string
    {
        return $this->sortParams['sort'] ?? '';
    }

    public function getSortDir() : string
    {
        return $this->sortParams['sortDir'] ?? 'asc';
    }

    public function getSortName() : string
    {
        return $this->sortName;
    }

    public function getSortDirName() : string
    {
        return $this->sortDirName;
    }

}