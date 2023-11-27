<?php

use Cms\Classes\Theme;
use Inetis\RicheditorSnippets\Classes\VersionHelper;

Route::get('/inetis/snippets/list', function () {
    $user = BackendAuth::getUser();

    if (!$user) {
        return response('Forbidden', 401);
    }

    // OC 3.5 does not have a permission for snippets access, but we should still check it on legacy environments
    if (VersionHelper::instance()->usesLegacyPagesSnippets() && !$user->hasAccess('rainlab.pages.access_snippets')) {
        return response('Forbidden', 401);
    }

    // Init a backend controller. In OC3, this will have the effect of initializing the SitePicker widget, thus
    // loading the active front-end theme.
    // Note: in this context, we don't have the _site_id $_GET parameter to know which should be the active theme in a
    // multisite environment. But October does store it in the user preferences Cookie, so it works for now...
    // If this changes in the future, we could either:
    // - Extract the _site_id param from the referer query string (still looks a bit hacky).
    // - Figure out a way to populate snippets list from a Controller instead of this route.
    App::make(Backend\Classes\Controller::class);

    $snippets = collect(VersionHelper::instance()->getSnippetManager()->listSnippets(Theme::getEditTheme()))
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
