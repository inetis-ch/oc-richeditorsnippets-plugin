# October Richeditor Snippets

- [Introduction](#introduction)
- [Example usage for Rainlab Syntax Fields](#syntaxFields)
- [Example usage for Rainlab Pages Content Blocks](#contentBlocks)
- [Example usage in fields.yaml](#fields)
- [Passing extra parameters](#extraParameters)

<a name="introduction"></a>
## Introduction

One of the great features of Rainlab Pages plugin is the `Snippets` feature. It allows the developer to hand over management of complex items, such as forms, maps, widgets, etc to the client. For more information on what a Snippet is, please see https://github.com/rainlab/pages-plugin#snippets.

This plugin simply extends the ability to re-use these snippets from any `richeditor` by providing an additional dropdown to the Froala Richeditor with a list of available snippets (supports partial and component based snippets). It also provides a twig filter to allow the parsing of implemented snippets.

For documentation regarding creating snippets, please see https://github.com/rainlab/pages-plugin#snippets-created-from-partials

<a name="syntaxFields"></a>
## Example usage for Rainlab Pages Syntax Fields

Option 1 (offset to variable)
```
{variable type="richeditor" tab="Content" name="text" label="Text"}{/variable}
{{ text | parseSnippets }}
```

Option 2 (wrap in filter)
```
{% filter parseSnippets %}
    {richeditor tab="Content" name="text" label="Text"}{/richeditor}
{% endfilter %}
```

<a name="contentBlocks"></a>
## Example usage for Rainlab Pages Content Blocks

```
{% filter parseSnippets %}
    {% content 'company-details.htm' %}
{% endfilter %}
```

Note this method is useful if you are including a third party component that will output richeditors but you don't want to override its partial.

For example if you are using a richeditor with Rainlab.Blog, you may want to include the component as follow in your CMS page:
```
{% filter parseSnippets %}
    {% component 'blogPost' %}
{% endfilter %}
```

<a name="cmsSettings"></a>
## Customizing CMS Editor settings

In a default October installation, this plugin automatically injects itself to appear in the richeditor toolbar.

But if you customize the "Toolbar Buttons" in the "Editor settings", you may want to add `snippets` in the list, otherwise the button will not appear anymore.

Note for October v2 users: actually we are not able to inject ourselves in the new richeditor toolbar. You will also need to configure it manually in the CMS settings.

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
```
{{ text | parseSnippets({context: 'foo'}) }}
```
```
{% filter parseSnippets({context: 'foo'}) %}
    {richeditor name="text" label="Text"}{/richeditor}
{% endfilter %}
```

You will then be able to access `context` as if it was a component property using `$this->property('context')`.

## Contributors
- Tough Developer: creator of the [original version](https://github.com/toughdeveloper/oc-richeditorsnippets-plugin)
- inetis
- [All Contributors](https://github.com/inetis-ch/oc-richeditorsnippets-plugin/graphs/contributors)
