<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 *
 * Actually XmlContainerArray is recursive, but phpstan doesn't support recursive types
 *
 * @phpstan-type XmlContainerArray array{name: string, attributes: array<string, string>, content: string, isContentCdata: bool, children: array{string, mixed}}
 */
class XmlContainer
{
    /** @var array<string, string> */
    protected array $attributes = [];
    protected string $content = '';
    protected bool $isContentCdata = false;
    /** @var XmlContainer[] */
    protected array $children = [];

    public function __construct(
        protected readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function addAttribute(string $name, string $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isContentCdata(): bool
    {
        return $this->isContentCdata;
    }

    public function setIsContentCdata(bool $isContentCdata): static
    {
        $this->isContentCdata = $isContentCdata;

        return $this;
    }

    /**
     * @return XmlContainer[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(XmlContainer $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @param XmlContainer[] $children
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @phpstan-return XmlContainerArray
     */
    public function toArray(): array
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = $child->toArray();
        }

        /** @phpstan-ignore return.type */
        return [
            'name' => $this->name,
            'attributes' => $this->attributes,
            'content' => $this->content,
            'isContentCdata' => $this->isContentCdata,
            'children' => $children,
        ];
    }
}
