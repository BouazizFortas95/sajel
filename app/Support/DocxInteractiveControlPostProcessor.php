<?php

namespace App\Support;

use ZipArchive;

class DocxInteractiveControlPostProcessor
{
    /**
     * Post-process DOCX file to inject native interactive Word Content Controls
     * for checkboxes (w14:checkbox) and editable date pickers (w:date),
     * and upgrade settings.xml to modern Word compatibility mode (w:val="15").
     */
    public static function process(string $filePath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if (! $xml) {
            $zip->close();

            return;
        }

        if (! str_contains($xml, 'xmlns:w14=')) {
            $xml = str_replace(
                'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"',
                'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"',
                $xml
            );
        }

        // Replace checkboxes: [CHECKBOX:true:id] or [CHECKBOX:false:id]
        $xml = preg_replace_callback('/\[CHECKBOX:(true|false):([^\]]+)\]/', function ($matches) {
            $checked = $matches[1] === 'true';
            $tag = htmlspecialchars($matches[2], ENT_XML1);
            $val = $checked ? '1' : '0';
            $sym = $checked ? '&#9746;' : '&#9744;';
            $font = 'MS Gothic';

            return '<w:sdt><w:sdtPr>'
                .'<w:alias w:val="'.$tag.'"/>'
                .'<w:tag w:val="'.$tag.'"/>'
                .'<w14:checkbox>'
                .'<w14:checked w14:val="'.$val.'"/>'
                .'<w14:checkedState w14:val="2612" w14:font="'.$font.'"/>'
                .'<w14:uncheckedState w14:val="2610" w14:font="'.$font.'"/>'
                .'</w14:checkbox>'
                .'</w:sdtPr><w:sdtContent>'
                .'<w:r><w:rPr><w:rFonts w:ascii="'.$font.'" w:hAnsi="'.$font.'"/><w:b/><w:sz w:val="22"/><w:color w:val="'.($checked ? '15803D' : '64748B').'"/></w:rPr>'
                .'<w:t>'.$sym.'</w:t></w:r>'
                .'</w:sdtContent></w:sdt>';
        }, $xml);

        // Replace dates: [DATE:YYYY-MM-DD:id]
        $xml = preg_replace_callback('/\[DATE:([^:]*):([^\]]+)\]/', function ($matches) {
            $date = $matches[1];
            $tag = htmlspecialchars($matches[2], ENT_XML1);
            $displayDate = ! empty($date) ? $date : 'انقر لتحديد التاريخ';
            $isPlaceholder = empty($date);
            $fullDateIso = ! empty($date) ? $date.'T00:00:00Z' : '';

            return '<w:sdt><w:sdtPr>'
                .'<w:alias w:val="'.$tag.'"/>'
                .'<w:tag w:val="'.$tag.'"/>'
                .'<w:date w:fullDate="'.$fullDateIso.'">'
                .'<w:dateFormat w:val="yyyy-MM-dd"/>'
                .'<w:lid w:val="ar-DZ"/>'
                .'<w:storeMappedDataAs w:val="dateTime"/>'
                .'<w:calendar w:val="gregorian"/>'
                .'</w:date>'
                .'</w:sdtPr><w:sdtContent>'
                .'<w:r><w:rPr><w:rFonts w:ascii="Cairo" w:hAnsi="Cairo"/><w:sz w:val="18"/><w:color w:val="'.($isPlaceholder ? '94A3B8' : '1E3A8A').'"/><w:b/></w:rPr>'
                .'<w:t>'.$displayDate.'</w:t></w:r>'
                .'</w:sdtContent></w:sdt>';
        }, $xml);

        $zip->addFromString('word/document.xml', $xml);

        // Upgrade settings.xml
        $settingsXml = $zip->getFromName('word/settings.xml');
        if ($settingsXml) {
            if (str_contains($settingsXml, '<w:compatSetting w:name="compatibilityMode"')) {
                $settingsXml = preg_replace(
                    '/<w:compatSetting w:name="compatibilityMode"[^>]*w:val="[^"]*"[^>]*\/>/',
                    '<w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/>',
                    $settingsXml
                );
            } else {
                $compat = '<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>';
                $settingsXml = str_replace('</w:settings>', $compat.'</w:settings>', $settingsXml);
            }
            $zip->addFromString('word/settings.xml', $settingsXml);
        }

        $zip->close();
    }
}

