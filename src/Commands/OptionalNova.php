<?php

namespace Astrotromic\NovaOptional\Commands;

use Composer\Semver\Semver;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;

class OptionalNova extends Command
{
    protected $signature = 'nova:optional {--no-dev}';

    /** @var \Spatie\TemporaryDirectory\TemporaryDirectory|null */
    protected $tempDir = null;

    public function handle(): void
    {
        $this->line('create temp directory');
        $this->tempDir = (new TemporaryDirectory(sys_get_temp_dir()))->create();
        $this->line($this->tempDir->path());

        $this->line('read composer.json');
        $json = collect(json_decode(File::get(base_path('composer.json')), true))
            ->except([
                'autoload',
                'autoload-dev',
                'scripts',
            ]);

        $this->line('update composer.json[repositories]');
        $json->put(
            'repositories',
            collect($json->get('repositories', []))
                ->reject(static function ($repository): bool {
                    return ($repository['type'] ?? null) === 'path' && ($repository['url'] ?? null) === './nova';
                })
                ->map(static function ($repository) {
                    if (! is_array($repository)) {
                        return $repository;
                    }

                    if ($repository['type'] !== 'path') {
                        return $repository;
                    }

                    if (Str::startsWith($repository['url'], '/')) {
                        return $repository;
                    }

                    $repository['url'] = base_path('./'.$repository['url']);

                    return $repository;
                })
                ->add([
                    'type' => 'composer',
                    'url' => 'https://nova.laravel.com',
                ])
                ->values()
                ->all()
        );

        $this->line('write composer.json to temp directory');
        File::put(
            $this->tempDir->path('composer.json'),
            $json->toJson(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->line('run composer install in temp directory');
        $process = Process::fromShellCommandline(
            'composer install --no-interaction '.($this->option('no-dev') ? '--no-dev' : null)
        )->setWorkingDirectory($this->tempDir->path());

        $process->run();

        if (! $process->isSuccessful()) {
            $this->line('installing placeholder');
            $this->line($process->getErrorOutput());
            File::cleanDirectory(base_path('nova'));

            $constraint = $json['require']['laravel/nova'];
            $versions = array_filter(array_map(
                'trim',
                explode(' ', preg_replace('/([^\d\.])/', ' ', $constraint))
            ));
            $version = Arr::first(Semver::satisfiedBy($versions, $constraint));

            File::put(base_path('nova/composer.json'), json_encode([
                'name' => 'laravel/nova',
                'description' => '',
                'version' => $version,
                'autoload' => [
                    "psr-4" => [
                        "Laravel\\Nova\\" => "src/"
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            File::ensureDirectoryExists(base_path('nova/src/Actions'), 0777, true);
            File::put(
                base_path('nova/src/Actions/Actionable.php'),
                <<<STRING
                <?php
                namespace Laravel\Nova\Actions;
                trait Actionable {}
                STRING
            );

            return;
        }

        $this->line('installing nova');
        File::copyDirectory(
            $this->tempDir->path('vendor/laravel/nova'),
            base_path('nova')
        );
    }

    public function __destruct()
    {
        if ($this->tempDir) {
            $this->tempDir->delete();
        }
    }
}
