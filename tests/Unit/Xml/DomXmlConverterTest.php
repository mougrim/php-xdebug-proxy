<?php

declare(strict_types=1);

namespace Tests\Mougrim\XdebugProxy\Unit\Xml;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Mougrim\XdebugProxy\Xml\DomXmlConverter;
use Mougrim\XdebugProxy\Xml\XmlContainer;
use Mougrim\XdebugProxy\Xml\XmlDocument;
use Mougrim\XdebugProxy\Xml\XmlParseException;
use Mougrim\XdebugProxy\Xml\XmlValidateException;
use Psr\Log\LoggerInterface;
use Tests\Mougrim\XdebugProxy\TestCase;
use const ENT_QUOTES;
use const ENT_XML1;
use function htmlspecialchars;

/**
 * @author Mougrim <rinat@mougrim.ru>
 */
class DomXmlConverterTest extends TestCase
{
    public function testParseGenerate(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<root><child id="1">Child1</child><child id="2">Child2<sub-child>SubChild1</sub-child><sub-child>SubChild2</sub-child></child></root>
';
        $converter = new DomXmlConverter($this->createFakeLogger());
        $document = $converter->parse($xml);

        static::assertSame('1.0', $document->getVersion());
        static::assertSame('UTF-8', $document->getEncoding());
        $root = $document->getRoot();
        static::assertNotEmpty($root);
        static::assertSame('root', $root->getName());
        static::assertFalse($root->isContentCdata(), "Content shouldn't be cdata");
        static::assertEmpty($root->getContent(), "Content: {$root->getContent()}");
        static::assertEmpty($root->getAttributes());
        static::assertCount(2, $root->getChildren());
        static::assertSame('child', $root->getChildren()[0]->getName());
        static::assertFalse($root->getChildren()[0]->isContentCdata(), "Content shouldn't be cdata");
        static::assertSame('Child1', $root->getChildren()[0]->getContent());
        static::assertSame(['id' => '1'], $root->getChildren()[0]->getAttributes());
        static::assertEmpty($root->getChildren()[0]->getChildren());
        static::assertSame('child', $root->getChildren()[1]->getName());
        static::assertFalse($root->getChildren()[1]->isContentCdata(), "Content shouldn't be cdata");
        static::assertSame('Child2', $root->getChildren()[1]->getContent());
        static::assertSame(['id' => '2'], $root->getChildren()[1]->getAttributes());
        $subChildren = $root->getChildren()[1]->getChildren();
        static::assertCount(2, $subChildren);
        static::assertSame('sub-child', $subChildren[0]->getName());
        static::assertFalse($subChildren[0]->isContentCdata(), "Content shouldn't be cdata");
        static::assertSame('SubChild1', $subChildren[0]->getContent());
        static::assertEmpty($subChildren[0]->getAttributes());
        static::assertEmpty($subChildren[0]->getChildren());
        static::assertSame('sub-child', $subChildren[1]->getName());
        static::assertFalse($subChildren[1]->isContentCdata(), "Content shouldn't be cdata");
        static::assertSame('SubChild2', $subChildren[1]->getContent());
        static::assertEmpty($subChildren[1]->getAttributes());
        static::assertEmpty($subChildren[1]->getChildren());
        static::assertSame(
            [
                'version' => '1.0',
                'encoding' => 'UTF-8',
                'root' => [
                    'name' => 'root',
                    'attributes' => [],
                    'content' => '',
                    'isContentCdata' => false,
                    'children' => [
                        [
                            'name' => 'child',
                            'attributes' => [
                                'id' => '1',
                            ],
                            'content' => 'Child1',
                            'isContentCdata' => false,
                            'children' => [],
                        ],
                        [
                            'name' => 'child',
                            'attributes' => [
                                'id' => '2',
                            ],
                            'content' => 'Child2',
                            'isContentCdata' => false,
                            'children' => [
                                [
                                    'name' => 'sub-child',
                                    'attributes' => [],
                                    'content' => 'SubChild1',
                                    'isContentCdata' => false,
                                    'children' => [],
                                ],
                                [
                                    'name' => 'sub-child',
                                    'attributes' => [],
                                    'content' => 'SubChild2',
                                    'isContentCdata' => false,
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $document->toArray()
        );

        static::assertSame($xml, $converter->generate($document));
    }

    public function testParseGenerateEscaping(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<root attribute="&quot;&gt;xss&lt;/root&gt;&lt;root&gt;">&lt;/root&gt;&lt;root&gt;xss</root>
';
        $converter = new DomXmlConverter($this->createFakeLogger());
        $document = $converter->parse($xml);
        static::assertSame('1.0', $document->getVersion());
        static::assertSame('UTF-8', $document->getEncoding());
        $root = $document->getRoot();
        static::assertSame('root', $root->getName());
        static::assertFalse($root->isContentCdata(), "Content shouldn't be cdata");
        static::assertSame('</root><root>xss', $root->getContent());
        static::assertSame(['attribute' => '">xss</root><root>'], $root->getAttributes());
        static::assertEmpty($root->getChildren());
        static::assertSame(
            [
                'version' => '1.0',
                'encoding' => 'UTF-8',
                'root' => [
                    'name' => 'root',
                    'attributes' => [
                        'attribute' => '">xss</root><root>',
                    ],
                    'content' => '</root><root>xss',
                    'isContentCdata' => false,
                    'children' => [],
                ],
            ],
            $document->toArray()
        );
        static::assertSame($xml, $converter->generate($document));
    }

    public function testWrongNameDecoding(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<&lt;rootattribute=&quot;value1&quot;&gt attribute="value2">Content</&lt;rootattribute=&quot;value1&quot;&gt>
';
        $converter = new DomXmlConverter($this->createFakeLogger());
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("Can't parse xml");
        $converter->parse($xml);
    }

    public function testParseEmptyMessage(): void
    {
        $converter = new DomXmlConverter($this->createFakeLogger());
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("Can't parse xml");
        $converter->parse('');
    }

    public function testNameValidate(): void
    {
        $root = new XmlContainer(htmlspecialchars('<rootattribute="value">', ENT_XML1 | ENT_QUOTES));
        $document = (new XmlDocument('1.0', 'UTF-8'))
            ->setRoot($root);
        $converter = new DomXmlConverter($this->createFakeLogger());
        $this->expectException(XmlValidateException::class);
        $this->expectExceptionMessage("Can't generate xml");
        $converter->generate($document);
    }

    public function testAttributeNameValidate(): void
    {
        $root = (new XmlContainer('root'))
            ->addAttribute(
                htmlspecialchars('>XSS</root><root attribute="', ENT_XML1 | ENT_QUOTES),
                'value'
            );
        $document = (new XmlDocument('1.0', 'UTF-8'))
            ->setRoot($root);
        $converter = new DomXmlConverter($this->createFakeLogger());
        $this->expectException(XmlValidateException::class);
        $this->expectExceptionMessage("Can't generate xml");
        $converter->generate($document);
    }

    /**
     * @see https://www.owasp.org/index.php/XML_External_Entity_(XXE)_Processing
     */
    public function testXxe(): void
    {
        /** @noinspection CheckDtdRefs */
        $xml = '<?xml version="1.0"?>
<!DOCTYPE results [
    <!ENTITY harmless SYSTEM "file:///etc/passwd">
]>
<results><result>&harmless;</result></results>
';

        $converter = new DomXmlConverter($this->createFakeLogger());
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessageMatches(
            '/^(?:Xml should be without entity ref nodes|Too many child nodes in document)$/'
        );
        $converter->parse($xml);
    }

    public function testXdebugInit(): void
    {
        $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
<init xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" fileuri="file:///path/to/file.php" language="PHP" xdebug:language_version="7.1.15-1+os1.02.3+some.site.org+1" protocol_version="1.0" appid="15603" idekey="idekey"><engine version="2.6.0"><![CDATA[Xdebug]]></engine><author><![CDATA[Derick Rethans]]></author><url><![CDATA[http://xdebug.org]]></url><copyright><![CDATA[Copyright (c) 2002-2018 by Derick Rethans]]></copyright></init>
';

        $converter = new DomXmlConverter($this->createFakeLogger());
        $document = $converter->parse($xml);

        static::assertSame('1.0', $document->getVersion());
        static::assertSame('iso-8859-1', $document->getEncoding());
        $root = $document->getRoot();

        static::assertNotEmpty($root);
        static::assertSame('init', $root->getName());
        static::assertFalse($root->isContentCdata(), "Content shouldn't be cdata");
        static::assertEmpty($root->getContent(), "Content: {$root->getContent()}");
        static::assertSame(
            [
                'xmlns:xdebug' => 'http://xdebug.org/dbgp/xdebug',
                'xmlns' => 'urn:debugger_protocol_v1',
                'fileuri' => 'file:///path/to/file.php',
                'language' => 'PHP',
                'xdebug:language_version' => '7.1.15-1+os1.02.3+some.site.org+1',
                'protocol_version' => '1.0',
                'appid' => '15603',
                'idekey' => 'idekey',
            ],
            $root->getAttributes()
        );
        static::assertCount(4, $root->getChildren());

        $children = $root->getChildren();

        static::assertSame('engine', $children[0]->getName());
        static::assertTrue($children[0]->isContentCdata(), 'Content should be cdata');
        static::assertSame('Xdebug', $children[0]->getContent());
        static::assertSame(
            [
                'version' => '2.6.0',
            ],
            $children[0]->getAttributes()
        );
        static::assertCount(0, $children[0]->getChildren());

        static::assertSame('author', $children[1]->getName());
        static::assertTrue($children[1]->isContentCdata(), 'Content should be cdata');
        static::assertSame('Derick Rethans', $children[1]->getContent());
        static::assertEmpty($children[1]->getAttributes());
        static::assertCount(0, $children[1]->getChildren());

        static::assertSame('url', $children[2]->getName());
        static::assertTrue($children[2]->isContentCdata(), 'Content should be cdata');
        static::assertSame('http://xdebug.org', $children[2]->getContent());
        static::assertEmpty($children[2]->getAttributes());
        static::assertCount(0, $children[2]->getChildren());

        static::assertSame('copyright', $children[3]->getName());
        static::assertTrue($children[3]->isContentCdata(), 'Content should be cdata');
        static::assertSame('Copyright (c) 2002-2018 by Derick Rethans', $children[3]->getContent());
        static::assertEmpty($children[3]->getAttributes());
        static::assertCount(0, $children[3]->getChildren());

        static::assertSame(
            [
                'version' => '1.0',
                'encoding' => 'iso-8859-1',
                'root' => [
                    'name' => 'init',
                    'attributes' => [
                        'xmlns:xdebug' => 'http://xdebug.org/dbgp/xdebug',
                        'xmlns' => 'urn:debugger_protocol_v1',
                        'fileuri' => 'file:///path/to/file.php',
                        'language' => 'PHP',
                        'xdebug:language_version' => '7.1.15-1+os1.02.3+some.site.org+1',
                        'protocol_version' => '1.0',
                        'appid' => '15603',
                        'idekey' => 'idekey',
                    ],
                    'content' => '',
                    'isContentCdata' => false,
                    'children' => [
                        [
                            'name' => 'engine',
                            'attributes' => [
                                'version' => '2.6.0',
                            ],
                            'content' => 'Xdebug',
                            'isContentCdata' => true,
                            'children' => [],
                        ],
                        [
                            'name' => 'author',
                            'attributes' => [],
                            'content' => 'Derick Rethans',
                            'isContentCdata' => true,
                            'children' => [],
                        ],
                        [
                            'name' => 'url',
                            'attributes' => [],
                            'content' => 'http://xdebug.org',
                            'isContentCdata' => true,
                            'children' => [],
                        ],
                        [
                            'name' => 'copyright',
                            'attributes' => [],
                            'content' => 'Copyright (c) 2002-2018 by Derick Rethans',
                            'isContentCdata' => true,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            $document->toArray()
        );

        static::assertSame($xml, $converter->generate($document));
    }

    public function testXdebugMessage(): void
    {
        $xml = '<?xml version="1.0" encoding="iso-8859-1"?>
<response xmlns="urn:debugger_protocol_v1" xmlns:xdebug="http://xdebug.org/dbgp/xdebug" command="step_into" transaction_id="8" status="break" reason="ok"><xdebug:message filename="file:///home/mougrim/Private/development/mougrim/php-xdebug-proxy/bin/test.php" lineno="11"></xdebug:message></response>';

        $converter = new DomXmlConverter($this->createFakeLogger());
        $document = $converter->parse($xml);

        static::assertSame('1.0', $document->getVersion());
        static::assertSame('iso-8859-1', $document->getEncoding());
        $root = $document->getRoot();

        static::assertNotEmpty($root);
        static::assertSame('response', $root->getName());
        static::assertFalse($root->isContentCdata(), "Content shouldn't be cdata");
        static::assertEmpty($root->getContent(), "Content: {$root->getContent()}");
        static::assertSame(
            [
                'xmlns:xdebug' => 'http://xdebug.org/dbgp/xdebug',
                'xmlns' => 'urn:debugger_protocol_v1',
                'command' => 'step_into',
                'transaction_id' => '8',
                'status' => 'break',
                'reason' => 'ok',
            ],
            $root->getAttributes()
        );
        static::assertCount(1, $root->getChildren());

        $children = $root->getChildren();

        static::assertSame('xdebug:message', $children[0]->getName());
        static::assertFalse($children[0]->isContentCdata(), 'Content shouldn\'t be cdata');
        static::assertEmpty($children[0]->getContent(), "Content: {$children[0]->getContent()}");
        static::assertSame(
            [
                'filename' => 'file:///home/mougrim/Private/development/mougrim/php-xdebug-proxy/bin/test.php',
                'lineno' => '11',
            ],
            $children[0]->getAttributes()
        );
        static::assertCount(0, $children[0]->getChildren());

        static::assertSame(
            [
                'version' => '1.0',
                'encoding' => 'iso-8859-1',
                'root' => [
                    'name' => 'response',
                    'attributes' => [
                        'xmlns:xdebug' => 'http://xdebug.org/dbgp/xdebug',
                        'xmlns' => 'urn:debugger_protocol_v1',
                        'command' => 'step_into',
                        'transaction_id' => '8',
                        'status' => 'break',
                        'reason' => 'ok',
                    ],
                    'content' => '',
                    'isContentCdata' => false,
                    'children' => [
                        [
                            'name' => 'xdebug:message',
                            'attributes' => [
                                'filename' => 'file:///home/mougrim/Private/development/mougrim/php-xdebug-proxy/bin/test.php',
                                'lineno' => '11',
                            ],
                            'content' => '',
                            'isContentCdata' => false,
                            'children' => [],
                        ],
                    ],
                ],
            ],
            $document->toArray()
        );
    }

    protected function createFakeLogger(): LoggerInterface
    {
        return (new Logger('fake'))
            ->pushHandler(new NullHandler());
    }
}
