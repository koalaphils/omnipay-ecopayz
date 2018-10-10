<?php

namespace AppBundle\Manager;

/**
 * Description of SettingManager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SettingManager extends AbstractManager
{
    const CATEGORY_SYSTEM = 'AppBundle::settings.categories.system';

    protected $menus;
    protected $settings;
    protected $settingsInfo;
    protected $settingCodes;
    protected $existingCodes;

    public function __construct()
    {
        $this->menus = [];
        $this->settings = null;
        $this->settingsInfo = [];
        $this->settingCodes = [];
        $this->existingCodes = [];
    }

    public function registerSettingMenu()
    {
        $menus = [];
        foreach ($this->getKernel()->getBundles() as $bundle) {
            if (method_exists($bundle, 'registerSettingMenu')) {
                foreach ($bundle->registerSettingMenu() as $menuKey => $menu) {
                    list($label, $labelDomain) = explode('::', $menu['label']);
                    //list($description, $descDomain) = explode('::', $menu['description']);
                    $menus[$menuKey] = [
                        'label' => $label,
                        'label_domain' => $labelDomain,
                        'uri' => array_get($menu, 'uri', '/'),
                        'permission' => array_get($menu, 'permission', null),
                    ];
                }
            }

            if (method_exists($bundle, 'registerSettingCodes')) {
                foreach ($bundle->registerSettingCodes() as $code) {
                    $this->settingCodes[] = $code;
                }
            }
        }
        $this->menus = $menus;
    }

    public function getSettingMenus()
    {
        return $this->menus;
    }

    public function getSetting($setting = null, $default = null)
    {
        $this->initSettings();

        if (is_null($setting)) {
            return $this->settings;
        }

        return array_get($this->settings, $setting, $default);
    }

    public function saveSetting($code, $value)
    {
        $this->getConnection()->executeUpdate(
            'INSERT INTO setting (setting_code, setting_value) VALUES (:code, :value) ON DUPLICATE KEY UPDATE setting_value = :value',
            [
                'code' => $code,
                'value' => json_encode($value),
            ]
        );
        array_set($this->settings, $code, $value);
    }

    public function updateSetting($key, $value)
    {
        $code = $this->getCode($key);
        $data = $this->getSetting($key);
        $codeKey = substr($key, strlen($code . '.'));
        $data = $value;
        $dql = null;
        $params = [
            'code' => $code,
            'value' => json_encode($data),
        ];
        $qb = null;
        if (!in_array($code, $this->existingCodes)) {
            $dql = 'INSERT INTO DbBundle:Setting s (s.code, s.value) VALUES (:code, :value)';
            // $qb = $this->getRepository()->getEntityManager()->createQuery($dql);
            $setting = new \DbBundle\Entity\Setting();
            $setting->setCode($code);
            $setting->setValue($data);
            $this->getRepository()->save($setting);

            array_set($this->settings, $key, $value);

            return true;
        }

        if ($code == $key && in_array($code, $this->existingCodes)) {
            $dql = 'UPDATE DbBundle:Setting s SET s.value = :value WHERE s.code = :code';
            $qb = $this->getRepository()->getEntityManager()->createQuery($dql);
        } elseif ($code != $key && in_array($code, $this->existingCodes)) {
            // $params['value'] = json_encode($this->convertValue($codeKey, $data), JSON_FORCE_OBJECT);
            $codeKeys = explode('.', $codeKey);
            $codeKeyCurrent = '$';
            $sqb = $this->getRepository()->getEntityManager()->createQueryBuilder();
            $sqb->update('DbBundle:Setting', 's')->where('s.code = :code');

            for ($i = 0; $i < (count($codeKeys) - 1); ++$i) {
                $codeKeyCurrent .= '."' . $codeKeys[$i] . '"';
                $sqb->set('s.value', 'JSON_SET(s.value, :key_' . $i . ', IFNULL(JSON_EXTRACT(s.value, :key_' . $i . '), JSON_OBJECT()))');
                $params['key_' . $i] = $codeKeyCurrent;
            }

            $codeKeyCurrent .= '."' . $codeKeys[$i] . '"';
            $sqb->set('s.value', 'JSON_SET(s.value, :key_' . (count($codeKeys) - 1) . ', :value)');
            $params['key_' . $i] = $codeKeyCurrent;

            $qb = $sqb->getQuery();
        }

        if ($qb === null) {
            return false;
        }

        $qb->setParameters($params);
        $qb->execute();

        array_set($this->settings, $key, $value);

        return true;
    }

    public function getCode($code)
    {
        if (in_array($code, $this->settingCodes)) {
            return $code;
        }
        $keys = explode('.', $code, -1);

        return $this->getCode(implode('.', $keys));
    }

    public function getSettingCodes()
    {
        return $this->settingCodes;
    }

    public function convertValue($codeKey, $value)
    {
        $keys = explode('.', $codeKey);
        if (count($keys) === 1) {
            return [$codeKey => $value];
        }
        $array = [];
        array_set($array, $codeKey, $value);

        return $array;
    }

    public function getAutoDeclineSechedulerConfig(array $scheduler = []): array
    {
        $task = [];
        foreach ($scheduler[\DbBundle\Entity\Setting::SCHEDULER_TASK] as $task => $config) {
            $tasks[$task] = $config;
        }

        return $tasks;
    }

    public function hasValidTimeInMinutes(array $data = []): bool
    {
        return !empty($data['minutesInterval']) && preg_match('/^[0-9]*$/', $data['minutesInterval']) ? true : false;
    }

    public function flattenSetting($key): array
    {
        return array_dot($this->getSetting($key));
    }

    /**
     * Get Setting Repository.
     *
     * @return \DbBundle\Repository\SettingRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }

    /**
     * @return \AppKernel
     */
    protected function getKernel()
    {
        return $this->getContainer()->get('kernel');
    }

    /**
     * Get Database connection.
     *
     * @param string $name
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection($name = null)
    {
        return $this->getDoctrine()->getConnection($name);
    }

    protected function initSettings()
    {
        if (is_null($this->settings)) {
            $this->settings = [];
            foreach ($this->getKernel()->getBundles() as $bundle) {
                if (method_exists($bundle, 'registerDefaultSetting')) {
                    $dottedCodeSetting = array_dot($bundle->registerDefaultSetting());
                    foreach ($dottedCodeSetting as $code => $value) {
                        array_set($this->settings, $code, $value);
                    }
                }
            }

            $settings = $this->getConnection()->fetchAll('SELECT * FROM setting');
            foreach ($settings as $setting) {
                $this->existingCodes[] = $setting['setting_code'];
                $settingValue = json_decode($setting['setting_value'], true);
                if (is_array($settingValue)) {
                    $flatSetting = array_dot($settingValue);
                    foreach ($flatSetting as $key => $value) {
                        array_set($this->settings, $setting['setting_code'] . '.' . $key, $value);
                    }
                } else {
                    array_set($this->settings, $setting['setting_code'], $settingValue);
                }
            }
        }
    }
}
