Layout Options UI
=================

This module allows existing layouts supplied by other modules or core to have
their default layout plugin class replaced with the Layout Options class.
These layouts will now show the layout configuration options defined in 
your layout_options YAML files.

First, enable this module on your site.

Then, to select the layouts to override, go to the 
Admin->Configuration->System->Layout Options page.  You will see a list of 
providers (module machine names) and layouts that are can be overridden.  
Check the ones you want to override.

Note: Only layouts that use the default Layout Plugin Class will be shown. 
Layouts that use their own custom classes will not work with this.  
