Layout Options
==============

This module provides a Layout plugin that allows configuration options to
easily be added layouts using using Yaml files and LayoutOption plugins. In
most cases, using no code just YAML files.

Note:  Initially developed for use with Entity Reference with Layout (ERL) 
       but has been tested with Layout Builder as well.

Overview
========

Layouts defined in *.layouts.yml files that use the LayoutOptions class 
provided by this module can then add configuration options using a combination 
of *.layout_option.yml files and LayoutOption plugins.

The module supplies a set of common LayoutOption plugins.

Some no code examples of configuration options that this module can be built 
with just Yaml files and the supplied default plugins are:

* Adding an id attribute to allow anchor references to content.
* Adding a set of predefined classes to the layout or it's regions.
* Allowing custom classes to be added.
* Attaching libraries to a layout

Installing
==========

Install with composer:
composer require drupal/layout_options

Then enable the module.

How to Use it
=============

Note: This module is a utility module that provides no out of the 
box functionality. 

Defining a Layout
-----------------

First, you need a layout that uses the LayoutOptions class.  Here's a simple 
example that would be in your *.layouts.yml file:

    my_1col_100:
      label: One column - 100
      category: My Layouts
      class: '\Drupal\layout_options\Plugin\Layout\LayoutOptions'
      template: templates/my_1col_100
      icon_map:
        - [main]
      regions:
        main:
          label: Content

You should be able to modify any existing layout without a class by just 
adding the class statement from the sample above.

Defining a *.layout_options.yml File
-------------------------------------

Configuration options are defined in [provider].layout_options.yml files which 
can be located in your theme or a module root directory (just like the 
*.layout.yml files).

There is a commented layout_options.layout_options.yml.examples file in the 
module that supplies examples.

There can be multiple *.layout_options.yml files defined in a site.  These are 
loaded acording to the Drupal module loading rules and then by the installed 
theme rules.

The multiple files are merged into a single layout_options definition file
with the each newer file being able to override values in the previous files.  
The active theme's file can rule them all... :)

The layout_options.yml file has two parts, the option definitions section and
a layout 'rules' section. The definitions section defines available options 
that can be used with layout. The rules section defines which options will be 
used with which layouts and/or field (if applicable).

### Option Definitions Section ###

The option definitions consist of a OptionLayout plugin id plus the related 
configurable items. E.g. a background color option can be defined with yaml 
information like this:

    layout_option_definitions:
      layout_bg_color:
        title: 'Background color'
        description: 'The background color to use with this layout item.'
        default: ''
        # A plugin that handles select form based class attributes.
        plugin: layout_options_class_select  
        multi: false  #select multiple options?
        options:  # The select options shown
          bg-info: 'Info'
          bg-primary: 'Primary'
          bg-secondary: 'Secondary'
          bg-success: 'Success'
          bg-white: 'White'
          bg-transparent: 'Transparent'
        layout: true  # allow option to be used in the overall layout container
        regions: true # allow this option to be used in any regions
        allowed_regions: [left, right]  #optional attribute to limit regions
        weight:  1 # optionally use to order options

This module supplies the following layoutOption plug-ins:

* layout_options_id - Add an id attribute
* layout_options_class_select - Add one or more classes via a select list
* layout_options_class_checkboxes - Add predefined classes via checkboxes
* layout_options_class_string - Add free form classes via textbox
* more to come..

Since these option handlers are plugin based, it is easy to build your own
plugins to handle custom needs.

### Layout Options Section (Rules) ###

This section defines what options (defined above) are used with what layout.
It can also be used to override and customize definitions for different layouts.

Additionally, if you are using ERL, you can use the containing field name to
define/customize options.

It should also be noted that the option definitions have a "layout" and 
"regions" boolean option that can limit where an option will be used.  E.g. 
regions = FALSE means the option is only used in the overall layout 
configuration setting.

Here is a simple example of this section:

    layout_options:
      global:  # Definitions here are added to all layouts.

        layout_id: 
          # Always show the layout_id option but only on the layout settings.
          regions: false

      my_layout_2col_50_50: # Only show if the layout id is my_layout_2col_50_50 
        layout_bg_color: {}

      # An ERL specific field option. It only applies to the field_header field.
      field_header: 
        layout_id: # Disable a global option
          regions: false
          layout: false
    
        layout_custom_classes: {} # Add the custom classes option

See the example layout_option yaml file for some more examples / details.

Layout Option Plugins
=====================

See the plugins defined in the code (src/Plugin/LayoutOption directory) and 
the OptionBase class.

Note, the OptionInterface is a bit mallable at this time.  If you build from 
the OptionBase class, it will minimize issues if it changes a bit.
