<?php

namespace AppBundle\Security\User;

use AppBundle\Entity\User;
use Symfony\Component\Translation\Translator;
use Ztec\Security\ActiveDirectoryBundle\Security\User\adUser;
use Ztec\Security\ActiveDirectoryBundle\Security\User\adUserProvider as adUserProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;
use Ztec\Security\ActiveDirectoryBundle\Service\AdldapService;
use adLDAP\adLDAP;
use adLDAP\collections\adLDAPUserCollection;

class UserProvider extends adUserProvider
{
    protected $usernamePatterns = array();
    protected $recursiveGrouproles = false;

    /**
     * @var Translator
     */
    private $translator;

    private $config = array();

    public function __construct(array $config, AdldapService $adLdapService, TranslatorInterface $translator)
    {
        $this->config = $config;
        $this->translator = $translator;

        $this->recursiveGrouproles = $this->getConfig('recursive_grouproles', false);
        $usernamePatterns = $this->getConfig('username_patterns', array());
        foreach ($usernamePatterns as $pat) {
            array_push($this->usernamePatterns, $pat);
        }
    }

    public function loadUserByUsername($username)
    {
        // The password is set to something impossible to find.
        try {
            $userString = $this->getUsernameFromString($username);
            $user = new User(
                $this->getUsernameFromString($userString),
                uniqid(true) . rand(0, 424242),
                array()
            );
        } catch (\InvalidArgumentException $e) {
            $msg = $this->translator->trans(
                'ztec.security.active_directory.invalid_user',
                array('%reason%' => $e->getMessage())
            );
            throw new UsernameNotFoundException($msg);
        }

        return $user;
    }


    /**
     * @param $string
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getUsernameFromString($string)
    {
        $username = $string;
        foreach ($this->usernamePatterns as $pattern) {
            if ($username == $string && preg_match($pattern, $string, $results)) {
                $username = $results[1];
                break;
            }
        }
        $username = strtolower($username);
        $validationPattern = $this->getConfig('username_validation_pattern', '/^[a-z0-9-.]+$/i');
        if (preg_match($validationPattern, $username) == true) {
            return $username;
        } else {
            $msg = $this->translator->trans(
                'ztec.security.active_directory.username_not_matching_rules',
                array(
                    '%username%' => $username
                )
            );
            throw new \InvalidArgumentException($msg);
        }
    }

    public function fetchData(adUser $adUser, TokenInterface $token, adLDAP $adLdap)
    {
        $connected = $adLdap->connect();
        $isAD      = $adLdap->authenticate($adUser->getUsername(), $token->getCredentials());
        if (!$isAD || !$connected) {
            $msg = $this->translator->trans(
                'ztec.security.active_directory.ad.bad_response',
                array(
                    '%connection_status%' => var_export($connected, 1),
                    '%is_AD%'             => var_export($isAD, 1),
                )
            );
            throw new \Exception(
                $msg
            );
        }
        /** @var adLDAPUserCollection $user */
        $user = $adLdap->user()->infoCollection($adUser->getUsername(), array('*'));

        if ($user) {
            $groups = $adLdap->user()->groups($adUser->getUsername(), $this->recursiveGrouproles);
            $sfRoles = array();
            $sfRolesTemp = array();
            foreach ($groups as $r) {
                if (in_array($r, $sfRolesTemp) === false) {
                    $sfRoles[] = 'ROLE_' . strtoupper(str_replace(' ', '_', $r));
                    $sfRolesTemp[] = $r;
                }
            }
            $adUser->setRoles($sfRoles);
            unset($sfRolesTemp);

            $adUser->setDisplayName($user->displayName);
            $adUser->setEmail($user->mail);
            $adUser->setRoles(['ROLE_USER']);
            $adUser->setPassword($token->getCredentials());
            
            return true;
        }
        return false;
    }

    protected function findManagerGUID($adLdap, $dn = '')
    {
        if (!empty($dn)) {
            $filter = "(&(objectClass=user)(samaccounttype=" . adLDAP::ADLDAP_NORMAL_ACCOUNT .")(objectCategory=person)(distinguishedname=".$dn."))";
            $sr = ldap_search($adLdap->getLdapConnection(), $adLdap->getBaseDn(), $filter, ["objectGUID"]);
            $entries = ldap_get_entries($adLdap->getLdapConnection(), $sr);
            if (isset($entries["count"]) && $entries["count"] > 0) {
                return $adLdap->utilities()->decodeGuid($entries[0]["objectguid"][0]);
            }
        }

        return null;
    }

    protected function convertWindowsTimeToDateTime($value)
    {
        return preg_replace(
            "/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2}).+/",
            "$1-$2-$3 $4:$5:$6",
            $value
        );
    }
}
