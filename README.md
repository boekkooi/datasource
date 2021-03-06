# FSi DataSource Component #

DataSource allows to fetch data from various sources using different drivers.
It supports pagination and through fields (that driver or its extensions must provide) allows
to give various conditions, that fetched data must fulfill.

DataSource to fetch specific kind of data (from database, xml, json, etc.) must be created with
properly configured driver, that will implement methods to get that kind of data.

## Basic usage ##

Let's assume we want to create DataSource with DoctrineDriver (you can find documentation for specific drivers in ``doc``
folder) that allows us to fetch some data through Doctrine ORM. For our example let's assume we have a News object (entity) with
id, title, author, create date, short content and content. We will need only a ``$managerRegistry`` instance. Then we can create
``$datasource`` instance.

``` php
<?php

use FSi\Component\DataSource\Driver\DriverFactoryManager;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineFactory;
use FSi\Component\DataSource\Driver\Doctrine\ORM\Extension\Core\CoreExtension;
use FSi\Component\DataSource\DataSourceFactory;

$managerRegistry = $this->getDoctrine()->getManager();

$driverExtensions = array(new CoreExtension());

$driverFactoryManager = new DriverFactoryManager(array(
    new DoctrineFactory($managerRegistry, $driverExtensions);
));

$extensions = array(
    //(...) Extensions that have to be loaded to DataSource after creation.
);

$factory = new DataSourceFactory($driverFactoryManager, $extensions);

$driverOptions = array(
    'entity' => 'FSiDemoBundle:News'
);
$datasource = $factory->createDataSource('doctrine-orm',  $driverOptions, 'datasource_name');

```

Then, if we want to give some conditions for returned data we need to specify fields, their type and way of comparison.

``` php
<?php

$datasource
    ->addField('id', 'number', 'eq')
    ->addField('title', 'text', 'like')
    ->addField('author', 'text', 'eq')
    ->addField('create_date', 'datetime', 'between', array(
        'someoption' => 'somevalue', //Specific options that this field allows.
    ))
    ->addField('content', 'text', 'like')
;
```

If we have configured DataSource we can bind some parameters to it.
``` php
<?php

$parameters = array(
    'datasource_name' => array(
        'fields' => array( // In fact this key always equals to constant \FSi\Component\DataSource\DataSourceInterface::PARAMETER_FIELDS.
            'title' => 'part of searched title',
            'author' => 'author@example.com',
            'create_date' => array( // Input data doesn't have to be scalar, but it must in form that fields expects it.
                'from' => '2012-04-04',
                'to' => '2012-12-12',
            ),
        ),
    ),
);

$datasource->bindParameters($parameters);
```

We can also set proper pagination.
``` php
<?php

$datasource->setMaxResults(20);
$datasource->setFirstResult(0);
```

And at last we can fetch our data
``` php
<?php

$result = $datasource->getResult();
```

or create view helpfull during view rendering (see below for more info).
``` php
<?php

$view = $datasource->createView();
```

Result that is returned always implements ``Traversable`` interface.

Note, that in fact all you need to do to fetch data is create DataSource and call ``getResult`` method, other steps are optional.

## View ##

While view rendering you should use DataSourceView. It's main purpose is to keep some attributes given by extensions, that are needed
to render various links, paginations, etc. For more details see documentation of extensions in ``doc`` folder.

To get these attributes you can use methods ``hasAttribute``, ``setAttribute``, ``getAttribute``, ``getAttributes`` and ``removeAttribute``.

View also have three methods to get parameters of current datasource, other ones on page or all of them (in case there is more
than one DataSource on page), these methods are: ``getParameters``, ``getAllParameters`` and ``getOtherParameters``. Parameters
you get allows you to regenerate actual state of DataSource (what means if you bind parameters you got from ``getParameters``
DataSource will be **in the same state** it was before binding).

These methods return multidimensional array, so it's up to you to transform it to state that is suitable to send as GET parameters
(like ``name[param1][param2]``).

View contains also FieldViews (one for each field, described below). You have various ways to get to them, since View implements
``ArrayAccess``, ``Countable``, and ``SeekableIterator``. 

``` php
<?php

$view = $datasource->createView(); //Main view.

foreach ($view as $fieldView) {
    // (...)
}

count($view);

$view['fieldname1'].hasAttribute('foo');

```

**Note:** Remember you can't set anything using ``ArrayAccess`` interface, constructions like ``unset($view['field1'])`` or 
``$view['field2'] = 'sth'`` won't work.

## FieldView ##

