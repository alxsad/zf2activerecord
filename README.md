Zf2ActiveRecord
============

Installation
-------------

step 1 - composer.json
``` json
"require": {
    "alxsad/zf2activerecord": "dev-master"
}
```

step 2 - run command
``` bash
php composer.phar self-update
php composer.phar update
```

Provided Events
-------------
* find.pre
* find.post
* save.pre
* save.post
* delete.pre
* delete.post

Example 1
-------------
module.config.php
``` php
'service_manager' => array(
    'factories' => array(
        'books-active-record' => function ($sm) {
            $adapter = $sm->get('zf2-active-record-adapter');
            $factory = new \Application\Entity\Book();
            $factory->setAdapter($adapter)
                    ->setPrimaryKey('id')
                    ->setTableName('books');
            return $factory;
        },
    ),
)
```

``` php
<?php

namespace Application\Entity;

use Zf2ActiveRecord\AbstractActiveRecord;

class Book extends AbstractActiveRecord
{
    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $author = null;

    /**
     * @var string
     */
    protected $title = null;

    /**
     * @return int
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Book
     */
    public function setId ($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor ()
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return Book
     */
    public function setAuthor ($author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle ()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Book
     */
    public function setTitle ($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Exchange internal values from provided array
     *
     * @param  array $array
     * @return void
     */
    public function exchangeArray (array $array)
    {
        foreach ($array as $key => $value) {
            switch (strtolower($key)) {
                case 'id':
                    $this->setId($value);
                    continue;
                case 'author':
                    $this->setAuthor($value);
                    continue;
                case 'title':
                    $this->setTitle($value);
                    continue;
                default:
                    break;
            }
        }
    }

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy ()
    {
        return array(
            'id'     => $this->getId(),
            'author' => $this->getAuthor(),
            'title'  => $this->getTitle(),
        );
    }
}
```
``` php
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction ()
    {
        /* @var $books \Application\Entity\Book */
        $books = $this->getServiceLocator()->get('books-active-record');

        /* @var $book \Application\Entity\Book */
        $book = $books->findByPk(1);
        $book->setTitle('Very Interested Book');
        $saved = $book->save();

        return array();
    }
}
```

Example 2 (Simple)
-------------
module.config.php
``` php
    'service_manager' => array(
        'factories' => array(
            'books-active-record' => function ($sm) {
                $adapter = $sm->get('zf2-active-record-adapter');
                $factory = new \Zf2ActiveRecord\ActiveRecord($adapter, array(
                    'primaryKey' => 'id',
                    'tableName'  => 'books',
                ));
                return $factory;
            },
        ),
    )
```

``` php
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction ()
    {
        /* @var $books \Zf2ActiveRecord\ActiveRecord */
        $books = $this->getServiceLocator()->get('books-active-record');

        /* @var $book \Zf2ActiveRecord\ActiveRecord */
        $book = $books->create(array(
            'title'  => 'test title',
            'author' => 'test author',
        ));
        $saved = $book->save();

        return array();
    }
}
```

Example 3 (Delete)
-------------
``` php
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction ()
    {
        /* @var $books \Application\Entity\Book */
        $books = $this->getServiceLocator()->get('books-active-record');

        /* @var $book \Application\Entity\Book */
        $book = $books->findByPk(1);
        $deleted = $book->delete();

        return array();
    }
}
```

Example 4 (Find)
-------------
``` php
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction ()
    {
        /* @var $books \Zf2ActiveRecord\ActiveRecord */
        $books = $this->getServiceLocator()->get('books-active-record');

        return array(
            'books' => $books->find(function(\Zend\Db\Sql\Select $select){
                $select->where(array('is_active' => 1));
                $select->limit(10);
            }),
        );
    }
}
```

Example 5 (Events)
-------------
``` php
<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction ()
    {
        $this->getEventManager()->getSharedManager()->attach(
            'Application\Entity\Book', 'save.pre', function($e)
        {
            $book = $e->getTarget();
            if ($book->isNew()) {
                $book->setTitle($book->getTitle() . ' - new');
            }
        });

        /* @var $books \Application\Entity\Book */
        $books = $this->getServiceLocator()->get('books-active-record');

        /* @var $book \Zf2ActiveRecord\ActiveRecord */
        $book = $books->create(array(
            'title'  => 'test title',
            'author' => 'test author',
        ));
        $saved = $book->save();

        return array();
    }
}
```