<?php

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface XmlConverter
{
    /**
     * @param string $xml
     *
     * @throws XmlException
     *
     * @return XmlDocument
     */
    public function parse(string $xml): XmlDocument;

    /**
     * @param XmlDocument $document
     *
     * @throws XmlException
     *
     * @return string
     */
    public function generate(XmlDocument $document): string;
}
