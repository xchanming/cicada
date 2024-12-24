---
title: Add cicada version to app scripts
issue: NEXT-26010
---
# Core
* Changed `\Cicada\Core\Framework\Script\Execution\ScriptExecutor` to add `cicada.version` global variable to app scripts.
* Changed `\Cicada\Core\Framework\Adapter\Twig\Extension\PhpSyntaxExtension` to add `version_compare` function to app scripts.
___
# Upgrade Information
## App scripts have access to cicada version

App scripts now have access to the cicada version via the `cicada.version` global variable.
```twig
{% if version_compare('6.4', cicada.version, '<=') %}
    {# 6.4 or lower compatible code #}
{% else %}
    {# 6.5 or higher compatible code #}    
{% endif %}
```
