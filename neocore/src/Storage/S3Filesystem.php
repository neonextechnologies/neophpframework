<?php

declare(strict_types=1);

namespace NeoCore\Storage;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Filesystem implements FilesystemInterface
{
    protected S3Client $client;
    protected string $bucket;
    protected array $config;

    public function __construct(S3Client $client, string $bucket, array $config = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->config = $config;
    }

    public function exists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }

    public function get(string $path): ?string
    {
        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (string) $result['Body'];
        } catch (AwsException $e) {
            return null;
        }
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        try {
            $params = [
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
            ];

            // Set ACL for visibility
            if (isset($options['visibility'])) {
                $params['ACL'] = $options['visibility'] === 'public' ? 'public-read' : 'private';
            }

            // Set content type
            if (isset($options['mimetype'])) {
                $params['ContentType'] = $options['mimetype'];
            }

            // Set metadata
            if (isset($options['metadata'])) {
                $params['Metadata'] = $options['metadata'];
            }

            $this->client->putObject($params);

            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function prepend(string $path, string $data): bool
    {
        $existing = $this->get($path) ?? '';
        return $this->put($path, $data . $existing);
    }

    public function append(string $path, string $data): bool
    {
        $existing = $this->get($path) ?? '';
        return $this->put($path, $existing . $data);
    }

    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];

        try {
            $objects = array_map(fn($path) => ['Key' => $path], $paths);

            $this->client->deleteObjects([
                'Bucket' => $this->bucket,
                'Delete' => ['Objects' => $objects],
            ]);

            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        $keys = $this->listKeys($directory);

        if (empty($keys)) {
            return true;
        }

        return $this->delete($keys);
    }

    public function copy(string $from, string $to): bool
    {
        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $to,
                'CopySource' => "{$this->bucket}/{$from}",
            ]);

            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function move(string $from, string $to): bool
    {
        if ($this->copy($from, $to)) {
            return $this->delete($from);
        }

        return false;
    }

    public function size(string $path): int
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (int) $result['ContentLength'];
        } catch (AwsException $e) {
            return 0;
        }
    }

    public function lastModified(string $path): int
    {
        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return $result['LastModified']->getTimestamp();
        } catch (AwsException $e) {
            return 0;
        }
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        $results = [];

        try {
            $params = [
                'Bucket' => $this->bucket,
                'Prefix' => $directory,
            ];

            if (!$recursive) {
                $params['Delimiter'] = '/';
            }

            $response = $this->client->listObjectsV2($params);

            if (isset($response['Contents'])) {
                foreach ($response['Contents'] as $object) {
                    $key = $object['Key'];
                    
                    // Skip if it's the directory itself
                    if ($key === $directory || str_ends_with($key, '/')) {
                        continue;
                    }

                    $results[] = $key;
                }
            }
        } catch (AwsException $e) {
            // Return empty array on error
        }

        return $results;
    }

    public function directories(string $directory = ''): array
    {
        $results = [];

        try {
            $response = $this->client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => $directory,
                'Delimiter' => '/',
            ]);

            if (isset($response['CommonPrefixes'])) {
                foreach ($response['CommonPrefixes'] as $prefix) {
                    $results[] = rtrim($prefix['Prefix'], '/');
                }
            }
        } catch (AwsException $e) {
            // Return empty array on error
        }

        return $results;
    }

    public function makeDirectory(string $path): bool
    {
        // S3 doesn't have directories, but we can create an empty object with trailing slash
        return $this->put($path . '/', '');
    }

    public function url(string $path): string
    {
        // Check if custom URL is configured
        if (!empty($this->config['url'])) {
            return rtrim($this->config['url'], '/') . '/' . ltrim($path, '/');
        }

        // Generate default S3 URL
        $region = $this->config['region'] ?? 'us-east-1';
        return sprintf(
            'https://%s.s3.%s.amazonaws.com/%s',
            $this->bucket,
            $region,
            $path
        );
    }

    public function temporaryUrl(string $path, int $expiration = 3600): string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expiration} seconds");

        return (string) $request->getUri();
    }

    public function setVisibility(string $path, string $visibility): bool
    {
        try {
            $this->client->putObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'ACL' => $visibility === 'public' ? 'public-read' : 'private',
            ]);

            return true;
        } catch (AwsException $e) {
            return false;
        }
    }

    public function getVisibility(string $path): string
    {
        try {
            $result = $this->client->getObjectAcl([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            foreach ($result['Grants'] as $grant) {
                if (isset($grant['Grantee']['URI']) && 
                    $grant['Grantee']['URI'] === 'http://acs.amazonaws.com/groups/global/AllUsers') {
                    return 'public';
                }
            }

            return 'private';
        } catch (AwsException $e) {
            return 'private';
        }
    }

    protected function listKeys(string $prefix = ''): array
    {
        $keys = [];

        try {
            $results = $this->client->getPaginator('ListObjectsV2', [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
            ]);

            foreach ($results as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $keys[] = $object['Key'];
                    }
                }
            }
        } catch (AwsException $e) {
            // Return empty array on error
        }

        return $keys;
    }
}
