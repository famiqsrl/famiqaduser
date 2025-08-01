# FamiqADUser Vendor

Integración con Active Directory pensada para aplicaciones Laravel. El paquete
exponen la clase `ActiveDirectoryUser` con métodos utilitarios para consultar
la estructura organizacional y comandos que facilitan la gestión de
configuración.

## Requisitos

- PHP 8.1 o superior.
- Laravel 8 o posterior.

## Instalación

Instala el paquete mediante Composer:

```bash
composer require famiq/ad-user
```

Publica el archivo de configuración ejecutando:

```bash
php artisan FamiqADUser:export
```

Esto generará `config/ldap.php` con la configuración por defecto.

## Uso

El comando `FamiqADUser:info {mail}` permite consultar datos básicos de un
usuario directamente desde la consola:

```bash
php artisan FamiqADUser:info usuario@dominio.com
```

La clase `ActiveDirectoryUser` incluye métodos como `getPhoneNumber()` o
`getHierarchy()` para obtener información adicional del directorio.

## Novedades

- Métodos de búsqueda `findByDepartment()` y `searchBy()`.
- Obtención de teléfonos y jerarquía de managers.
