<?php

declare(strict_types=1);

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class XmlContainer
{
    protected string $name;
    /** @var array<string, string> */
    protected array $attributes = [];
    protected string $content = '';
    protected bool $isContentCdata = false;
    /** @var XmlContainer[] */
    protected array $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
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

    /**
     * @return $this
     */
    public function addAttribute(string $name, string $value): XmlContainer
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, string> $attributes
     *
     * @return $this
     */
    public function setAttributes(array $attributes): XmlContainer
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return $this
     */
    public function setContent(string $content): XmlContainer
    {
        $this->content = $content;

        return $this;
    }

    public function isContentCdata(): bool
    {
        return $this->isContentCdata;
    }

    /**
     * @return $this
     */
    public function setIsContentCdata(bool $isContentCdata): XmlContainer
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

    /**
     * @return $this
     */
    public function addChild(XmlContainer $child): XmlContainer
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @param XmlContainer[] $children
     *
     * @return $this
     */
    public function setChildren(array $children): XmlContainer
    {
        $this->children = $children;

        return $this;
    }

    public function toArray(): array
    {
        $children = [];
        foreach ($this->children as $child) {
            $children[] = $child->toArray();
        }

        return [
            'name' => $this->name,
            'attributes' => $this->attributes,
            'content' => $this->content,
            'isContentCdata' => $this->isContentCdata,
            'children' => $children,
        ];
    }
}
