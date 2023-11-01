<?php namespace Inetis\RicheditorSnippets\Classes;

/***

This file is mostly copied from RainLab\Pages\Classes\Snippet

***/

use ApplicationException;
use Cms\Classes\CmsException;
use Lang;
use Cms\Classes\Snippet;
use Cms\Classes\SnippetManager;
use Cms\Classes\Controller as CmsController;
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
        $map = self::extractSnippetsFromMarkup($markup);
        $controller = CmsController::getController();

        foreach ($map as $snippetDeclaration => $snippetInfo) {
            $snippetCode = $snippetInfo['code'];

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

            $pattern = preg_quote($snippetDeclaration);
            $markup = mb_ereg_replace($pattern, $generatedMarkup, $markup);
        }

        return $markup;
    }

    protected static function extractSnippetsFromMarkup($markup)
    {
        $map = [];
        $matches = [];
        $theme = Theme::getActiveTheme();

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
                        $properties[$propertyName] = $propertyMatches['value'][$index];
                    }
                }

                $componentMatch = [];
                $componentClass = null;

                if (preg_match('/data\-component\s*=\s*"([^"]+)"/', $snippetDeclaration, $componentMatch)) {
                    $componentClass = $componentMatch[1];
                }

                // Apply default values for properties not defined in the markup
                // and normalize property code names.
                $properties = self::preprocessPropertyValues($theme, $snippetCode, $componentClass, $properties);

                $map[$snippetDeclaration] = [
                    'code'       => $snippetCode,
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
        $snippet = SnippetManager::instance()->findByCodeOrComponent($theme, $snippetCode, $componentClass, true);
        if (!$snippet) {
            throw new ApplicationException(Lang::get('rainlab.pages::lang.snippet.not_found', ['code' => $snippetCode]));
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
