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

    public function __construct(string $version, string $encoding = null)
    {
        $this->version = $version;
        $this->encoding = $encoding;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return XmlContainer|null
     */
    public function getRoot()
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
