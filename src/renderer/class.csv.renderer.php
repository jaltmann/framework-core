<?php
namespace renderer;

class CsvRenderer extends AbstractRenderer
{
  private $filename = false;
    public function __construct()
    {
        $this->filename = 'export-'.date('YmdHi').'.csv';

        header('Content-Type: text/csv');
        header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");

        header('Cache-Control: public');
    }

    public function render($args, $vars)
    {
        $lines = $vars['messages'][0]['msg'];

        $fp = fopen('php://output', 'w');
        foreach ($lines as $elements) {
          fputcsv($fp, $elements, ';', '"');
        }
        readfile($fp);
        fclose($fp);

        header('Content-Disposition: attachment; filename='.$this->filename);
        header('Content-Length:'.filesize($fp));

        return;
    }
}
