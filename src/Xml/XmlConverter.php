<?php

namespace Mougrim\XdebugProxy\Xml;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
interface XmlConverter
{
    /**
     * @throws XmlException
     */
    public function parse(string $xml): XmlDocument;

    /**
     * @throws XmlException
     */
    public function generate(XmlDocument $document): string;
}
