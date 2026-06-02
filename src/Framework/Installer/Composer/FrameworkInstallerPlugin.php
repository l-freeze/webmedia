<?php

declare(strict_types=1);

namespace FrameworkInstaller\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

final class FrameworkInstallerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $root = $this->rootPath(composer: $composer);

        $this->runScaffold(
            root: $root,
            script: 'scaffold-kernel',
            requiredFile: $root . '/apps/kernel/config/application.php',
            io: $io,
        );
        $this->runScaffold(
            root: $root,
            script: 'scaffold-shared',
            requiredFile: $root . '/apps/shared/config/container.php',
            io: $io,
        );
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    private function rootPath(Composer $composer): string
    {
        $vendorDir = (string) $composer->getConfig()->get('vendor-dir');
        return dirname($vendorDir);
    }

    private function runScaffold(string $root, string $script, string $requiredFile, IOInterface $io): void
    {
        if (is_file($requiredFile)) {
            return;
        }

        $packageRoot = dirname(__DIR__, 4);
        $scriptPath = $packageRoot . '/bin/' . $script;
        if (!is_file($scriptPath)) {
            $io->writeError("<error>Framework installer could not find {$scriptPath}</error>");
            return;
        }

        $command = escapeshellcmd(PHP_BINARY)
            . ' '
            . escapeshellarg($scriptPath)
            . ' --root='
            . escapeshellarg($root);

        passthru(command: $command, result_code: $exitCode);
        if ($exitCode !== 0) {
            $io->writeError("<error>Framework installer failed to run {$script}</error>");
        }
    }
}
