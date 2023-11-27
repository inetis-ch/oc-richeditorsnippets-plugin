<?php namespace Inetis\RicheditorSnippets\Classes;

use October\Rain\Support\Traits\Singleton;

class VersionHelper
{
    use Singleton;

    protected $hasRainLabPages;
    protected $octoberVersion;

    protected function init()
    {
        $this->hasRainLabPages = class_exists('RainLab\Pages\Plugin');
        $this->octoberVersion = $this->guessOctoberVersion();
    }

    /**
     * Check if the system is using the legacy snippets manager from RainLab.Pages or if we can use the native snippets
     * (October 3.5 and up)
     */
    public function usesLegacyPagesSnippets(): bool
    {
        return !$this->hasMinimumOctoberVersion('3.5') && $this->hasRainLabPages;
    }

    /**
     * Check if the current October version is greater than or equal to a given version string.
     */
    public function hasMinimumOctoberVersion(string $minimumVersion): bool
    {
        return version_compare($this->octoberVersion, $minimumVersion) >= 0;
    }

    /**
     * Get an instance of SnippetManager depending on what is available on the current system.
     */
    public function getSnippetManager()
    {
        $managerClass = $this->usesLegacyPagesSnippets()
            ? 'RainLab\Pages\Classes\SnippetManager'
            : 'Cms\Classes\SnippetManager';

        return $managerClass::instance();
    }

    /**
     * Utility trying to get the current October version (\System::VERSION constant was added only since October 2)
     */
    private function guessOctoberVersion(): string
    {
        return class_exists('System') ? \System::VERSION : '1.0';
    }
}
