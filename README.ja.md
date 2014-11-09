Dependency Injection framework for PHP
======================================

[![Latest Stable Version](https://poser.pugx.org/ray/di/v/stable.png)](https://packagist.org/packages/ray/di)
[![Build Status](https://secure.travis-ci.org/koriym/Ray.Di.png?branch=develop-2)](http://travis-ci.org/koriym/Ray.Di)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/b/koriym/ray.di/badges/quality-score.png?b=develop-2&s=38a2876fe3393f2d5307f3b4c6b5fb0b23812be1)](https://scrutinizer-ci.com/b/koriym/ray.di/?branch=develop-2)
[![Code Coverage](https://scrutinizer-ci.com/g/koriym/Ray.Di/badges/coverage.png?s=676589defaa2a762ac42ed97f2a7e64efc4617b9)](https://scrutinizer-ci.com/g/koriym/Ray.Di/)

**Ray.Di**はGoogleのJava用DI framework [Guice](http://code.google.com/p/google-guice/wiki/Motivation?tm=6)の主要な機能を持つアノテーションベースのDIフレームワークです。
DIを効率よく使用すると以下のようなメリットがあります。

* ロジックとコンフィギュレーションの分離を促進し、ソースコードを読みやすくします。
* コンポーネントの独立性と再利用性を強化します。コンポーネントは依存関係のあるインタフェースを宣言するだけになるため、他のコンポーネントとの関係を疎結合にし再利用性を高めます。
* コーディング量を減少させます。インジェクションの処理そのものはインジェクターが提供するためその分だけ実装するコードの量が減ります。多くの場合、依存を受け取る為のtraitの`use`文を記述するだけです。

Ray.Diは以下の特徴があります。

 * [AOP Alliance](http://aopalliance.sourceforge.net/)に準拠したアスペクト指向プログラミングをサポートしています。
 * [Doctrine.Commons](http://www.doctrine-project.org/projects/common)アノテーションを使用しています。

Getting Started
--------------

### Linked Bindings

Ray.Diを使ったディペンデンシーインジェクション（[依存性の注入](http://ja.wikipedia.org/wiki/%E4%BE%9D%E5%AD%98%E6%80%A7%E3%81%AE%E6%B3%A8%E5%85%A5)）の一般的な例です。

```php
namespace MovieApp;

use Ray\Di\AbstractModule;
use Ray\Di\Di\Inject;
use Ray\Di\Injector;
use MovieApp\FinderInterface;
use MovieApp\Finder;

interface FinderInterface
{
}

interface ListerInterface
{
}

class Finder implements FinderInterface
{
}

class Lister implements ListerInterface
{
    public $finder;

    /**
     * @Inject
     */
    public function setFinder(FinderInterface $finder)
    {
        $this->finder = $finder;
    }
}

class ListerModule extends AbstractModule
{
    public function configure()
    {
        $this->bind(FinderInterface::class)->to(Finder::class);
        $this->bind(ListerInterface::class)->to(Lister::class);
    }
}

$injector = new Injector(new ListerModule);
$lister = $injector->getInstance(ListerInterface::class);
$works = ($lister->finder instanceof Finder::class);
echo(($works) ? 'It works!' : 'It DOES NOT work!');

// It works!
```
これは **Linked Bindings** です。 Linked bindings はインターフェイスとその実装クラスを束縛します。
また束縛は再帰的にされ、依存に必要な依存は〜と順に辿って依存解決をします。

### Provider Bindings

[Provider bindings](http://code.google.com/p/rayphp/wiki/ProviderBindings) はインターフェイスと実装クラスの`プロバイダー`を束縛します。`プロバイダー`は必要とされる依存（インスタンス）を`get`メソッドで返します。

```php
use Ray\Di\ProviderInterface;

interface ProviderInterface
{
    public function get();
}
```

`@Inject`とアノテートするとプロバイダーにも依存が注入されるので`get()`メソッドで依存を提供します。

```php
class DatabaseTransactionLogProvider implements Provider
{
    private $connection;

    /**
     * @Inject
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function get()
    {
        $transactionLog = new DatabaseTransactionLog;
        $transactionLog->setConnection($this->connection);

        return $transactionLog;
    }
}
```
このように依存が必要であったり、生成が複雑なインスタンスは **Provider Bindings**を使います。

```php
$this->bind('TransactionLogInterface')->toProvider('DatabaseTransactionLogProvider');
```



### Named Binding

Rayには`@Named`という文字列で`名前`を指定できるビルトインアノテーションがあります。同じインターフェイスの依存を`名前`で区別します。

メソッドの引数が１つの場合
```php
/**
 *  @Inject
 *  @Named("checkout")
 */
public RealBillingService(CreditCardProcessorInterface $processor)
{
...
```

メソッドの引数が複数の場合は`変数名=名前`のペアでカンマ区切りの文字列を指定します。
```php
/**
 *  @Inject
 *  @Named("processonr=checkout,subProcessor=backup")
 */
public RealBillingService(CreditCardProcessorInterface $processor, CreditCardProcessorInterface $subProcessor)
{
...
```

名前を使って束縛するために`annotatedWith()`メソッドを使います。

```php
protected function configure()
{
    $this->bind('CreditCardProcessorInterface')->annotatedWith('checkout')->to('CheckoutCreditCardProcessor')    $this->bind('CreditCardProcessorInterface')->annotatedWith('backup')->to('CheckoutBackupCreditCardProcessor');
}
```

### Instance Bindings

`toInstance`は値を直接束縛します。

```php
protected function configure()
{
    $this->bind('UserInterface')->toInstance(new User);
}
```

PHPのスカラー値にはタイプヒントが無いので名前を使って束縛します。

```php
protected function configure()
{
    $this->bind()->annotatedWith("login_id")->toInstance('bear');
}
```

### Untargeted Bindings

ターゲットを指定しないで束縛をつくることがで、コンクリートクラスの束縛に便利です。事前にインジェクターに型の情報を伝えるので束縛を事前に行いエラー検知や最適化を行うことができます。
Untargetted bindingsは以下のように`to()`が必要ありません。

```php

protected function configure()
{
    $this->bind(MyConcreteClass::class);
    $this->bind(AnotherConcreteClass::class)->in(Scope::SINGLETON);
}
```

note: annotations is not supported Untargeted Bindings

### Constructor Bindings

`@Inject`アノテーションのないサードパーティーのクラスに特定の束縛を指定するのに`toConstructor`を使うことができます。クラス名と`Named Binding`を指定して束縛します。

```php
<?php
class Car
{
    public function __construct(EngineInterface $engine, $carName)
    {
        // ...
```
```php
<?php
protected function configure()
{
    $this->bind(EngineInterface::class)->annotatedWith('na')->to(NaturalAspirationEngine::class);
    $this->bind()->annotatedWith('car_name')->toInstance('Eunos Roadster');
    $this
        ->bind(CarInterface::class)
        ->toConstructor(
            Car::class,
            'engine=na,carName=car_name' // varName=BindName,...
        );
}
```

この例では`Car`クラスでは`EngineInterface $engine, $carName`と二つの引数が必要ですが、それぞれの変数名に`Named binding`束縛を行い依存解決をしています。

## Scopes

デフォルトでは、Rayは毎回新しいインスタンスを生成しますが、これはスコープの設定で変更することができます。

```php
protected function configure()
{
    $this->bind('TransactionLog')->to('InMemoryTransactionLog')->in(Scope::SINGLETON);
}
```

## Object Life Cycle

オブジェクトライフサイクルのアノテーションを使ってオブジェクトの初期化や、PHPの終了時に呼ばれるメソッドを指定する事ができます。

このメソッドは全ての依存がインジェクトされた後に呼ばれます。
セッターインジェクションがある場合などでも全ての必要な依存が注入された前提にすることができます。

```php
/**
 * @PostConstruct
 */
public function onInit()
{
    //....
}
```
## Install

モジュールは他のモジュールの束縛をインストールして使う事ができます。

 * 同一の束縛があれば先にされた方が優先されますが`overrindeInstall`でインストールすると後からのモジュールが優先されインストールされます。

```php
protected function configure()
{
    $this->install(new OtherModule);
    $this->override(new CustomiseModule);
}
```

## Automatic Injection

Ray.Diは`toInstance()`や`toProvider()`がインスタンスを渡した時に自動的にインジェクトします。
またインジェクターが作られたときにそのインジェクターはモジュールにインジェクトされます。依存にはまた違う依存があり、順に辿って依存を解決します。


## Aspect Oriented Programing

Ray.Aopのアスペクト指向プログラミングが利用できます。

```php
class TaxModule extends AbstractModule
{
    protected function configure()
    {
        $this->bindInterceptor(
            $this->matcher->subclassesOf('Ray\Di\Aop\RealBillingService'),
            $this->matcher->annotatedWith('Tax'),
            [$this->requestInjection('TaxCharger')]
        );
    }
}
```

```php
class AopMatcherModule extends AbstractModule
{
    protected function configure()
    {
        $this->bindInterceptor(
            $this->matcher->any(),                 // In any class and
            $this->matcher->startWith('delete'),   // ..the method start with "delete"
            [$this->requestInjection(Logger::class)]
        );
    }
}

```

Best practice
-------------

可能な限りインジェクターを直接使わないコードにします。その代わりアプリケーションのbootstrapで **ルートオブジェクト** をインジェクトするようにします。
このルートオブジェクトのクラスは依存する他のオブジェクトのインジェクションに使われます。その先のオブジェクトも同じで、依存が依存を必要として最終的にオブジェクトグラフが作られます。

Performance boost
=================

インジェクターオブジェクトをシリアライズすると、束縛の最適化が行われます。
`unserialize`して利用したインジェクターではパフォーマンスが向上します。

```php

// save
$injector = new Injector(new ListerModule);
$cachedInjector = serialize($injector);

// load
$injector = unserialize($cachedInjector);
$lister = $injector->getInstance(ListerInterface::class);

```

Requirement
-----------

* PHP 5.5+
* hhvm


Installation
------------

Ray.Diをインストールにするには [Composer](http://getcomposer.org)を利用します。

```bash
# Add Ray.Di as a dependency
$ composer require ray/di ~2.0@dev
```

Testing Ray.Di
--------------

インストールしてテストとデモプログラムを実行するにはこのようにします。

```bash
$ composer create-project ray/di Ray.Di ~2.0@dev
$ cd Ray.Di
$ phpunit
$ php docs/demo/run.php
```
