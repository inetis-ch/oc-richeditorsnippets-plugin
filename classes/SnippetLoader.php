<?php namespace Inetis\RicheditorSnippets\Classes;

use Cache;
use Config;
use Event;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\Theme;
use Cms\Classes\ComponentManager;
use RainLab\Pages\Classes\SnippetManager;

class SnippetLoader
{
	protected static $pageSnippetsCache = null;

	/**
	 * Add a component registered as a snippet to the active controller.
	 *
	 * @param array $snippetInfo	The info of the snippet to register
	 * @return string				The generated unique alias for this snippet
	 */
	public static function registerComponentSnippet($snippetInfo)
	{
		$theme = Theme::getActiveTheme();
		$controller = CmsController::getController();

		// Make an unique alias for this snippet based on its name and parameters
		$snippetInfo['code'] = uniqid($snippetInfo['code'] . '-' . md5(serialize($snippetInfo['properties'])) . '-');

		self::attachComponentSnippetToController($snippetInfo, $controller, true);
		self::cacheSnippet($snippetInfo['code'], $snippetInfo);

		return $snippetInfo['code'];
	}

	/**
	 * Add a partial registered as a snippet to the active controller.
	 *
	 * @param array $snippetInfo	The info of the snippet to register
	 * @return string				The generated unique alias for this snippet
	 */
	public static function registerPartialSnippet($snippetInfo)
	{
		$theme = Theme::getActiveTheme();
		$partialSnippetMap = SnippetManager::instance()->getPartialSnippetMap($theme);
		$snippetCode = $snippetInfo['code'];

		if (!array_key_exists($snippetCode, $partialSnippetMap)) {
			throw new ApplicationException(sprintf('Partial for the snippet %s is not found', $snippetCode));
		}

		return $partialSnippetMap[$snippetCode];
	}

	/**
	 * Save to the cache the component snippets loaded for this page.
	 * Should be called once after all snippets are loaded to avoid multiple serializations.
	 */
	public static function saveCachedSnippets()
	{
		self::fetchCachedSnippets();

		Cache::put(
			self::getMapCacheKey(),
			serialize(self::$pageSnippetsCache),
			Config::get('cms.parsedPageCacheTTL', 10)
		);
	}

	/**
	 * Register back to the current controller all component snippets previously saved.
	 * This make AJAX handlers of these components available.
	 *
	 * @param CmsController $cmsController
	 */
	public static function restoreComponentSnippetsFromCache($cmsController)
	{
		$componentSnippets = self::fetchCachedSnippets();

        foreach ($componentSnippets as $componentInfo) {
            self::attachComponentSnippetToController($componentInfo, $cmsController);
        }
	}

	/**
	 * Attach a component-based snippet to a controller.
	 *
	 * Register the component if it is not defined yet.
	 * This is required because not all component snippets are registered as components,
	 * but it's safe to register them in render-time.
	 *
	 * If asked, the run lifecycle events of the component can be run. This is required for
	 * component that are added late in the page execution like with the twig filter.
	 *
	 * @param array $componentInfo
	 * @param CmsController $controller
	 * @param bool $triggerRun			Should the run events of the component lifecycle be triggered?
	 */
	protected static function attachComponentSnippetToController($componentInfo, CmsController $controller, $triggerRun = false)
	{
		$componentManager = ComponentManager::instance();

		if (!$componentManager->hasComponent($componentInfo['component'])) {
			$componentManager->registerComponent($componentInfo['component'], $componentInfo['code']);
		}

		$component = $controller->addComponent(
			$componentInfo['component'],
			$componentInfo['code'],
			$componentInfo['properties']
		);

		if ($triggerRun) {
			if ($component->fireEvent('component.beforeRun', [], true)) {
                return;
            }

            if ($component->onRun()) {
                return;
            }

            if ($component->fireEvent('component.run', [], true)) {
                return;
			}
		}
	}

	/**
	 * Store a component snippet to the cache.
	 * The cache is not actually saved; saveCachedSnippets() must be called to persist the cache.
	 *
	 * @param string $alias			The unique alias of the snippet
	 * @param array $snippetInfo	The info of the snippet
	 */
	protected static function cacheSnippet($alias, $snippetInfo)
	{
		self::fetchCachedSnippets();
		self::$pageSnippetsCache[$alias] = $snippetInfo;
	}

	/**
	 * Load cached component snippets from the cache.
	 * If it has already be loaded once, it won't do anything.
	 */
	protected static function fetchCachedSnippets()
	{
		if (self::$pageSnippetsCache !== null) {
			return self::$pageSnippetsCache;
		}

        $cached = Cache::get(self::getMapCacheKey(), false);

		if ($cached !== false) {
			$cached = @unserialize($cached);
		}

		if (!is_array($cached)) {
			$cached = [];
		}

		return $cached;
	}

	/**
	 * Get a cache key for the current page.
	 *
	 * @return string
	 */
    protected static function getMapCacheKey()
    {
        $theme = Theme::getActiveTheme();
        $page = CmsController::getController()->getRouter();

        return crc32($theme->getPath() . $page->getUrl()) . '-dynamic-snippet-map';
    }
}
