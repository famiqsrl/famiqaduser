# Famiq ActiveDirectoryUser Vendor

This package exposes the `ActiveDirectoryUser` class with LDAP configuration
required for Active Directory integration. The class mirrors the application's
original model and can be reused as an external dependency. The package also
provides a command for exporting the configuration file.

## New Features

- `FamiqADUser:info {mail}` artisan command to quickly display information about a user.
- Helper methods on `ActiveDirectoryUser`:
  - `getPhoneNumber()` and `getMobileNumber()`.
  - `getHierarchy()` to retrieve the manager chain.
  - `findByDepartment()` and `searchBy()` static search helpers.
