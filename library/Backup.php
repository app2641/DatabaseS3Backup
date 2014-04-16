<?php


use Aws\S3\S3Client;
use Aws\Common\Enum\Region;
use Guzzle\Http\EntityBody;

class Backup
{

    /**
     * S3Clientクラス
     *
     * @author app2641
     **/
    protected $client;



    /**
     * バックアップデータを保存するバケット名
     *
     * @author app2641
     **/
    protected $bucket;



    /**
     * aws.iniパス
     *
     * @author app2641
     **/
    protected $aws_ini_path = 'configs/aws.ini';



    /**
     * databases.iniパス
     *
     * @author app2641
     **/
    protected $databases_ini_path = 'configs/databases.ini';



    public function __construct ()
    {
        // AWSクライアントの設定
        $ini = parse_ini_file(ROOT.'/'.$this->aws_ini_path);
        $this->bucket = $ini['bucket'];

        $this->client = S3Client::factory(
            array(
                'key' => $ini['key'],
                'secret' => $ini['secret'],
                'region' => Region::AP_NORTHEAST_1
            )
        );
    }



    public function execute ()
    {
        try {
            $ini = parse_ini_file(ROOT.'/'.$this->databases_ini_path);
            $user = $ini['user'];
            $pass = $ini['password'];

            $data_dir = ROOT.'/data';
            if (! is_dir($data_dir)) {
                mkdir($data_dir);
                chmod($data_dir, 0777);
            }


            // データベースをバックアップしていく
            foreach ($ini['databases'] as $name) {
                $file_name = $name.'-'.date('Y-m-d');
                $file_path = $data_dir.'/'.$file_name.'.gz';

                $command = sprintf(
                    'mysqldump --skip-lock-tables -u %s --password="%s" %s | gzip -9 > %s',
                    $user,
                    $pass,
                    $name,
                    $file_path
                );
                exec($command);


                // S3アップロード
                if (file_exists($file_path)) {
                    try {
                        $this->client->putObject(
                            array(
                                'Bucket' => $this->bucket,
                                'Key' => 'Backup/'.$name.'/'.$file_name.'.gz',
                                'Body' => EntityBody::factory(fopen($file_path, 'r'))
                            )
                        );

                        $this->client->putObject(
                            array(
                                'Bucket' => $this->bucket.'-glacier',
                                'Key' => 'Backup/'.$name.'/'.$file_name.'.gz',
                                'Body' => EntityBody::factory(fopen($file_path, 'r'))
                            )
                        );

                        unlink($file_path);

                    } catch (\Exception $e) {
                        echo 'S3アップロードに失敗しました'.PHP_EOL;
                        throw $e;
                    }
                }
            }
        
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
