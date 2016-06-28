# Wordpress Starter Theme - Twig/Timber Integrations

This theme is designed to show developers how Twig/Timber integrations work with a Wordpress theme. Timber is the Wordpress implementation of the Twig templating language, and makes Wordpress templating much cleaner. By default Wordpress mixes logic and view rendering, and forces front-end developers to use PHP to display content.

Twig is a lovely templating language that replaces:

```
<?php echo the_content(); ?>
```

with

```
{{post.content}}
```

## Installation

This theme works out of the box, as long as you have the Timber Library plugin installed in Wordpress. The plugin is available at: https://en-gb.wordpress.org/plugins/timber-library/.

## Twig - more info

More info on the Twig templating language: http://twig.sensiolabs.org/.

## Timber - more info

More info on the Timber framework, which implements Twig in Wordpress: http://upstatement.com/timber/.
