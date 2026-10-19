<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

beforeEach(function () {
    $workflow = Yaml::parseFile(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
    $this->deploymentSteps = array_column($workflow['jobs']['deploy']['steps'], null, 'name');
});

it('rejects missing secrets and unsafe deployment paths before connecting', function (string $path, bool $valid, ?string $missingSecret) {
    $environment = [
        'SFTP_HOST' => 'hosting.example.com',
        'SFTP_PORT' => '22',
        'SFTP_USERNAME' => 'hosting-user',
        'SFTP_PASSWORD' => 'example-password',
        'SFTP_REMOTE_PATH' => $path,
        'SFTP_KNOWN_HOSTS' => 'example-host-key',
    ];

    if ($missingSecret !== null) {
        $environment[$missingSecret] = '';
    }

    $process = new Process(['bash', '-e', '-c', $this->deploymentSteps['Validate deployment configuration']['run']], null, $environment);
    $process->run();

    expect($process->isSuccessful())->toBe($valid);
})->with([
    'dedicated directory' => ['/home/account/optimize', true, null],
    'jailed directory' => ['/optimize/', true, null],
    'account root' => ['/', false, null],
    'repeated root slash' => ['///', false, null],
    'relative directory' => ['optimize', false, null],
    'parent traversal' => ['/home/account/../', false, null],
    'dot directory' => ['/home/account/./optimize', false, null],
    'command injection' => ['/optimize"; rm -rf /', false, null],
    'missing password' => ['/home/account/optimize', false, 'SFTP_PASSWORD'],
    'missing host key' => ['/home/account/optimize', false, 'SFTP_KNOWN_HOSTS'],
]);

it('packages committed application files with freshly built assets but no local secrets or runtime state', function () {
    $filesystem = new Filesystem;
    $directory = dirname(__DIR__, 2).'/.amp/in/deployment-test-'.bin2hex(random_bytes(8));
    $source = $directory.'/source';
    $destination = $directory.'/deployment';

    try {
        $files = [
            'app/example.php' => 'application code',
            'bootstrap/app.php' => 'bootstrap',
            'bootstrap/cache/config.php' => 'cached secrets',
            'config/app.php' => 'config',
            'database/migrations/example.php' => 'migration',
            'public/index.php' => 'entry point',
            'public/build/manifest.json' => 'old manifest',
            'public/build/stale.js' => 'old asset',
            'resources/views/example.blade.php' => 'view',
            'routes/web.php' => 'routes',
            'artisan' => 'artisan',
            'composer.json' => '{}',
            'composer.lock' => '{}',
            'storage/app/public/warehouse/product.jpg' => 'bundled image',
            'storage/logs/laravel.log' => 'private log',
            '.env' => 'production secret',
            'tests/example.php' => 'test code',
        ];

        foreach ($files as $path => $contents) {
            $filesystem->ensureDirectoryExists(dirname($source.'/'.$path));
            $filesystem->put($source.'/'.$path, $contents);
        }

        (new Process(['git', 'init', '--quiet'], $source))->mustRun();
        (new Process(['git', 'add', '.'], $source))->mustRun();
        (new Process(['git', '-c', 'user.name=Deployment Test', '-c', 'user.email=deployment@example.com', 'commit', '--quiet', '-m', 'Fixture'], $source))->mustRun();

        $filesystem->delete($source.'/public/build/stale.js');
        $filesystem->put($source.'/public/build/manifest.json', 'fresh manifest');
        $filesystem->put($source.'/public/build/fresh.js', 'fresh asset');
        $filesystem->put($source.'/public/hot', 'http://local-vite');
        $filesystem->ensureDirectoryExists($source.'/vendor');
        $filesystem->put($source.'/vendor/autoload.php', 'production dependencies');

        $process = new Process(['bash', '-e', '-o', 'pipefail', '-c', $this->deploymentSteps['Assemble deployment']['run']], $source, [
            'DEPLOY_DIRECTORY' => $destination,
        ]);
        $process->mustRun();

        expect(file_get_contents($destination.'/app/example.php'))->toBe('application code')
            ->and(file_get_contents($destination.'/vendor/autoload.php'))->toBe('production dependencies')
            ->and(file_get_contents($destination.'/public/build/manifest.json'))->toBe('fresh manifest')
            ->and(file_get_contents($destination.'/public/build/fresh.js'))->toBe('fresh asset')
            ->and(file_get_contents($destination.'/storage/app/public/warehouse/product.jpg'))->toBe('bundled image');

        foreach (['.env', 'tests', 'storage/logs', 'bootstrap/cache/config.php', 'public/hot', 'public/build/stale.js'] as $path) {
            expect(file_exists($destination.'/'.$path))->toBeFalse();
        }
    } finally {
        $filesystem->deleteDirectory($directory);
    }
});
