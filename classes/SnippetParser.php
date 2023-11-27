<?php namespace Inetis\RicheditorSnippets\Classes;

/***

This file is mostly copied from RainLab\Pages\Classes\Snippet (removed after 1.5.12)
Since October 3.4, it was slightly refactored and moved to \Cms\Classes\PageManager

***/

use ApplicationException;
use Cms\Classes\CmsException;
use Cms\Classes\Controller as CmsController;
use Cms\Classes\PageManager;
use Cms\Classes\Theme;
use SystemException;

class SnippetParser
{
    /**
     * Take a richeditor markup and run snippets contained inside.
     *
     * @param string $markup
     * @param array $params
     * @return string
     * @throws ApplicationException
     * @throws CmsException
     * @throws SystemException
     */
    public static function parse($markup, $params = [])
    {
        // Also process links, like the |content filter does
        if (VersionHelper::instance()->hasMinimumOctoberVersion('3.2')) {
            $markup = PageManager::processLinks($markup);
        }

        $searches = $replaces = [];
        $theme = Theme::getActiveTheme();
        $parsedSnippets = self::extractSnippetsFromMarkup($markup, $theme);
        $controller = CmsController::getController();

        foreach ($parsedSnippets as $snippetDeclaration => $snippetInfo) {
            if (isset($snippetInfo['component'])) {
                // The snippet is a component registered as a snippet
                $snippetAlias = SnippetLoader::registerComponentSnippet($snippetInfo);
                $generatedMarkup = $controller->renderComponent($snippetAlias, $params);
            }
            else {
                // The snippet is a partial
                $partialName = SnippetLoader::registerPartialSnippet($snippetInfo);
                $generatedMarkup = $controller->renderPartial($partialName, array_merge($params, $snippetInfo['properties']));
            }

            $searches[] = $snippetDeclaration;
            $replaces[] = $generatedMarkup;
        }

        if ($searches) {
            $markup = str_replace($searches, $replaces, $markup);
        }

        return $markup;
    }

    protected static function extractSnippetsFromMarkup($markup, $theme)
    {
        $map = [];
        $matches = [];

        // Converts a json: payload from the inspector
        $processPropertyValue = function($value) {
            return str_starts_with($value, 'json:')
                ? json_decode(urldecode(substr($value, 5)), true)
                : $value;
        };

        if (preg_match_all('/\<figure\s+[^\>]+\>[^\<]*\<\/figure\>/i', $markup, $matches)) {
            foreach ($matches[0] as $snippetDeclaration) {
                $nameMatch = [];

                if (!preg_match('/data\-snippet\s*=\s*"([^"]+)"/', $snippetDeclaration, $nameMatch)) {
                    continue;
                }

                $snippetCode = $nameMatch[1];
                $properties = [];
                $propertyMatches = [];
                if (preg_match_all('/data\-property-(?<property>[^=]+)\s*=\s*\"(?<value>[^\"]+)\"/i', $snippetDeclaration, $propertyMatches)) {
                    foreach ($propertyMatches['property'] as $index => $propertyName) {
                        $properties[$propertyName] = $processPropertyValue($propertyMatches['value'][$index]);
                    }
                }

                $componentMatch = [];
                $componentClass = null;
                if (preg_match('/data\-component\s*=\s*"([^"]+)"/', $snippetDeclaration, $componentMatch)) {
                    $componentClass = $componentMatch[1];
                }

                $snippetAjaxMatches = [];
                $snippetAjax = false;
                if (preg_match('/data\-ajax\s*=\s*"([^"]+)"/', $snippetDeclaration, $snippetAjaxMatches)) {
                    $snippetAjax = $snippetAjaxMatches[1] === 'true' || $snippetAjaxMatches[1] === '1';
                }

                // Apply default values for properties not defined in the markup
                // and normalize property code names.
                $properties = self::preprocessPropertyValues($theme, $snippetCode, $componentClass, $properties);
                $map[$snippetDeclaration] = [
                    'code'       => $snippetCode,
                    'useAjax'    => $snippetAjax,
                    'component'  => $componentClass,
                    'properties' => $properties
                ];
            }
        }

        return $map;
    }

    /**
     * Applies default property values and fixes property names.
     *
     * As snippet properties are defined with data attributes, they are lower case, whereas
     * real property names are case sensitive. This method finds original property names defined
     * in snippet classes or partials and replaces property names defined in the static page markup.
     */
    protected static function preprocessPropertyValues($theme, $snippetCode, $componentClass, $properties)
    {
        $manager = VersionHelper::instance()->getSnippetManager();
        $snippet = $manager->findByCodeOrComponent($theme, $snippetCode, $componentClass, true);

        // Try without cache
        if (!$snippet) {
            $snippet = $manager->findByCodeOrComponent($theme, $snippetCode, $componentClass, false);
        }

        // Cannot proceed
        if (!$snippet) {
            return [];
        }

        $properties = array_change_key_case($properties);
        $snippetProperties = $snippet->getProperties();

        foreach ($snippetProperties as $propertyInfo) {
            $propertyCode = $propertyInfo['property'];
            $lowercaseCode = strtolower($propertyCode);

            if (!array_key_exists($lowercaseCode, $properties)) {
                if (array_key_exists('default', $propertyInfo)) {
                    $properties[$propertyCode] = $propertyInfo['default'];
                }
            }
            else {
                $markupPropertyInfo = $properties[$lowercaseCode];
                unset($properties[$lowercaseCode]);
                $properties[$propertyCode] = $markupPropertyInfo;
            }
        }

        return $properties;
    }
}
