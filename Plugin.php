<?php namespace Inetis\RicheditorSnippets;

use Event;
use Inetis\RicheditorSnippets\Classes\VersionHelper;
use Input;
use System\Classes\PluginBase;
use Backend\FormWidgets\RichEditor;
use Backend\Classes\Controller;
use Inetis\RicheditorSnippets\Classes\SnippetParser;
use RainLab\Pages\Controllers\Index as StaticPage;
use Inetis\RicheditorSnippets\Classes\SnippetLoader;
use Backend\Facades\BackendAuth;

/**
 * RicheditorSnippets Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Richeditor Snippets',
            'description' => 'Adds button to Richeditor toolbar to quickly add Snippets.',
            'author'      => 'Tough Developer & inetis',
            'icon'        => 'icon-newspaper-o',
            'homepage'    => 'https://github.com/inetis-ch/oc-richeditorsnippets-plugin'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        // Extend controllers to always have Static Page methods
        Controller::extend(function($widget) {
            $widget->addDynamicMethod('onGetInspectorConfiguration', function() {
                return (new StaticPage)->onGetInspectorConfiguration();
            });

            $widget->addDynamicMethod('onGetSnippetNames', function() {
                return (new StaticPage)->onGetSnippetNames();
            });

            $widget->addDynamicMethod('onInspectableGetOptions', function() {
                return (new StaticPage)->onInspectableGetOptions();
            });
        });

        RichEditor::extend(function($widget) {
            // Adds default CSS/JS for snippets from RainLab.Pages Plugin
            if (VersionHelper::instance()->usesLegacyPagesSnippets()) {
                $widget->addCss('/plugins/rainlab/pages/assets/css/pages.css', 'RainLab.Pages');
                $widget->addJs('/plugins/rainlab/pages/assets/js/pages-page.js', 'RainLab.Pages');
                $widget->addJs('/plugins/rainlab/pages/assets/js/pages-snippets.js', 'RainLab.Pages');
            }

            // Adds custom assets
            if ($this->canAccessSnippets()) {
                $widget->addJs('/inetis/snippets/list');
                $widget->addJs('/plugins/inetis/richeditorsnippets/assets/js/froala.snippets.plugin.js', 'Inetis.RicheditorSnippets');
                $widget->addCss('/plugins/inetis/richeditorsnippets/assets/css/richeditorsnippets.css', 'Inetis.RicheditorSnippets');
            }
        });

        // Register components from cache for AJAX handlers
        Event::listen('cms.page.initComponents', function($controller, $page, $layout) {
            if (Input::ajax()) {
                SnippetLoader::restoreComponentSnippetsFromCache($controller, $page);
            }
        });

        // Save the snippets loaded in this page for use inside subsequent AJAX calls
        Event::listen('cms.page.postprocess', function($controller, $url, $page, $dataHolder) {
            if (!Input::ajax()) {
                SnippetLoader::saveCachedSnippets($page);
            }
        });
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'parseSnippets' => function($html, $params = []) {
                    return SnippetParser::parse($html, $params);
                }
            ]
        ];
    }

    private function canAccessSnippets(): bool
    {
        if (!($user = BackendAuth::getUser())) {
            return false;
        }

        if (VersionHelper::instance()->usesLegacyPagesSnippets()) {
            return $user->hasAccess('rainlab.pages.access_snippets');
        }

        // Since OC 3.4, there is no permission to restrict snippets access
        return true;
    }
}
