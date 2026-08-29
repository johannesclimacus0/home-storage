# Artisan Factory

Набросок, прототип, макет - как угодно. Пакет позволяющий запускать фабрики через Artisan, чтобы лишний раз не лезть в Tinker.

Писалось на скорую руку и для собственного удобства. Функционал минимален, не совсем продуман. Вся логика собрана в одном файле.
В дальнейшем код будет нормально разнесён по классам, отрефакторен и в целом приведён в более приличный вид.
## Требования

- PHP 8.3+
- Laravel 13

## Установка

```
composer require johannesclimacus/artisan-factory --dev
```


## Примеры использования

Создать одну запись:

```
php artisan factory:create User
```

Создать несколько записей:

```
php artisan factory:create User --count=5
```

Применить состояние фабрики:

```
php artisan factory:create User --state=unverified
```

Переопределить атрибуты фабрики:

```
php artisan factory:create User --set="name=Test User" --set="email=test@example.com"
```

Специальные значения null, true и false автоматически преобразуются в соответствующие PHP-значения. Всё остальное остаётся строками.

## Конфигурация

Опубликовать файл конфигурации:

```
php artisan vendor:publish --tag=factory-create-config
```

В конфигурации можно задать максимальное количество записей, создаваемых одной командой, и namespace моделей.

## Тестирование

```
composer test
```

## License

MIT
