<?php

class AppPhpmemadmin
{
    const ROOT_CFG_VERSION = 'phpmemadminVersion';
    
    const LOCAL_CFG_CONF = 'phpmemadminConf';
    
    private $name;
    private $version;
    
    private $rootPath;
    private $currentPath;
    private $neardConf;
    private $neardConfRaw;
    private $enable;
    
    private $conf;
    
    public function __construct($rootPath)
    {
        global $neardConfig, $neardLang;
        Util::logInitClass($this);
        
        $this->name = $neardLang->getValue(Lang::PHPMEMADMIN);
        $this->version = $neardConfig->getRaw(self::ROOT_CFG_VERSION);
        
        $this->rootPath = $rootPath;
        $this->currentPath = $rootPath . '/phpmemadmin' . $this->version;
        $this->neardConf = $this->currentPath . '/neard.conf';
        $this->enable = is_dir($this->currentPath);
        
        $this->neardConfRaw = @parse_ini_file($this->neardConf);
        if ($this->neardConfRaw !== false) {
            $this->conf = $this->currentPath . '/' . $this->neardConfRaw[self::LOCAL_CFG_CONF];
        }
        
        if (!$this->enable) {
            Util::logInfo($this->name . ' is not enabled!');
            return;
        }
        if (!is_dir($this->currentPath)) {
            Util::logError(sprintf($neardLang->getValue(Lang::ERROR_FILE_NOT_FOUND), $this->name . ' ' . $this->version, $this->currentPath));
        }
        if (!is_file($this->neardConf)) {
            Util::logError(sprintf($neardLang->getValue(Lang::ERROR_CONF_NOT_FOUND), $this->name . ' ' . $this->version, $this->neardConf));
        }
        if (!is_file($this->conf)) {
            Util::logError(sprintf($neardLang->getValue(Lang::ERROR_CONF_NOT_FOUND), $this->name . ' ' . $this->version, $this->conf));
        }
    }
    
    public function __toString()
    {
        return $this->getName();
    }
    
    private function replace($key, $value)
    {
        $this->replaceAll(array($key => $value));
    }
    
    private function replaceAll($params)
    {
        $content = file_get_contents($this->neardConf);
    
        foreach ($params as $key => $value) {
            $content = preg_replace('|' . $key . ' = .*|', $key . ' = ' . '"' . $value.'"', $content);
            $this->neardConfRaw[$key] = $value;
        }
    
        file_put_contents($this->neardConf, $content);
    }
    
    public function update($sub = 0, $showWindow = false)
    {
        return $this->updateConfig(null, $sub, $showWindow);
    }
    
    private function updateConfig($version = null, $sub = 0, $showWindow = false)
    {
        global $neardBs, $neardBins;
        $version = $version == null ? $this->version : $version;
        Util::logDebug(($sub > 0 ? str_repeat(' ', 2 * $sub) : '') . 'Update ' . $this->name . ' ' . $version . ' config...');
    
        $alias = $neardBs->getAliasPath() . '/phpmemadmin.conf';
        if (is_file($alias)) {
            Util::replaceInFile($alias, array(
                '/^Alias\s\/phpmemadmin\s.*/' => 'Alias /phpmemadmin "' . $this->getCurrentPath() . '/web/"',
                '/^<Directory\s.*/' => '<Directory "' . $this->getCurrentPath() . '/web/">',
            ));
        } else {
            Util::logError($this->getName() . ' alias not found : ' . $alias);
        }
        
        Util::replaceInFile($this->getConf(), array(
            '/^\s\s\s\s\s\s\s\s"port"/' => '        "port": ' . $neardBins->getMemcached()->getPort(),
        ));
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getVersionList()
    {
        return Util::getVersionList($this->getRootPath());
    }

    public function getVersion()
    {
        return $this->version;
    }
    
    public function setVersion($version)
    {
        global $neardConfig;
        $this->version = $version;
        $neardConfig->replace(self::ROOT_CFG_VERSION, $version);
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    public function getCurrentPath()
    {
        return $this->currentPath;
    }
    
    public function isEnable()
    {
        return $this->enable;
    }

    public function getConf()
    {
        return $this->conf;
    }
}
