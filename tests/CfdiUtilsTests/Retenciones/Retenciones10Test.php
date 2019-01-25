<?php
namespace CfdiUtilsTests\Retenciones;

use CfdiUtils\CadenaOrigen\DOMBuilder;
use CfdiUtils\Certificado\Certificado;
use CfdiUtils\Nodes\Node;
use CfdiUtils\Retenciones\RetencionesCreator10;
use CfdiUtilsTests\TestCase;

class Retenciones10Test extends TestCase
{
    public function testCreatePreCfdiWithAllCorrectValues()
    {
        $cerFile = $this->utilAsset('certs/CSD01_AAA010101AAA.cer');
        $pemFile = $this->utilAsset('certs/CSD01_AAA010101AAA.key.pem');
        $passPhrase = '';
        $certificado = new Certificado($cerFile);
        $xmlResolver = $this->newResolver();
        $xsltBuilder = new DOMBuilder();

        // create object
        $creator = new RetencionesCreator10([
            'FechaExp' => '2019-01-23T08:00:00-06:00',
            'CveRetenc' => '14', // Dividendos o utilidades distribuidos
        ], $xmlResolver, $xsltBuilder);
        $retenciones = $creator->retenciones();
        $retenciones->addEmisor([
            'RFCEmisor' => 'AAA010101AAA',
            'NomDenRazSocE' => 'ACCEM SERVICIOS EMPRESARIALES SC',
        ]);
        $retenciones->getReceptor()->addExtranjero([
            'NumRegIdTrib' => '998877665544332211',
            'NomDenRazSocR' => 'WORLD WIDE COMPANY INC',
        ]);
        $retenciones->addPeriodo(['MesIni' => '5', 'MesFin' => '5', 'Ejerc' => '2018']);
        $retenciones->addTotales([
            'montoTotOperacion' => '55578643',
            'montoTotGrav' => '0',
            'montoTotExent' => '55578643',
            'montoTotRet' => '0',
        ]);
        $retenciones->addImpRetenidos([
            'BaseRet' => '0',
            'Impuesto' => '01', // 01 - ISR
            'montoRet' => '0',
            'TipoPagoRet' => 'Pago provisional',
        ]);

        $retenciones->addComplemento(
            new Node('dividendos:Dividendos', [
                'xmlns:dividendos' => 'http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos',
                'xsi:schemaLocation' => vsprintf('%s %s', [
                    'http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos',
                    'http://www.sat.gob.mx/esquemas/retencionpago/1/dividendos/dividendos.xsd',
                ]),
                'Version' => '1.0',
            ], [
                new Node('dividendos:DividOUtil', [
                    'CveTipDivOUtil' => '06', // 06 - Proviene de CUFIN al 31 de diciembre 2013
                    'MontISRAcredRetMexico' => '0',
                    'MontISRAcredRetExtranjero' => '0',
                    'MontRetExtDivExt' => '0',
                    'TipoSocDistrDiv' => 'Sociedad Nacional',
                    'MontISRAcredNal' => '0',
                    'MontDivAcumNal' => '0',
                    'MontDivAcumExt' => '0',
                ]),
            ])
        );

        // verify properties
        $this->assertSame($xmlResolver, $creator->getXmlResolver());
        $this->assertSame($xsltBuilder, $creator->getXsltBuilder());

        // verify root node
        $root = $creator->retenciones();
        $this->assertSame(RetencionesCreator10::XMLNS_1_0, $root['xmlns:retenciones']);
        $this->assertSame(RetencionesCreator10::XMLNS_1_0 . ' ' . RetencionesCreator10::XSD_1_0, $root['xsi:schemaLocation']);
        $this->assertSame('1.0', $root['Version']);

        // put additional content using helpers
        $creator->putCertificado($certificado);
        $creator->addSello('file://' . $pemFile, $passPhrase);

        // validate
        $asserts = $creator->validate();
        $this->assertGreaterThanOrEqual(1, $asserts->count());
        $this->assertTrue($asserts->exists('XSD01'));
        $this->assertSame('', $asserts->get('XSD01')->getExplanation());
        $this->assertFalse($asserts->hasErrors());

        // check against known content
        $this->assertXmlStringEqualsXmlFile($this->utilAsset('retenciones/sample-before-tfd.xml'), $creator->asXml());
    }

    public function testValidateIsCheckingAgainstXsdViolations()
    {
        $retencion = new RetencionesCreator10();
        $retencion->setXmlResolver($this->newResolver());
        $assert = $retencion->validate()->get('XSD01');
        $this->assertTrue($assert->getStatus()->isError());
    }

    public function testAddSelloFailsWithWrongPassPrase()
    {
        $pemFile = $this->utilAsset('certs/CSD01_AAA010101AAA_password.key.pem');
        $passPhrase = '_worng_passphrase_';

        $retencion = new RetencionesCreator10();
        $retencion->setXmlResolver($this->newResolver());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot open the private key');
        $retencion->addSello('file://' . $pemFile, $passPhrase);
    }

    public function testAddSelloFailsWithWrongCertificado()
    {
        $cerFile = $this->utilAsset('certs/CSD09_AAA010101AAA.cer');
        $pemFile = $this->utilAsset('certs/CSD01_AAA010101AAA.key.pem');
        $passPhrase = '';
        $certificado = new Certificado($cerFile);

        $retencion = new RetencionesCreator10();
        $retencion->setXmlResolver($this->newResolver());

        $retencion->putCertificado($certificado);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The private key does not belong to the current certificate');
        $retencion->addSello('file://' . $pemFile, $passPhrase);
    }
}
