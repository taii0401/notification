<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Templates\TemplateRenderer;

class TemplateRendererTest extends TestCase
{
    public function test_template_variables_can_be_rendered(): void
    {
        $renderer = new TemplateRenderer();

        $result = $renderer->render(
            '您好 {{customer_name}}，訂單 {{order_no}} 已付款。',
            [
                'customer_name' => '王小明',
                'order_no' => 'ORD-001',
            ]
        );

        $this->assertSame(
            '您好 王小明，訂單 ORD-001 已付款。',
            $result
        );
    }

    public function test_multiple_variables_can_be_rendered(): void
    {
        $renderer = new TemplateRenderer();

        $result = $renderer->render(
            '{{order_no}} - {{amount}} 元',
            [
                'order_no' => 'ORD-001',
                'amount' => 1280,
            ]
        );

        $this->assertSame(
            'ORD-001 - 1280 元',
            $result
        );
    }
}