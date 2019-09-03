<?php
namespace CfdiUtilsTests\Utils;

use CfdiUtils\Utils\Xml;
use CfdiUtilsTests\TestCase;
use DOMDocument;

class XmlTest extends TestCase
{
    public function testMethodDocumentElementWithoutRootElement()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('DOM Document does not have root element');
        Xml::documentElement(new DOMDocument());
    }

    public function testMethodDocumentElementWithRootElement()
    {
        $document = new DOMDocument();
        $root = $document->createElement('root');
        $document->appendChild($root);
        $this->assertSame($root, Xml::documentElement($document));
    }

    /**
     * @param string $expected
     * @param string $content
     * @testWith ["", ""]
     *           ["foo", "foo"]
     *           ["&amp;", "&"]
     *           ["&lt;", "<"]
     *           ["&gt;", ">"]
     *           ["'", "'"]
     *           ["\"", "\""]
     *           ["&amp;copy;", "&copy;"]
     *           ["foo &amp; bar", "foo & bar"]
     */
    public function testMethodCreateElement(string $expected, string $content)
    {
        $elementName = 'element';
        $document = Xml::newDocument();
        $element = Xml::createElement($document, $elementName, $content);
        $document->appendChild($element);
        $this->assertSame($content, $element->textContent);
        $this->assertXmlStringEqualsXmlString(
            sprintf('<%1$s>%2$s</%1$s>', $elementName, $expected),
            $document->saveXML($element)
        );
    }

    public function testMethodCreateElementWithBadName()
    {
        $document = Xml::newDocument();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create element');
        Xml::createElement($document, '');
    }

    /**
     * @param string $expected
     * @param string $content
     * @testWith ["", ""]
     *           ["foo", "foo"]
     *           ["&amp;", "&"]
     *           ["&lt;", "<"]
     *           ["&gt;", ">"]
     *           ["'", "'"]
     *           ["\"", "\""]
     *           ["&amp;copy;", "&copy;"]
     *           ["foo &amp; bar", "foo & bar"]
     */
    public function testMethodCreateElementNs(string $expected, string $content)
    {
        $prefix = 'foo';
        $namespaceURI = 'http://tempuri.org/';
        $elementName = $prefix . ':element';
        $document = Xml::newDocument();
        $element = Xml::createElementNS($document, $namespaceURI, $elementName, $content);
        $document->appendChild($element);
        $this->assertSame($content, $element->textContent);
        $this->assertXmlStringEqualsXmlString(
            sprintf('<%1$s xmlns:%3$s="%4$s">%2$s</%1$s>', $elementName, $expected, $prefix, $namespaceURI),
            $document->saveXML($element)
        );
    }

    public function testMethodCreateElementNsWithBadName()
    {
        $document = Xml::newDocument();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create element');
        Xml::createElementNS($document, 'http://tempuri.org/', '');
    }

    public function testMethodCreateElementNsWithBadUri()
    {
        $document = Xml::newDocument();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create element');
        Xml::createElementNS($document, '', 'x:foo');
    }
}