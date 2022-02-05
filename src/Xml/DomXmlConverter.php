<?php

/** @noinspection PhpComposerExtensionStubsInspection ext-dom is declared as suggest */

namespace Mougrim\XdebugProxy\Xml;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMEntityReference;
use DOMException;
use DOMNode;
use DOMText;
use DOMXPath;
use Psr\Log\LoggerInterface;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
use const LIBXML_NONET;
use function count;
use function error_clear_last;
use function error_get_last;
use function libxml_disable_entity_loader;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DomXmlConverter implements XmlConverter
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function getError(int $type): string
    {
        switch ($type) {
            case E_ERROR: // 1
                return 'E_ERROR';
            case E_WARNING: // 2
                return 'E_WARNING';
            case E_PARSE: // 4
                return 'E_PARSE';
            case E_NOTICE: // 8
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384
                return 'E_USER_DEPRECATED';
            default:
                return "[{$type}] Unknown";
        }
    }

    /**
     * @throws XmlException
     */
    public function parse(string $xml): XmlDocument
    {
        $domDocument = new DOMDocument();
        if (\PHP_VERSION_ID < 80000) {
            $oldDisableValue = libxml_disable_entity_loader();
            $result = @$domDocument->loadXML($xml, LIBXML_NONET);
            libxml_disable_entity_loader($oldDisableValue);
        } else {
            $result = @$domDocument->loadXML($xml, LIBXML_NONET);
        }
        if (!$result) {
            $error = error_get_last();
            error_clear_last();
            $message = '';
            if ($error) {
                $message = ": [{$this->getError($error['type'])}] {$error['message']} in {$error['file']}:{$error['line']}";
            }
            throw new XmlParseException("Can't parse xml{$message}");
        }

        return $this->toDocument($domDocument);
    }

    /**
     * @throws XmlParseException
     */
    protected function toDocument(DOMDocument $domDocument): XmlDocument
    {
        $document = new XmlDocument($domDocument->xmlVersion, $domDocument->xmlEncoding);

        if (count($domDocument->childNodes) > 1) {
            throw new XmlParseException('Too many child nodes in document');
        }

        if ($domDocument->documentElement) {
            $document->setRoot($this->toContainer($domDocument, $domDocument->documentElement));
        }

        return $document;
    }

    /**
     * @throws XmlParseException
     */
    protected function toContainer(DOMDocument $domDocument, DOMElement $domElement): XmlContainer
    {
        $container = new XmlContainer($domElement->tagName);
        $xpath = new DOMXPath($domDocument);
        $nodes = $xpath->query('namespace::*|attribute::*', $domElement);
        if ($nodes) {
            foreach ($nodes as /** @var DOMNode $node */ $node) {
                if (!$domElement->hasAttribute($node->nodeName)) {
                    continue;
                }
                $container->addAttribute($node->nodeName, $node->nodeValue);
            }
        }
        $content = '';
        foreach ($domElement->childNodes as /** @var DOMNode $child */ $child) {
            if ($child instanceof DOMElement) {
                $container->addChild($this->toContainer($domDocument, $child));
            } elseif ($child instanceof DOMText) {
                if ($child instanceof DOMCdataSection) {
                    $container->setIsContentCdata(true);
                }
                $content .= $child->nodeValue;
            } elseif ($child instanceof DOMEntityReference) {
                throw new XmlParseException('Xml should be without entity ref nodes');
            } else {
                $this->logger->warning(
                    "Unknown element child node type {$child->nodeType}, skip it",
                    [
                        'xml' => $domDocument->saveXML(),
                    ]
                );
            }
        }
        $container->setContent($content);

        return $container;
    }

    /**
     * @throws XmlException
     */
    public function generate(XmlDocument $document): string
    {
        try {
            $domDocument = new DOMDocument($document->getVersion(), $document->getEncoding());
            if ($document->getRoot()) {
                $domElement = $this->toDomElement($domDocument, $document->getRoot());
                $domDocument->appendChild($domElement);
            }

            return $domDocument->saveXML();
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DOMException $exception) {
            throw new XmlValidateException("Can't generate xml", 0, $exception);
        }
    }

    protected function toDomElement(DOMDocument $domDocument, XmlContainer $container): DOMElement
    {
        $domElement = $domDocument->createElement($container->getName());
        if ($container->getContent()) {
            if ($container->isContentCdata()) {
                $domContent = $domDocument->createCDATASection($container->getContent());
            } else {
                $domContent = $domDocument->createTextNode($container->getContent());
            }
            $domElement->appendChild($domContent);
        }
        foreach ($container->getAttributes() as $name => $value) {
            $domElement->setAttribute($name, $value);
        }
        foreach ($container->getChildren() as $child) {
            $domElement->appendChild($this->toDomElement($domDocument, $child));
        }

        return $domElement;
    }
}
