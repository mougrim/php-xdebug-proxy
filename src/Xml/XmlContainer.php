<?php

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class XmlContainer
{
    protected $name;
    protected $attributes = [];
    protected $content = '';
    protected $isContentCdata = false;
    protected $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function addAttribute(string $name, string $value): XmlContainer
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @param string[] $attributes
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
     * @param string $content
     *
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
     * @param bool $isContentCdata
     *
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
     * @param XmlContainer $child
     *
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
}
