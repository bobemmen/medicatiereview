<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class DossierExtractor
{
    public function extractFromUpload(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        return match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx', 'doc' => $this->extractWord($path, $extension),
            'txt', 'md' => (string) file_get_contents($path),
            default => throw new RuntimeException(
                "Bestandstype '{$extension}' wordt niet ondersteund. Gebruik PDF, DOCX, DOC of TXT."
            ),
        };
    }

    private function extractPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);

        return $this->normalise($pdf->getText());
    }

    private function extractWord(string $path, string $extension): string
    {
        $readerType = $extension === 'doc' ? 'MsDoc' : 'Word2007';
        $phpWord = WordIOFactory::load($path, $readerType);

        $text = '';

        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractElements($section->getElements());
        }

        return $this->normalise($text);
    }

    private function extractElements(array $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $value = $element->getText();

                if (is_string($value)) {
                    $text .= $value . "\n";
                }
            }

            if (method_exists($element, 'getElements')) {
                $text .= $this->extractElements($element->getElements());
            }
        }

        return $text;
    }

    private function normalise(string $text): string
    {
        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