FieldView allows to set some specific attributes that relates to that specific field. You can access them by the same methods like in View: 
``hasAttribute``, ``setAttribute``, ``getAttribute``, ``getAttributes`` and ``removeAttribute``.

To see which attributes are set in which case see extensions docs.

## Extensions ##

**You can find available extensions documentation in doc folder.**

In general there are three types of extensions: DataSource extensions, Driver extension, and Field extensions.

All parts of this component use Symfony's EventDispatcher (``Symfony\Component\EventDispatcher\EventDispatcher``) to manage events.

### DataSource extensions ###

Each extension must implement interface ``FSi\Component\DataSource\DataSourceExtensionInterface``.
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.
Method ``loadDriverExtensions`` must return array of objects that (if any) must be valid driver extensions (see below).

Each of subscribers can subscribe to one or many of following events:
(list contains key, that is const of ``FSi\Component\DataSource\Event\DataSourceEvents`` and event method's argument, which is defined
in ``FSi\Component\DataSource\Event\DataSourceEvent`` namespace)

* ``PRE_BIND_PARAMETERS`` - ``ParametersEventArgs``
* ``POST_BIND_PARAMETERS`` - ``DataSourceEventArgs``
* ``PRE_GET_RESULT`` - ``DataSourceEventArgs``
* ``POST_GET_RESULT`` - ``ResultEventArgs``
* ``PRE_BUILD_VIEW`` - ``ViewEventArgs``
* ``POST_BUILD_VIEW`` - ``ViewEventArgs``
* ``PRE_GET_PARAMETERS`` - ``ParametersEventArgs``
* ``POST_GET_PARAMETERS`` - ``ParametersEventArgs``

All of arguments allows to access ``DataSource`` through ``getDataSource`` method.

Arguments:

* ``DataSourceEventArgs`` - just gives access to DataSource (see above)
* ``ParametersEventArgs`` - allows to get and set parameters through ``getParameters`` and ``setParameters`` methods
* ``ViewEventArgs`` - allows to get view through ``getView`` method
* ``ResultEventArgs`` - allows to get and set result through ``getResult`` and ``setResult`` methods

### Driver extensions ###

Each extension must implement interface ``FSi\Component\DataSource\Driver\DriverExtensionInterface``.
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.

Driver extension must implement method ``getExtendedDriverTypes()`` which returns types of drivers that this extension is
suitable for.

Driver extension provides field types through methods ``hasFieldType`` and ``getFieldType``,
where getFieldType must return field object for given type, that implements ``FSi\Component\DataSource\Field\FieldTypeInterface``
and already has all its extensions loaded.

Each of subscribers can subscribe to one of following events:
(list contains key, that is const of ``FSi\Component\DataSource\Event\DriverEvents`` and event method's argument, that is defined
in ``FSi\Component\DataSource\Event\DriverEvent`` namespace)

* ``PRE_GET_RESULT`` - ``DriverEventArgs``
* ``POST_GET_RESULT`` - ``ResultEventArgs``

Arguments:

* ``DriverEventArgs`` - allows to access driver through ``getDriver`` method
* ``ResultEventArgs`` - allows to access driver (like ``DriverEventArgs``) and to set and get result through ``getResult`` and ``setResult`` methods

### Extension for Field ###

Each extension must implement interface ``FSi\Component\DataSource\Field\FieldExtensionInterface``
Method ``loadSubscribers`` must return array of objects that (if any) must implement ``Symfony\Component\EventDispatcher\EventSubscriberInterface``.

Field extension must implement method ``getExtendedFieldTypes()`` which returns types of fields that this extension is
suitable for.

Each of subscribers can subscribe to one of following events:
(list contains key, that is const of ``FSi\Component\DataSource\Event\FieldEvents`` and event method's argument, that is defined
in ``FSi\Component\DataSource\Event\FieldEvent`` namespace)

* ``PRE_BIND_PARAMETER`` - ``ParameterEventArgs``
* ``POST_BIND_PARAMETER`` - ``FieldEventArgs``
* ``POST_BUILD_VIEW`` - ``ViewEventArgs``
* ``POST_GET_PARAMETER`` - ``ParameterEventArgs``

All of arguments allows to access ``Field`` through ``getField`` method.

Arguments:

* ``FieldEventArgs`` - just gives access to field (see above)
* ``ParameterEventArgs`` - allows to get and set Parameter through ``getParameter`` and ``setParameter`` methods
* ``ViewEventArgs`` - allows to get FieldView through ``getView`` method
