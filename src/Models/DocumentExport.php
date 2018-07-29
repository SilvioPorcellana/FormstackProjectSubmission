<?php

namespace Models;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

/**
 * This is the main class for the Document model.
 *
 * @property \PDO $pdo;
 * @property string $db_table
 *
 * @property string $key
 * @property string $value
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $exported_at
 */


class DocumentExport
{
    /**
     * Document constructor.
     *
     * @param $key
     * @param $value
     */
    public function __construct(array $params = [])
    {
        return $this;
    }

    
    public static function export($document_id, $format = 'csv', $sendheaders = true, $download = true, $filename = 'documents.csv')
    {
        $return = false;
        $document = Document::find($document_id);

        if (! $document)
        {
            throw new \BadMethodCallException('document with id ' . $document_id . ' not found');
        }

        if ($sendheaders)
        {
            self::_sendHeaders($format, $download, $filename);
        }

        if ($format == 'csv' || empty($format))
        {
            $return = 'Document created at: ' . \Models\Document::formatDate($document->created_at) . "\n";
            if ($document->updated_at > 0)
            {
                $return .= 'Document updated at: ' . \Models\Document::formatDate($document->updated_at) . "\n";
            }
            $return .= "\n";
            $return .= 'key,value' . "\n";
            foreach ($document->rows as $row)
            {
                $return .= $row->key . ',' . $row->value . "\n";
            }
        }

        $document->update(['exported_at' => time()]);
        return $return;
    }
    


    private static function _sendHeaders($format = 'csv', $download = true, $filename = 'documents.csv')
    {
        if ($format == 'csv' || empty($format))
        {
            // disable caching
            $now = gmdate("D, d M Y H:i:s");
            header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
            header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
            header("Last-Modified: {$now} GMT");

            if ($download)
            {
                // force download
                header("Content-Type: application/force-download");
                header("Content-Type: application/octet-stream");
                header("Content-Type: application/download");

                // disposition / encoding on response body
                header("Content-Disposition: attachment;filename=" . $filename);
                header("Content-Transfer-Encoding: binary");
            }
        }
    }




    public static function exportTo($document_id, $destination = 's3', $format = 'csv', $filename = 'documents.csv')
    {
        $data = self::export($document_id, $format, false);

        if ($destination == 's3' || empty($destination))
        {
            $_config_file_path = $_SERVER['DOCUMENT_ROOT'] . '/../_config.ini';
            $config = parse_ini_file($_config_file_path);

            $bucket = $config['s3_bucket'];
            $key = $config['s3_key'];
            $secret = $config['s3_secret'];

            $s3 = new S3Client([
                'version'   => 'latest',
                'region'    => 'us-east-1',
                'credentials' => [
                    'key'       => $key,
                    'secret'    => $secret,
                ],
            ]);

            try
            {
                // Upload data.
                $result = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $filename,
                    'Body'   => $data,
                    'ACL'    => 'public-read',
                ]);

                // Print the URL to the object.
                return $result['ObjectURL'] . PHP_EOL;
            }
            catch (S3Exception $e)
            {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

}