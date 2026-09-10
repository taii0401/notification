<?php

namespace App\Services\Templates;

class TemplateRenderer
{
    public function render(
        string $template,
        array $data
    ): string {
        foreach ($data as $key => $value) {
            $template = str_replace(
                '{{' . $key . '}}',
                (string) $value,
                $template
            );
        }

        return $template;
    }
}