<?php

namespace App\Vendor\FamiqADUser;

use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User;

class ActiveDirectoryUser extends User
{
    const MAX_ITERATIONS = 8;

    public function belongsToGroup($group = "")
    {
        return $this->hasMany(Group::class, 'member')
            ->where('cn', $group)
            ->exists();
    }

    public function isAreaManager()
    {
        return $this->belongsToGroup("Lider Famiq 1");
    }

    public function isGeneralManager()
    {
        return $this->getMail() == env('GeneralManager', '');
    }

    public function getGeneralManager()
    {
        return self::where('mail', env('GeneralManager', ''))->first();
    }

    public function getAreaManager()
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

    public function getEmployeeId()
    {
        return $this['employeeid'][0] ?? null;
    }

    public function getDirectReports()
    {
        $users = [];

        if ($this['directreports']) {
            $users = array_map(function ($user) {
                return self::query()->find($user);
            }, $this['directreports']);
        }

        return $users;
    }

    public function getDirectReportsWhitUser()
    {
        return array_merge([$this], $this->getDirectReports());
    }

    public function getGroups()
    {
        return $this->hasMany(Group::class, 'member')->with($this->primaryGroup())->get();
    }

    public function getCommonName()
    {
        return $this['cn'][0] ?? null;
    }

    public function getPuesto()
    {
        return $this->title[0] ?? '*A definir';
    }

    public function getDepartamento()
    {
        return $this->department[0] ?? '*A definir';
    }

    public function getManager($excludeGeneralManager = true)
    {
        $manager = $this->manager()->first();
        if ($excludeGeneralManager && $manager && $manager->isGeneralManager()) {
            return null;
        }

        return $manager;
    }

    public function getSAMAccountName()
    {
        return $this['samaccountname'][0] ?? null;
    }

    public function getSector()
    {
        return $this->extensionattribute2[0] ?? '*A definir';
    }

    static function findById($value)
    {
        return self::where('msds-externaldirectoryobjectid', "=", "User_" . $value)->first();
    }

    static function findByCn($value)
    {
        return self::where('cn', "=", "User_" . $value)->first();
    }

    static function findBySAMAccountName($value)
    {
        return self::where('samaccountname', $value)->first();
    }

    static function findByMail($value)
    {
        return self::where('mail', $value)->first();
    }

    static function getHRManager()
    {
        return self::where('mail', env('HRManager', ''))->first();
    }

    public function getFirstApprover()
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

    public function getMail()
    {
        return $this->mail ? $this->mail[0] : null;
    }

    public function getSecondApprover()
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

    public function getPhoneNumber()
    {
        return $this->telephonenumber[0] ?? null;
    }

    public function getMobileNumber()
    {
        return $this->mobile[0] ?? null;
    }

    public function getHierarchy()
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

    public static function findByDepartment($department)
    {
        return self::where('department', $department)->get();
    }

    public static function searchBy($attribute, $value)
    {
        return self::where($attribute, 'contains', $value)->get();
    }
}
