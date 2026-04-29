<?php

namespace Tests\Feature\Mail;

use Tests\TestCase;
use Tests\Support\MailTemplateCatalogBuilder;

class MailTemplateCatalogTest extends TestCase
{
    public function test_it_generates_a_catalog_with_all_current_email_templates(): void
    {
        $builder = new MailTemplateCatalogBuilder();
        $outputPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'mail-template-catalog.html';
        $catalog = $builder->generate($outputPath);

        $expectedClasses = $builder->supportedTemplateClasses();
        sort($expectedClasses);

        $discoveredClasses = array_merge(
            $catalog['discovered']['mailables'],
            $catalog['discovered']['notifications']
        );
        sort($discoveredClasses);

        $this->assertSame($expectedClasses, $discoveredClasses);
        $this->assertSame(27, $catalog['summary']['total_templates']);
        $this->assertSame(27, $catalog['summary']['rendered']);
        $this->assertSame(0, $catalog['summary']['failed']);
        $this->assertFileExists($outputPath);
        $this->assertStringContainsString('Catalogo actual de plantillas de email de KANTRACK', file_get_contents($outputPath));
    }
}
