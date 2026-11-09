<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class BlogHtml
{
    public function clean(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p[style],div[style],span[style],h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],strong,b,em,i,u,s,strike,sub,sup,br,hr,blockquote[style],ul,ol,li[style],a[href|title],table[style],thead,tbody,tfoot,tr,th[colspan|rowspan|style],td[colspan|rowspan|style]');
        $config->set('CSS.AllowedProperties', ['text-align', 'color', 'background-color', 'font-size', 'font-family', 'font-weight', 'font-style', 'text-decoration', 'line-height', 'margin-left', 'margin-right', 'width', 'height', 'border', 'border-collapse']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('URI.DisableExternalResources', true);

        return (new HTMLPurifier($config))->purify($html);
    }
}
