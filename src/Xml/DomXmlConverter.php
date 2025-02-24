<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

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

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DomXmlConverter implements XmlConverter
{
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {
    }

    protected function getError(int $type): string
    {
        return match ($type) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => "[{$type}] Unknown",
        };
    }

    /**
     * @throws XmlException
     */
    public function parse(string $xml): XmlDocument
    {
        if (!$xml) {
            throw new XmlParseException("Can't parse xml: xml must not be empty");
        }
        $domDocument = new DOMDocument();
        $result = @$domDocument->loadXML($xml, LIBXML_NONET);
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
        if (count($domDocument->childNodes) > 1) {
            throw new XmlParseException('Too many child nodes in document');
        }

        $root = null;
        if ($domDocument->documentElement) {
            $root = $this->toContainer($domDocument, $domDocument->documentElement);
        }

        return new XmlDocument($domDocument->xmlVersion ?? '', $domDocument->xmlEncoding, $root);
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
                $container->addAttribute($node->nodeName, $node->nodeValue ?? '');
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
                $content .= ($child->nodeValue ?? '');
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
            $domDocument = new DOMDocument($document->getVersion(), $document->getEncoding() ?? '');
            $xmlContainer = $document->getRoot();
            if ($xmlContainer) {
                $domElement = $this->toDomElement($domDocument, $xmlContainer);
                $domDocument->appendChild($domElement);
            }

            $xml = @$domDocument->saveXML();

            if ($xml === false) {
                $error = error_get_last();
                error_clear_last();
                $message = '';
                if ($error) {
                    $message = ": [{$this->getError($error['type'])}] {$error['message']} in {$error['file']}:{$error['line']}";
                }

                throw new XmlValidateException("Can't generate xml{$message}");
            }

            return $xml;
        } catch (DOMException $exception) {
            throw new XmlValidateException("Can't generate xml", 0, $exception);
        }
    }

    /**
     * @throws DOMException
     */
    protected function toDomElement(DOMDocument $domDocument, XmlContainer $container): DOMElement
    {
        $domElement = $domDocument->createElement($container->getName());
        if ($container->getContent()) {
            if ($container->isContentCdata()) {
                $domContent = $domDocument->createCDATASection($container->getContent());
            } else {
                $domContent = $domDocument->createTextNode($container->getContent());
            }
            if ($domContent) {
                $domElement->appendChild($domContent);
            }
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
