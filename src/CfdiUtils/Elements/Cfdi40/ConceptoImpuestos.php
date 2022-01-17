<?php

namespace CfdiUtils\Elements\Cfdi40;

class ConceptoImpuestos extends Impuestos
{
    public function getChildrenOrder(): array
    {
        return [
            'cfdi:Traslados',
            'cfdi:Retenciones',
        ];
    }
}
