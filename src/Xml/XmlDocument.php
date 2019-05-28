<?php

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class XmlDocument
{
    protected $version;
    protected $encoding;
    protected $root;

    public function __construct(string $version, ?string $encoding = null)
    {
        $this->version = $version;
        $this->encoding = $encoding;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function getRoot(): ?XmlContainer
    {
        return $this->root;
    }

    /**
     * @param XmlContainer $root
     *
     * @return $this
     */
    public function setRoot(XmlContainer $root): XmlDocument
    {
        $this->root = $root;

        return $this;
    }
}
