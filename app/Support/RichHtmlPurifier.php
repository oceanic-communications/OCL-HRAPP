<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

final class RichHtmlPurifier
{
    public static function purify(string $dirty): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set(
            'HTML.Allowed',
            'p,br,strong,b,em,i,u,s,strike,sub,sup,a[href|title|target|rel],ul,ol,li,h2,h3,h4,blockquote,pre,code,'.
            'img[src|alt|width|height|style|class],span[style|class],hr,'.
            'iframe[src|width|height|frameborder|title],'.
            'table[style|class|border|cellpadding|cellspacing],caption[style|class],'.
            'thead[style|class],tbody[style|class],tfoot[style|class],tr[style|class],'.
            'th[colspan|rowspan|style|class|scope],td[colspan|rowspan|style|class]'
        );
        // border-radius is only registered when CSS.Proprietary is enabled.
        $config->set('CSS.Proprietary', true);
        $config->set(
            'CSS.AllowedProperties',
            'max-width,width,height,text-align,float,margin,margin-left,margin-right,margin-top,margin-bottom,'.
            'padding,padding-left,padding-right,padding-top,padding-bottom,'.
            'border,border-radius,border-collapse,vertical-align,background-color,background',
        );
        $config->set('HTML.SafeIframe', true);
        $config->set(
            'URI.SafeIframeRegexp',
            '%^(https?:)?//(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/|player\.vimeo\.com/video/)%'
        );
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.AutoParagraph', false);

        return (new HTMLPurifier($config))->purify($dirty);
    }
}
