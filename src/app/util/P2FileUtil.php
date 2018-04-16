<?php
namespace app\util;

use JBZoo\Utils\Str;
use Slim\Exception\NotFoundException;

class P2FileUtil
{

    public static function getRootFolder($request, $response, string $version): string
    {
        // TODO maybe change root directory        
        $rootFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'data', $version]);
        
        if (! Str::isStart($rootFolder, __DIR__)) {
            throw new NotFoundException($request, $response);
        }
        if (! file_exists($rootFolder)) {
            throw new NotFoundException($request, $response);
        }
        return $rootFolder;
    }

    public static function getFolders($rootFolder): Composite
    {
        $pathNames = glob($rootFolder . DIRECTORY_SEPARATOR . '*');
        $directories = array_filter($pathNames, 'is_dir');
        $directories = array_filter($directories, function ($pathName) {
            return file_exists($pathName . DIRECTORY_SEPARATOR . 'p2.complete');
        });
        
        $timestamps = array_map(function ($filename) {
            return self::getP2Timestamp($filename . DIRECTORY_SEPARATOR . 'artifacts.xml');
        }, $directories);
        
        $timestamp = empty($timestamps) ? 0 : max($timestamps);
        
        $locations = array_map(function ($pathName) {
            return basename($pathName);
        }, $directories);
        
        return new Composite($locations, $timestamp);
    }

    public static function getP2Timestamp(string $filename) : string
    {
        $xml = simplexml_load_file($filename);
        return $xml->xpath("/repository/properties/property[@name='p2.timestamp']/@value")[0];
    }
}

class Composite
{
    public $locations;
    public $timestamp;

    public function __construct(array $locations, int $timestamp)
    {
        $this->locations = $locations;
        $this->timestamp = $timestamp;
    }
}