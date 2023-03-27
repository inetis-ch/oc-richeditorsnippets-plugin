<?php

use Cms\Classes\Theme;
use RainLab\Pages\Classes\SnippetManager;
use RainLab\Pages\Controllers\Index as StaticPage;

Route::get('/inetis/snippets/list', function () {
    $user = BackendAuth::getUser();

    if (!$user || !$user->hasAccess('rainlab.pages.access_snippets')) {
        return response('Forbidden', 401);
    }

    // Init StaticPages controller. In OC3, this will have the effect of initializing the SitePicker widget, thus
    // loading the active front-end theme.
    // Note: in this context, we don't have the _site_id $_GET parameter to know which should be the active theme in a
    // multisite environment. But October does store it in the user preferences Cookie, so it works for now...
    // If this changes in the future, we could either:
    // - Extract the _site_id param from the referer query string (still looks a bit hacky).
    // - Figure out a way to populate snippets list from the base BackendController instead of this route.
    $controller = App::make(StaticPage::class);

    $snippets = collect(SnippetManager::instance()->listSnippets(Theme::getEditTheme()))
        ->transform(function ($item) {
            return [
                'component' => $item->getComponentClass(),
                'snippet'   => $item->code,
                'name'      => $item->getName(),
            ];
        })
        ->keyBy('snippet');

    return response('$.oc.snippets = ' . $snippets, 200)
        ->header('Content-Type', 'application/javascript');

})->middleware('web');
