# October Richeditor Snippets

- [Introduction](#introduction)
- [Usage](#usage)
- [Example usage for Rainlab Syntax Fields](#syntaxFields)
- [Example usage for Rainlab Pages Content Blocks](#contentBlocks)
- [Example usage in fields.yaml](#fields)
- [Passing extra parameters](#extraParameters)

<a name="introduction"></a>
## Introduction

For more information about snippets, please refer to the [official documentation](https://docs.octobercms.com/3.x/cms/themes/snippets.html).

Back in the days, snippets were a [feature introduced by](https://octobercms.com/blog/post/introducing-snippets) the RainLab Pages plugin. Unfortunately, they were only usable in RainLab Pages itself, not in other plugins.

This plugin was created in order to add the ability to use snippets (supports partial and component based snippets) in any `richeditor`.

As of [October 3.4 release](https://octobercms.com/support/article/rn-35), snippets are now part of the core and, just like this plugin, available on all RichEditors.

In most cases, users can migrate to the native snippets feature instead of using this plugin, but there is still one reason that may make you need this plugin: AJAX handlers. In the native snippets feature, you need to include the components of every snippet in the layout, hope there is no name conflict between them, and this will not work if your AJAX handler uses a property (it will run with the values from the component, not the ones set in the snippet). You will also run into issues if the same snippet is included multiple times.

<a name="usage"></a>
## Usage
Install this plugin. If your October core version is lower than 3.4.0, also install RainLab.Pages. Then, wherever you want to display a richeditor, apply the `parseSnippets` filter. Note that you don't need to chain it with `|raw`, `|links` or `|content`, as this plugin will take care of running them.
```twig
{{ myRichEditorContent | parseSnippets }}
```

In the backend editor settings, you will need to add `snippets` to the list of "Toolbar buttons".

<a name="syntaxFields"></a>
## Example usage for Rainlab Pages Syntax Fields

Option 1 (offset to variable)
```twig
{variable type="richeditor" tab="Content" name="text" label="Text"}{/variable}

{{ text | parseSnippets }}
```

Option 2 (wrap in `{% apply %}` or `{% filter %}`)
```twig
{% apply parseSnippets %}
    {richeditor tab="Content" name="text" label="Text"}{/richeditor}
{% endapply %}
```

<a name="contentBlocks"></a>
## Example usage for Rainlab Pages Content Blocks

```twig
{% apply parseSnippets %}
    {% content 'company-details.htm' %}
{% endapply %}
```

Note this method is useful if you are including a third party component that will output the content of a richeditors, but you don't want to override its partial.

For example if you are using a richeditor with Rainlab.Blog, you may want to include the component as follows in your CMS page:
```twig
{% apply parseSnippets %}
    {% component 'blogPost' %}
{% endapply %}
```

<a name="fields"></a>
## Example usage in fields.yaml

If you do not set `toolbarButtons` you will not need to add `snippets` to the list. Please see example below when customization is required.

```yaml
html_content:
    type: richeditor
    toolbarButtons: bold|italic|snippets
    size: huge
```

<a name="extraParameters"></a>
## Pass extra parameters
If needed, you can pass extra parameters to your snippets from your theme like this:
```twig
{{ text | parseSnippets({context: 'foo'}) }}
```
```twig
{% apply parseSnippets({context: 'foo'}) %}
    {richeditor name="text" label="Text"}{/richeditor}
{% endapply %}
```

You will then be able to access `context` as if it was a component property using `$this->property('context')`.

## Contributors
- Tough Developer: creator of the [original version](https://github.com/toughdeveloper/oc-richeditorsnippets-plugin)
- [inetis](https://inetis.ch) (current maintainer)
- [All Contributors](https://github.com/inetis-ch/oc-richeditorsnippets-plugin/graphs/contributors)
