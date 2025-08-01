<?php
declare(strict_types=1);

namespace Famiq\ActiveDirectoryUser;

use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\Collection;

class ActiveDirectoryUser extends User
{
    const MAX_ITERATIONS = 8;

    /**
     * Verifica si el usuario pertenece a un grupo específico.
     */
    public function belongsToGroup(string $group = ""): bool
    {
        return $this->hasMany(Group::class, 'member')
            ->where('cn', $group)
            ->exists();
    }

    /**
     * Determina si el usuario es líder de área.
     */
    public function isAreaManager(): bool
    {
        return $this->belongsToGroup("Lider Famiq 1");
    }

    /**
     * Comprueba si el usuario es el gerente general.
     */
    public function isGeneralManager(): bool
    {
        return $this->getMail() == env('GeneralManager', '');
    }

    /**
     * Obtiene el usuario configurado como gerente general.
     */
    public function getGeneralManager(): ?self
    {
        return self::where('mail', env('GeneralManager', ''))->first();
    }

    /**
     * Obtiene el gerente de área asociado al usuario.
     */
    public function getAreaManager(): ?self
    {
        if (env('AreaManager', null)) {
            return self::where('mail', env('AreaManager'))->first();
        }

        $user = $this;
        $iteration = 1;
        while ($user && !$user->isAreaManager()) {
            if ($iteration++ > self::MAX_ITERATIONS) {
                return null;
            }
            $user = $user->getManager();
        }

        // Si el líder inmediato es el gerente general devuelve el de Capital Humano
        if ($user && $user->getMail() == $this->getGeneralManager()->getMail()) {
            return $this->getHRManager();
        }

        return $user;
    }

    /**
     * Devuelve el identificador de empleado.
     */
    public function getEmployeeId(): ?string
    {
        return $this['employeeid'][0] ?? null;
    }

    /**
     * Obtiene los usuarios que dependen directamente de este usuario.
     */
    public function getDirectReports(): array
    {
        $users = [];

        if ($this['directreports']) {
            $users = array_map(function ($user) {
                return self::query()->find($user);
            }, $this['directreports']);
        }

        return $users;
    }

    /**
     * Obtiene los reportes directos incluyendo al usuario actual.
     */
    public function getDirectReportsWhitUser(): array
    {
        return array_merge([$this], $this->getDirectReports());
    }

    /**
     * Devuelve los grupos a los que pertenece el usuario.
     */
    public function getGroups(): Collection
    {
        return $this->hasMany(Group::class, 'member')->with($this->primaryGroup())->get();
    }

    /**
     * Obtiene el nombre común del usuario.
     */
    public function getCommonName(): ?string
    {
        return $this['cn'][0] ?? null;
    }

    /**
     * Devuelve el puesto del usuario.
     */
    public function getPuesto(): string
    {
        return $this->title[0] ?? '*A definir';
    }

    /**
     * Devuelve el departamento al que pertenece el usuario.
     */
    public function getDepartamento(): string
    {
        return $this->department[0] ?? '*A definir';
    }

    /**
     * Obtiene el manager inmediato del usuario.
     */
    public function getManager(bool $excludeGeneralManager = true): ?self
    {
        $manager = $this->manager()->first();
        if ($excludeGeneralManager && $manager && $manager->isGeneralManager()) {
            return null;
        }

        return $manager;
    }

    /**
     * Devuelve el SAM Account Name del usuario.
     */
    public function getSAMAccountName(): ?string
    {
        return $this['samaccountname'][0] ?? null;
    }

    /**
     * Obtiene el sector del usuario.
     */
    public function getSector(): string
    {
        return $this->extensionattribute2[0] ?? '*A definir';
    }

    /**
     * Busca un usuario por su identificador interno.
     */
    public static function findById(string $value): ?self
    {
        return self::where('msds-externaldirectoryobjectid', "=", "User_" . $value)->first();
    }

    /**
     * Busca un usuario por su CN.
     */
    public static function findByCn(string $value): ?self
    {
        return self::where('cn', "=", "User_" . $value)->first();
    }

    /**
     * Busca un usuario por su SAM Account Name.
     */
    public static function findBySAMAccountName(string $value): ?self
    {
        return self::where('samaccountname', $value)->first();
    }

    /**
     * Busca un usuario por su correo electrónico.
     */
    public static function findByMail(string $value): ?self
    {
        return self::where('mail', $value)->first();
    }

    /**
     * Obtiene el usuario configurado como gerente de Recursos Humanos.
     */
    public static function getHRManager(): ?self
    {
        return self::where('mail', env('HRManager', ''))->first();
    }

    /**
     * Devuelve el primer aprobador para procesos internos.
     */
    public function getFirstApprover(): ?self
    {
        $user = $this->getManager();

        if (self::getHRManager()->getMail() == $this->getMail()) {
            return null;
        }

        // Si el líder inmediato es el gerente general devuelve el de Capital Humano
        if ($user && $user->getMail() == $this->getGeneralManager()->getMail()) {
            return $this->getHRManager();
        }

        return $user;
    }

    /**
     * Obtiene el correo electrónico del usuario.
     */
    public function getMail(): ?string
    {
        return $this->mail ? $this->mail[0] : null;
    }

    /**
     * Devuelve el segundo aprobador para procesos internos.
     */
    public function getSecondApprover(): ?self
    {
        $firstApprover = $this->getFirstApprover();

        try {
            $hRManager = self::getHRManager();

            if (self::getGeneralManager()->getMail() == $this->getMail()) {
                return null;
            }

            if (in_array($hRManager->getMail(), [$this->getMail(), $this->getManager() ? $this->getManager()->getMail() : ''])) {
                return null;
            }

            if ($firstApprover && $firstApprover->getMail() == $hRManager->getMail()) {
                return null;
            }

            return $hRManager;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene el número telefónico del usuario.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->telephonenumber[0] ?? null;
    }

    /**
     * Obtiene el número móvil del usuario.
     */
    public function getMobileNumber(): ?string
    {
        return $this->mobile[0] ?? null;
    }

    /**
     * Devuelve la jerarquía de managers hasta el gerente general.
     */
    public function getHierarchy(): array
    {
        $hierarchy = [];
        $user = $this->getManager(false);
        while ($user) {
            $hierarchy[] = $user;
            if ($user->isGeneralManager()) {
                break;
            }
            $user = $user->getManager(false);
        }

        return $hierarchy;
    }

    /**
     * Busca usuarios por departamento.
     */
    public static function findByDepartment(string $department): Collection
    {
        return self::where('department', $department)->get();
    }

    /**
     * Realiza una búsqueda genérica de usuarios.
     */
    public static function searchBy(string $attribute, string $value): Collection
    {
        return self::where($attribute, 'contains', $value)->get();
    }
}
