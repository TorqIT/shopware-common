<?php

namespace Torq\Shopware\Common\Utilities;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class DownloadFile
{
    public static function downloadAsExcel(array $headers, array $data, string $filename): Response {
        $writer = WriterEntityFactory::createXLSXWriter();
        $tmpXLSX = tempnam(sys_get_temp_dir(), '_export_');
        $writer->openToFile($tmpXLSX);
        $writer->addRow(WriterEntityFactory::createRowFromArray($headers));
        
        foreach ($data as $row) {
            $writer->addRow(WriterEntityFactory::createRowFromArray($row));
        }

        $writer->close();       

        $content = file_get_contents($tmpXLSX); //fopen($tmpXLSX ?: '', 'w+');
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $response = new Response($content ?: null, 200, ['Content-Type' => $contentType,
                                                        'Content-Disposition' => 'filename=' . $filename]);
        $response->setLastModified((new \DateTimeImmutable()));

        unlink($tmpXLSX);

        return $response;
    }

    public static function downloadAsPdf($response, $domRef = null, $filename = 'download'): Response {
        if($domRef == null)
            $html = $response->getContent();
        else
            $html = (new Crawler($response->getContent()))->filter($domRef)->first()->html();
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);                      
        
        $dompdf = new Dompdf($options);
        //$a4 = [0, 0, 595.28, 841.89];
        //$dompdf->setPaper($a4, 'portrait');
        
        $dompdf->loadHtml($html);
        $dompdf->render();            

        $content = $dompdf->output();
        $contentType = 'application/pdf';
        $response = new Response($content ?: null, 200, ['Content-Type' => $contentType,
                                                        'Content-Disposition' => 'filename='. $filename . '.pdf']);
        $response->setLastModified((new \DateTimeImmutable()));
        return $response;
    }
}
